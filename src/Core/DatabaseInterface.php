<?php

declare(strict_types=1);

namespace Lab\Core;

/**
 * Contrato de la conexión a base de datos.
 *
 * Usar una interfaz permite:
 * - Sustituir la implementación real por un mock en los tests
 * - Desacoplar los servicios de la implementación concreta (PDO)
 * - Facilitar el testeo sin necesitar una BD real
 *
 * ✅ MEJOR ASÍ: los servicios dependen de esta interfaz, no de Database directamente.
 * ❌ NO HAGAS ESTO: inyectar Database (clase concreta) en los constructores de tus servicios.
 */
interface DatabaseInterface
{
    /**
     * Ejecuta una query preparada con parámetros opcionales.
     *
     * @param string  $sql    Query SQL con placeholders (? o :nombre)
     * @param array   $params Valores para los placeholders
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement;

    /**
     * Ejecuta un callable dentro de una transacción.
     *
     * Si el callable lanza una excepción, hace ROLLBACK automático.
     * Si termina sin excepción, hace COMMIT.
     *
     * @param callable $cb Función con las operaciones a ejecutar
     * @return mixed El valor de retorno del callable
     */
    public function transaction(callable $cb): mixed;

    /**
     * Devuelve el ID del último registro insertado.
     *
     * @return string
     */
    public function lastInsertId(): string;

    /**
     * Devuelve la conexión PDO subyacente.
     *
     * @return \PDO
     */
    public function getConnection(): \PDO;
}
