<?php

declare(strict_types=1);

/**
 * LAB 07: Sesiones y Autenticación — JWT manual
 * ===============================================
 * Ejecutar: php labs/php/07_sessions_auth.php
 *
 * NOTA: Este lab NO necesita servidor web.
 * Demuestra JWT paso a paso y configuración segura de sesiones.
 *
 * Cubre: anatomía JWT, generación paso a paso, verificación,
 * manipulación fallida, configuración segura de sesiones PHP.
 */

function separator(string $title): void {
    echo "\n" . str_repeat('═', 60) . "\n  {$title}\n" . str_repeat('═', 60) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 1: Anatomía de un JWT');
// ════════════════════════════════════════════════════════════════════════════
// Un JWT tiene 3 partes separadas por puntos (.):
//   header.payload.signature
// Cada parte está codificada en Base64URL (no es cifrado, es encoding)
// ✅ RESULTADO: el payload es LEGIBLE por cualquiera → no guardes datos sensibles

$realToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsImVtYWlsIjoiYWRtaW5AbGFiLnRlc3QiLCJyb2xlIjoiYWRtaW4iLCJleHAiOjk5OTk5OTk5OTksImlhdCI6MTcwMDAwMDAwMH0.signature_placeholder';

echo "\n  Token JWT de ejemplo:\n";
echo "  {$realToken}\n\n";

$parts = explode('.', $realToken);
echo "  PARTE 1 — Header (tipo y algoritmo):\n";
echo "  Base64URL: {$parts[0]}\n";
// Decodificar manualmente
$headerDecoded = json_decode(base64_decode(str_pad(strtr($parts[0], '-_', '+/'), strlen($parts[0]) % 4 ? strlen($parts[0]) + 4 - strlen($parts[0]) % 4 : 0, '=')), true);
echo "  Decoded: " . json_encode($headerDecoded, JSON_PRETTY_PRINT) . "\n\n";

echo "  PARTE 2 — Payload (claims/datos):\n";
echo "  Base64URL: {$parts[1]}\n";
$payloadDecoded = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4 ? strlen($parts[1]) + 4 - strlen($parts[1]) % 4 : 0, '=')), true);
echo "  Decoded: " . json_encode($payloadDecoded, JSON_PRETTY_PRINT) . "\n\n";

echo "  PARTE 3 — Signature (verificación de integridad):\n";
echo "  {$parts[2]}\n";
echo "  → HMAC-SHA256(header + '.' + payload, secret_key)\n";
echo "  → Solo quien conoce el secret puede generar o verificar la firma\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 2: Generación de JWT paso a paso');
// ════════════════════════════════════════════════════════════════════════════

// 👉 MODIFICA: cambia el secret y observa que el token cambia completamente
$secret  = 'mi-super-secret-key-2024'; // ❌ En producción: mínimo 32 bytes aleatorios
$payload = [
    'sub'   => 1,                    // Subject: ID del usuario
    'email' => 'admin@lab.test',
    'role'  => 'admin',
    'exp'   => time() + 3600,        // Expiry: 1 hora desde ahora
    'iat'   => time(),               // Issued at: momento de emisión
    'jti'   => bin2hex(random_bytes(8)), // JWT ID: identificador único (evita replay)
];

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string {
    $padded = str_pad($data, strlen($data) + (4 - strlen($data) % 4) % 4, '=');
    return base64_decode(strtr($padded, '-_', '+/'));
}

// Paso 1: Codificar el header
$headerJson  = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
$headerB64   = base64UrlEncode($headerJson);

// Paso 2: Codificar el payload
$payloadJson = json_encode($payload);
$payloadB64  = base64UrlEncode($payloadJson);

// Paso 3: Generar la firma
$signingInput = "{$headerB64}.{$payloadB64}";
$signature   = base64UrlEncode(hash_hmac('sha256', $signingInput, $secret, true));

// Paso 4: Ensamblar el token
$token = "{$headerB64}.{$payloadB64}.{$signature}";

echo "\n  Generación paso a paso:\n\n";
echo "  Step 1 — Header JSON:     {$headerJson}\n";
echo "  Step 1 — Header Base64URL: {$headerB64}\n\n";
echo "  Step 2 — Payload JSON:    " . substr($payloadJson, 0, 60) . "...\n";
echo "  Step 2 — Payload Base64URL: {$payloadB64}\n\n";
echo "  Step 3 — Signing input:   {$headerB64}.{$payloadB64}\n";
echo "  Step 3 — Signature:       {$signature}\n\n";
echo "  ✅ Token final:\n";
echo "  {$token}\n";

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 3: Verificación de JWT');
// ════════════════════════════════════════════════════════════════════════════

function verifyJwt(string $token, string $secret): array|false {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        echo "  ❌ Token malformado\n";
        return false;
    }

    [$header, $payload, $sig] = $parts;

    // Verificar firma
    $expectedSig = base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));
    if (!hash_equals($expectedSig, $sig)) {
        echo "  ❌ Firma inválida — el token ha sido manipulado\n";
        return false;
    }

    $claims = json_decode(base64UrlDecode($payload), true);

    // Verificar expiración
    if (isset($claims['exp']) && $claims['exp'] < time()) {
        echo "  ❌ Token expirado (exp: " . date('Y-m-d H:i:s', $claims['exp']) . ")\n";
        return false;
    }

    return $claims;
}

echo "\n  Verificando el token generado:\n";
$claims = verifyJwt($token, $secret);
if ($claims) {
    echo "  ✅ Token válido. Claims:\n";
    echo "     sub={$claims['sub']}, email={$claims['email']}, role={$claims['role']}\n";
    echo "     exp=" . date('Y-m-d H:i:s', $claims['exp']) . "\n";
}

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 4: JWT manipulado — falla la verificación');
// ════════════════════════════════════════════════════════════════════════════
// Un atacante intenta cambiar su rol a 'admin' modificando el payload.
// ✅ RESULTADO: la firma no coincide → token rechazado.

$parts = explode('.', $token);

// Atacante intenta cambiar role='user' a role='admin' en el payload
$maliciousPayload = base64UrlDecode($parts[1]);
$maliciousData = json_decode($maliciousPayload, true);
$maliciousData['role'] = 'super-admin'; // 🔓 Intento de escalada de privilegios
$maliciousData['sub']  = 999;           // 🔓 Intento de suplantar otro usuario
$tampered = $parts[0] . '.' . base64UrlEncode(json_encode($maliciousData)) . '.' . $parts[2];

echo "\n  Token manipulado (payload alterado, firma original):\n";
echo "  " . substr($tampered, 0, 80) . "...\n\n";
echo "  Verificando token manipulado:\n";
verifyJwt($tampered, $secret);

// ════════════════════════════════════════════════════════════════════════════
separator('🔬 EXPERIMENTO 5: Configuración segura de sesiones PHP');
// ════════════════════════════════════════════════════════════════════════════
// Las sesiones de PHP tienen muchas opciones de seguridad importantes.
// En producción, configura estas en php.ini o en la aplicación.

echo "\n  Configuración recomendada para sesiones seguras:\n\n";

$sessionConfig = [
    'session.cookie_httponly'  => '1',       // ✅ Evita acceso desde JavaScript (XSS)
    'session.cookie_secure'    => '1',       // ✅ Solo enviar cookie por HTTPS
    'session.cookie_samesite'  => 'Strict',  // ✅ Protección CSRF
    'session.use_strict_mode'  => '1',       // ✅ Rechaza IDs de sesión no iniciados
    'session.use_only_cookies' => '1',       // ✅ No pasar session_id en URL
    'session.gc_maxlifetime'   => '3600',    // ✅ 1 hora de inactividad
    'session.name'             => 'APPID',   // ✅ Nombre no revelador (no PHPSESSID)
];

foreach ($sessionConfig as $key => $value) {
    $current = ini_get($key);
    $status = ($current == $value) ? '✅' : '👉';
    echo "  {$status} {$key} = {$value}\n";
    if ($current != $value && $current !== false) {
        echo "     (actual: {$current})\n";
    }
}

echo "\n  Para aplicar en código:\n";
echo "  session_start([\n";
echo "      'cookie_httponly'  => true,\n";
echo "      'cookie_secure'    => true,\n";
echo "      'cookie_samesite'  => 'Strict',\n";
echo "      'use_strict_mode'  => true,\n";
echo "  ]);\n";
echo "\n  // Regenerar el ID de sesión tras login (previene Session Fixation)\n";
echo "  session_regenerate_id(delete_old_session: true);\n";

echo "\n\n  ✅ Lab 07 completado. JWT y sesiones seguras son fundamentales para la auth.\n\n";
