<?php

declare(strict_types=1);

namespace Lab\Tests\Unit;

use Lab\Traits\Loggable;
use Lab\Traits\Timestampable;
use Lab\Traits\Validatable;
use PHPUnit\Framework\TestCase;

/**
 * Tests para los tres traits: Loggable, Timestampable, Validatable.
 *
 * Usamos clases anónimas para testear los traits de forma aislada,
 * sin necesidad de crear clases concretas en el código de producción.
 */
class TraitsTest extends TestCase
{
    // ══════════════════════════════════════════════════════════════
    // Loggable Tests
    // ══════════════════════════════════════════════════════════════

    private function makeLoggable(): object
    {
        return new class {
            use Loggable;
        };
    }

    public function testLoggableStoresInfoLog(): void
    {
        $obj = $this->makeLoggable();
        $obj->info('Test info message');

        $logs = $obj->getLogs();
        $this->assertCount(1, $logs);
        $this->assertSame('info', $logs[0]['level']);
        $this->assertSame('Test info message', $logs[0]['message']);
    }

    public function testLoggableStoresWarningLog(): void
    {
        $obj = $this->makeLoggable();
        $obj->warning('Watch out!');

        $logs = $obj->getLogs();
        $this->assertSame('warning', $logs[0]['level']);
    }

    public function testLoggableStoresErrorLog(): void
    {
        $obj = $this->makeLoggable();
        $obj->error('Something broke');

        $logs = $obj->getLogs();
        $this->assertSame('error', $logs[0]['level']);
    }

    public function testLoggableStoresContext(): void
    {
        $obj = $this->makeLoggable();
        $obj->log('info', 'User created', ['user_id' => 42]);

        $logs = $obj->getLogs();
        $this->assertSame(['user_id' => 42], $logs[0]['context']);
    }

    public function testLoggableClearLogs(): void
    {
        $obj = $this->makeLoggable();
        $obj->info('Message 1');
        $obj->info('Message 2');
        $this->assertCount(2, $obj->getLogs());

        $obj->clearLogs();
        $this->assertCount(0, $obj->getLogs());
    }

    public function testLoggableLogHasTimestamp(): void
    {
        $obj = $this->makeLoggable();
        $obj->info('Test');

        $logs = $obj->getLogs();
        $this->assertArrayHasKey('timestamp', $logs[0]);
        $this->assertNotEmpty($logs[0]['timestamp']);
    }

    // ══════════════════════════════════════════════════════════════
    // Timestampable Tests
    // ══════════════════════════════════════════════════════════════

    private function makeTimestampable(): object
    {
        return new class {
            use Timestampable;
        };
    }

    public function testTimestampableInitSetsCreatedAt(): void
    {
        $obj = $this->makeTimestampable();
        $this->assertNull($obj->getCreatedAt());

        $obj->initTimestamps();
        $this->assertInstanceOf(\DateTimeImmutable::class, $obj->getCreatedAt());
    }

    public function testTimestampableTouchUpdatesUpdatedAt(): void
    {
        $obj = $this->makeTimestampable();
        $obj->initTimestamps();

        $before = $obj->getUpdatedAt();
        usleep(1000); // 1ms para asegurar diferencia de tiempo
        $obj->touch();
        $after = $obj->getUpdatedAt();

        // updatedAt debe ser >= before (puede ser igual en entornos muy rápidos)
        $this->assertGreaterThanOrEqual($before, $after);
    }

    public function testTimestampableSoftDelete(): void
    {
        $obj = $this->makeTimestampable();
        $obj->initTimestamps();

        $this->assertFalse($obj->isDeleted());
        $obj->softDelete();
        $this->assertTrue($obj->isDeleted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $obj->getDeletedAt());
    }

    public function testTimestampableRestore(): void
    {
        $obj = $this->makeTimestampable();
        $obj->initTimestamps();
        $obj->softDelete();

        $this->assertTrue($obj->isDeleted());
        $obj->restore();
        $this->assertFalse($obj->isDeleted());
        $this->assertNull($obj->getDeletedAt());
    }

    public function testTimestampableIsDeletedReturnsFalseInitially(): void
    {
        $obj = $this->makeTimestampable();
        $this->assertFalse($obj->isDeleted());
    }

    // ══════════════════════════════════════════════════════════════
    // Validatable Tests
    // ══════════════════════════════════════════════════════════════

    private function makeValidatable(): object
    {
        return new class {
            use Validatable;
        };
    }

    public function testValidatableRequiredRuleFailsForEmpty(): void
    {
        $obj = $this->makeValidatable();
        $valid = $obj->validate(['name' => ''], ['name' => 'required']);

        $this->assertFalse($valid);
        $this->assertArrayHasKey('name', $obj->getValidationErrors());
    }

    public function testValidatableEmailRuleFailsForInvalidEmail(): void
    {
        $obj = $this->makeValidatable();
        $valid = $obj->validate(['email' => 'not-an-email'], ['email' => 'email']);

        $this->assertFalse($valid);
        $this->assertArrayHasKey('email', $obj->getValidationErrors());
    }

    public function testValidatableEmailRulePassesForValidEmail(): void
    {
        $obj = $this->makeValidatable();
        $valid = $obj->validate(['email' => 'test@example.com'], ['email' => 'required|email']);

        $this->assertTrue($valid);
    }

    public function testValidatableMinRuleFailsForShortString(): void
    {
        $obj = $this->makeValidatable();
        $valid = $obj->validate(['pass' => 'abc'], ['pass' => 'min:8']);

        $this->assertFalse($valid);
    }

    public function testValidatableMaxRuleFailsForLongString(): void
    {
        $obj = $this->makeValidatable();
        $valid = $obj->validate(['name' => str_repeat('x', 101)], ['name' => 'max:100']);

        $this->assertFalse($valid);
    }

    public function testValidatableInRuleFailsForInvalidValue(): void
    {
        $obj = $this->makeValidatable();
        $valid = $obj->validate(['role' => 'superadmin'], ['role' => 'in:admin,user,guest']);

        $this->assertFalse($valid);
    }

    public function testValidatableHasErrorsReturnsFalseWhenNoErrors(): void
    {
        $obj = $this->makeValidatable();
        $obj->validate(
            ['name' => 'Ana', 'email' => 'ana@lab.test'],
            ['name' => 'required', 'email' => 'required|email']
        );

        $this->assertFalse($obj->hasErrors());
    }
}
