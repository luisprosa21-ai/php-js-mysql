<?php

declare(strict_types=1);

namespace Lab\Middleware;

use Lab\Services\AuthService;

/**
 * Middleware de autenticación JWT.
 *
 * Verifica que la petición incluya un Bearer token válido en el header
 * Authorization. Si el token es inválido o falta, devuelve 401.
 *
 * Patrón Middleware: permite encadenar procesadores de request/response
 * de forma limpia y desacoplada.
 */
class AuthMiddleware
{
    /**
     * @param AuthService $auth Servicio de autenticación para verificar tokens
     */
    public function __construct(private AuthService $auth) {}

    /**
     * Procesa la petición verificando el JWT.
     *
     * @param array    $request Array con datos de la petición (headers, body, etc.)
     * @param callable $next    El siguiente manejador en la cadena
     * @return array Respuesta con status y datos
     */
    public function handle(array $request, callable $next): array
    {
        $authHeader = $request['headers']['Authorization']
            ?? $request['headers']['authorization']
            ?? '';

        // El header debe tener el formato: "Bearer <token>"
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return [
                'status' => 401,
                'error'  => 'Token de autenticación requerido.',
            ];
        }

        $token = substr($authHeader, 7);

        try {
            $claims = $this->auth->verifyToken($token);
            // Añadir los claims verificados al request para uso posterior
            $request['auth'] = $claims;
        } catch (\RuntimeException $e) {
            return [
                'status' => 401,
                'error'  => $e->getMessage(),
            ];
        }

        return $next($request);
    }
}
