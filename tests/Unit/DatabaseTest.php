<?php

declare(strict_types=1);

namespace Lab\Tests\Unit;

use Lab\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Tests para Database Singleton.
 *
 * Verificamos las propiedades del patrón Singleton:
 * - Una única instancia
 * - No se puede clonar
 * - No se puede deserializar
 *
 * NOTA: No probamos la conexión real (eso es un test de integración).
 * Solo verificamos las restricciones del Singleton.
 */
class DatabaseTest extends TestCase
{
    public function testSingletonReturnsSameInstance(): void
    {
        // Usar reflection para inyectar una instancia en el singleton
        // sin necesitar una BD real — creamos una instancia con constructor omitido
        $reflection   = new \ReflectionClass(Database::class);
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true);

        // Guardar instancia original (puede ser null)
        $originalInstance = $instanceProp->getValue(null);

        try {
            // Crear instancia de Database sin ejecutar el constructor (no necesita BD)
            $fakeDb = $reflection->newInstanceWithoutConstructor();

            // Inyectar la instancia en el singleton
            $instanceProp->setValue(null, $fakeDb);

            $instance1 = Database::getInstance();
            $instance2 = Database::getInstance();

            $this->assertSame($instance1, $instance2, 'getInstance() debe devolver la misma instancia.');
            $this->assertSame($fakeDb, $instance1, 'getInstance() debe devolver la instancia inyectada.');
        } finally {
            // Restaurar el estado original
            $instanceProp->setValue(null, $originalInstance);
        }
    }

    public function testDatabaseCannotBeCloned(): void
    {
        $reflection  = new \ReflectionClass(Database::class);
        $cloneMethod = $reflection->getMethod('__clone');

        $this->assertTrue($cloneMethod->isPrivate(), '__clone debe ser privado para prevenir clonación.');
    }

    public function testDatabaseWakeupThrowsException(): void
    {
        $reflection = new \ReflectionClass(Database::class);

        // Crear instancia sin constructor
        $db = $reflection->newInstanceWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot unserialize a singleton.');

        $db->__wakeup();
    }
}
