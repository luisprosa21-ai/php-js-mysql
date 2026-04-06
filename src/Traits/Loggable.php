<?php

declare(strict_types=1);

namespace Lab\Traits;

/**
 * Trait Loggable — añade capacidades de logging a cualquier clase.
 *
 * Un trait en PHP es un mecanismo de reutilización de código para lenguajes
 * de herencia simple. Permite "mezclar" métodos en múltiples clases sin
 * herencia.
 *
 * Uso:
 *   class MiClase {
 *       use Loggable;
 *   }
 *   $obj->log('info', 'Mensaje', ['clave' => 'valor']);
 *   $obj->info('Todo bien');
 */
trait Loggable
{
    /** @var array<int, array{level: string, message: string, context: array, timestamp: string}> */
    private array $logs = [];

    /**
     * Registra un mensaje con nivel y contexto opcional.
     *
     * @param string $level   Nivel del log (debug, info, warning, error, critical)
     * @param string $message Mensaje descriptivo
     * @param array  $context Datos adicionales de contexto
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $this->logs[] = [
            'level'     => strtolower($level),
            'message'   => $message,
            'context'   => $context,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Registra un mensaje de nivel INFO.
     *
     * @param string $message
     * @param array  $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Registra un mensaje de nivel WARNING.
     *
     * @param string $message
     * @param array  $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Registra un mensaje de nivel ERROR.
     *
     * @param string $message
     * @param array  $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Devuelve todos los logs registrados.
     *
     * @return array<int, array{level: string, message: string, context: array, timestamp: string}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Elimina todos los logs registrados.
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }
}
