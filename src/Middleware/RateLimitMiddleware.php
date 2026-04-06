<?php

declare(strict_types=1);

namespace Lab\Middleware;

/**
 * Middleware de rate limiting.
 *
 * Limita el número de peticiones por IP en una ventana de tiempo.
 * Implementación simple en memoria (para producción usar Redis/Memcached).
 *
 * HTTP 429 Too Many Requests es el código estándar cuando se supera el límite.
 * El header Retry-After indica cuántos segundos esperar.
 */
class RateLimitMiddleware
{
    // 👉 MODIFICA: aumenta o reduce el límite de peticiones
    private int $maxRequests = 60;

    // 👉 MODIFICA: cambia la ventana de tiempo en segundos
    private int $windowSeconds = 60;

    /** @var array<string, array{count: int, reset_at: int}> Contador por IP */
    private static array $store = [];

    /**
     * @param int $maxRequests   Peticiones máximas por ventana (👉 MODIFICA)
     * @param int $windowSeconds Duración de la ventana en segundos (👉 MODIFICA)
     */
    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests   = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Verifica y registra la petición del cliente.
     *
     * @param array    $request Array con datos de la petición (incluyendo 'ip')
     * @param callable $next    El siguiente manejador
     * @return array Respuesta con status y datos
     */
    public function handle(array $request, callable $next): array
    {
        $ip  = $request['ip'] ?? '127.0.0.1';
        $now = time();

        // Inicializar o resetear la ventana si expiró
        if (!isset(self::$store[$ip]) || self::$store[$ip]['reset_at'] <= $now) {
            self::$store[$ip] = [
                'count'    => 0,
                'reset_at' => $now + $this->windowSeconds,
            ];
        }

        self::$store[$ip]['count']++;
        $remaining = $this->maxRequests - self::$store[$ip]['count'];

        // Añadir headers de rate limit a la respuesta (siempre)
        $rateLimitHeaders = [
            'X-RateLimit-Limit'     => $this->maxRequests,
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset'     => self::$store[$ip]['reset_at'],
        ];

        if (self::$store[$ip]['count'] > $this->maxRequests) {
            return [
                'status'  => 429,
                'error'   => 'Demasiadas peticiones. Inténtalo más tarde.',
                'headers' => array_merge($rateLimitHeaders, [
                    'Retry-After' => self::$store[$ip]['reset_at'] - $now,
                ]),
            ];
        }

        $response = $next($request);
        $response['headers'] = array_merge($response['headers'] ?? [], $rateLimitHeaders);

        return $response;
    }
}
