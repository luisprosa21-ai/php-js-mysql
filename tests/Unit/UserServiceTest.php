<?php

declare(strict_types=1);

namespace Lab\Tests\Unit;

use Lab\Core\DatabaseInterface;
use Lab\Models\User;
use Lab\Services\UserService;
use PHPUnit\Framework\TestCase;

/**
 * Tests para UserService usando mocks de Database.
 *
 * Principio: los tests unitarios no necesitan BD real.
 * Usamos createMock() para simular el comportamiento de Database.
 */
class UserServiceTest extends TestCase
{
    private UserService $service;
    private DatabaseInterface $dbMock;

    protected function setUp(): void
    {
        // Crear mock de DatabaseInterface (no necesita conexión real)
        $this->dbMock  = $this->createMock(DatabaseInterface::class);
        $this->service = new UserService($this->dbMock);
    }

    // ── hashPassword ─────────────────────────────────────────────────────────

    public function testHashPasswordReturnsArgon2idHash(): void
    {
        $hash = $this->service->hashPassword('password123');

        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    public function testHashPasswordProducesDifferentHashesForSameInput(): void
    {
        $hash1 = $this->service->hashPassword('password123');
        $hash2 = $this->service->hashPassword('password123');

        // Argon2ID usa salt aleatorio → hashes distintos
        $this->assertNotEquals($hash1, $hash2);
    }

    // ── verifyPassword ────────────────────────────────────────────────────────

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        $hash = $this->service->hashPassword('mySecret123');

        $this->assertTrue($this->service->verifyPassword('mySecret123', $hash));
    }

    public function testVerifyPasswordReturnsFalseForWrongPassword(): void
    {
        $hash = $this->service->hashPassword('correct');

        $this->assertFalse($this->service->verifyPassword('wrong', $hash));
    }

    // ── findById ─────────────────────────────────────────────────────────────

    public function testFindByIdReturnsUserWhenFound(): void
    {
        $userData = [
            'id'            => 1,
            'name'          => 'Ana García',
            'email'         => 'ana@lab.test',
            'password_hash' => '$argon2id$test',
            'role'          => 'user',
            'created_at'    => null,
            'updated_at'    => null,
            'deleted_at'    => null,
        ];

        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetch')->willReturn($userData);

        $this->dbMock
            ->method('query')
            ->willReturn($stmtMock);

        $user = $this->service->findById(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->id);
        $this->assertSame('Ana García', $user->getName());
        $this->assertSame('ana@lab.test', $user->getEmail());
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(false);

        $this->dbMock
            ->method('query')
            ->willReturn($stmtMock);

        $user = $this->service->findById(999);

        $this->assertNull($user);
    }

    // ── findByEmail ───────────────────────────────────────────────────────────

    public function testFindByEmailReturnsUser(): void
    {
        $row = [
            'id' => 2, 'name' => 'Carlos', 'email' => 'carlos@lab.test',
            'password_hash' => '$argon2id$hash', 'role' => 'admin',
            'created_at' => null, 'updated_at' => null, 'deleted_at' => null,
        ];

        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetch')->willReturn($row);

        $this->dbMock->method('query')->willReturn($stmtMock);

        $user = $this->service->findByEmail('carlos@lab.test');

        $this->assertNotNull($user);
        $this->assertSame('admin', $user->getRole());
    }

    // ── delete ───────────────────────────────────────────────────────────────

    public function testDeleteReturnsFalseForNonExistentUser(): void
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetch')->willReturn(false);

        $this->dbMock->method('query')->willReturn($stmtMock);

        $result = $this->service->delete(999);

        $this->assertFalse($result);
    }

    public function testDeleteReturnsTrueForExistingUser(): void
    {
        $row = [
            'id' => 1, 'name' => 'Ana', 'email' => 'ana@lab.test',
            'password_hash' => '$argon2id$hash', 'role' => 'user',
            'created_at' => null, 'updated_at' => null, 'deleted_at' => null,
        ];

        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetch')->willReturn($row);

        $this->dbMock->method('query')->willReturn($stmtMock);

        $result = $this->service->delete(1);

        $this->assertTrue($result);
    }

    // ── create ───────────────────────────────────────────────────────────────

    public function testCreateThrowsForInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->create([
            'name'     => '', // inválido
            'email'    => 'not-valid-email',
            'password' => '123', // muy corto
        ]);
    }

    // ── paginate ─────────────────────────────────────────────────────────────

    public function testPaginateReturnsStructuredResult(): void
    {
        $countStmt = $this->createMock(\PDOStatement::class);
        $countStmt->method('fetchColumn')->willReturn(5);

        $rowsStmt = $this->createMock(\PDOStatement::class);
        $rowsStmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'Ana', 'email' => 'ana@lab.test',
             'password_hash' => '$argon2id$x', 'role' => 'user',
             'created_at' => null, 'updated_at' => null, 'deleted_at' => null],
        ]);

        $this->dbMock
            ->method('query')
            ->willReturnOnConsecutiveCalls($countStmt, $rowsStmt);

        $result = $this->service->paginate(1, 15);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('last_page', $result);
        $this->assertSame(5, $result['total']);
        $this->assertSame(1, $result['page']);
    }
}
