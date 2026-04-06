<?php

declare(strict_types=1);

namespace Lab\Core;

/**
 * Singleton PDO Database Connection.
 *
 * Implementa el patrón Singleton para garantizar una única instancia de la
 * conexión a base de datos durante el ciclo de vida de la aplicación.
 *
 * Patrón Singleton: garantiza que una clase tenga solo una instancia y
 * proporciona un punto de acceso global a ella.
 *
 * ❌ NO HAGAS ESTO: crear new PDO() en cada clase que necesite BD
 * ✅ MEJOR ASÍ: usar Database::getInstance()->getConnection()
 */
final class Database implements DatabaseInterface
{
    /** @var Database|null La única instancia de la clase */
    private static ?Database $instance = null;

    /** @var \PDO La conexión PDO */
    private \PDO $connection;

    /**
     * Constructor privado — impide instanciación directa con `new Database()`.
     * Carga la configuración y crea la conexión PDO.
     *
     * @throws \PDOException Si la conexión falla
     */
    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $this->connection = new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
    }

    /**
     * Punto de acceso global a la instancia única.
     *
     * Si no existe instancia, la crea. Si ya existe, devuelve la misma.
     * Esto garantiza una sola conexión a BD durante todo el request.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Devuelve la conexión PDO subyacente.
     *
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * Ejecuta una query preparada con parámetros opcionales.
     *
     * Usar prepared statements SIEMPRE que haya valores dinámicos.
     * Los prepared statements son la defensa principal contra SQL Injection.
     *
     * @param string $sql    Query SQL con placeholders (? o :nombre)
     * @param array  $params Valores para los placeholders
     * @return \PDOStatement
     * @throws \PDOException Si la query falla
     *
     * @example
     *   $stmt = $db->query('SELECT * FROM users WHERE email = ?', [$email]);
     *   $user = $stmt->fetch();
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Ejecuta un callable dentro de una transacción.
     *
     * Si el callable lanza una excepción, se hace ROLLBACK automático.
     * Si el callable termina sin excepción, se hace COMMIT.
     *
     * ACID: las transacciones garantizan Atomicidad, Consistencia,
     * Aislamiento y Durabilidad.
     *
     * @param callable $cb Función con las operaciones a ejecutar
     * @return mixed El valor de retorno del callable
     * @throws \Throwable Si el callable lanza una excepción (después del ROLLBACK)
     *
     * @example
     *   $db->transaction(function() use ($db, $data) {
     *       $db->query('INSERT INTO orders ...', $data);
     *       $db->query('UPDATE stock ...', $data);
     *   });
     */
    public function transaction(callable $cb): mixed
    {
        $this->connection->beginTransaction();
        try {
            $result = $cb($this->connection);
            $this->connection->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Devuelve el ID del último registro insertado.
     *
     * @return string El ID del último INSERT
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Impide la clonación de la instancia Singleton.
     *
     * ❌ NO HAGAS ESTO: $db2 = clone $db; // rompe el Singleton
     */
    private function __clone() {}

    /**
     * Impide la deserialización de la instancia Singleton.
     *
     * @throws \Exception Siempre lanza excepción si se intenta deserializar
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize a singleton.');
    }
}
