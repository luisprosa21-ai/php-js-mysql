<?php

declare(strict_types=1);

namespace Lab\Services;

/**
 * Servicio de autenticación JWT manual (sin librerías externas).
 *
 * JWT (JSON Web Token) es un estándar abierto (RFC 7519) para transmitir
 * información entre partes de forma segura como un objeto JSON firmado.
 *
 * Estructura de un JWT:
 *   header.payload.signature
 *
 * Cada parte está codificada en Base64URL (no Base64 estándar).
 * La firma garantiza que el token no ha sido manipulado.
 *
 * ❌ NO HAGAS ESTO: guardar el JWT en localStorage (vulnerable a XSS)
 * ✅ MEJOR ASÍ: guardar en cookie HttpOnly; SameSite=Strict
 */
class AuthService
{
    /**
     * @param UserService $us     Servicio de usuarios para verificar credenciales
     * @param string      $secret Clave secreta para firmar los JWT (mínimo 32 chars)
     * @param int         $expiry Segundos de validez del token (por defecto 1 hora)
     */
    public function __construct(
        private UserService $us,
        private string $secret,
        private int $expiry = 3600
    ) {}

    /**
     * Autentica al usuario con email y contraseña y devuelve un JWT.
     *
     * @param string $email    Email del usuario
     * @param string $password Contraseña en texto plano
     * @return array{token: string, expires_at: int, user: array}
     * @throws \RuntimeException Si las credenciales son incorrectas
     */
    public function login(string $email, string $password): array
    {
        $user = $this->us->findByEmail($email);

        if ($user === null || !$this->us->verifyPassword($password, $user->getPasswordHash())) {
            // ✅ Mismo mensaje de error para email y contraseña incorrectos
            // Esto evita que un atacante sepa si el email existe en el sistema
            throw new \RuntimeException('Credenciales inválidas.');
        }

        $expiresAt = time() + $this->expiry;

        $token = $this->generateToken([
            'sub'   => $user->id,
            'email' => $user->getEmail(),
            'role'  => $user->getRole(),
            'exp'   => $expiresAt,
            'iat'   => time(),
        ]);

        return [
            'token'      => $token,
            'expires_at' => $expiresAt,
            'user'       => $user->jsonSerialize(),
        ];
    }

    /**
     * Genera un JWT firmado con HMAC-SHA256.
     *
     * @param array<string, mixed> $payload Los claims del JWT
     * @return string El token JWT en formato header.payload.signature
     */
    public function generateToken(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        $payload = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->sign($header, $payload);

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Verifica y decodifica un JWT.
     *
     * @param string $token El JWT a verificar
     * @return array<string, mixed> El payload decodificado
     * @throws \RuntimeException Si el token es inválido o ha expirado
     */
    public function verifyToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \RuntimeException('Token JWT malformado: debe tener 3 partes.');
        }

        [$header, $payload, $signature] = $parts;

        // 1. Verificar la firma
        $expectedSignature = $this->sign($header, $payload);

        // ✅ Comparación constante de tiempo para prevenir timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \RuntimeException('Firma JWT inválida: el token ha sido manipulado.');
        }

        // 2. Decodificar y verificar claims
        $claims = json_decode($this->base64UrlDecode($payload), true);

        if (!is_array($claims)) {
            throw new \RuntimeException('Payload JWT inválido.');
        }

        if (isset($claims['exp']) && $claims['exp'] < time()) {
            throw new \RuntimeException('Token JWT expirado.');
        }

        return $claims;
    }

    /**
     * Codifica datos en Base64URL (RFC 4648 §5).
     *
     * Base64URL difiere del Base64 estándar en que usa '-' en lugar de '+'
     * y '_' en lugar de '/', y elimina el relleno '='. Esto hace la cadena
     * segura para usar en URLs y headers HTTP.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica datos en Base64URL.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) + (4 - strlen($data) % 4) % 4, '=');
        return base64_decode(strtr($padded, '-_', '+/'));
    }

    /**
     * Genera la firma HMAC-SHA256 para el JWT.
     *
     * @param string $header  Header codificado en Base64URL
     * @param string $payload Payload codificado en Base64URL
     * @return string La firma en Base64URL
     */
    private function sign(string $header, string $payload): string
    {
        return $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );
    }
}
