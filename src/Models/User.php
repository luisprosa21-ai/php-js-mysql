<?php

declare(strict_types=1);

namespace Lab\Models;

use Lab\Traits\Timestampable;
use Lab\Traits\Validatable;

/**
 * Modelo de Usuario.
 *
 * Representa un usuario del sistema con sus datos y comportamientos asociados.
 * Implementa JsonSerializable para permitir la serialización directa a JSON
 * sin exponer datos sensibles (como el hash de contraseña).
 *
 * Usa los traits Timestampable y Validatable para añadir funcionalidades
 * sin duplicar código.
 */
class User implements \JsonSerializable
{
    use Timestampable;
    use Validatable;

    /** @var int|null ID autoincremental de la BD (null si no persiste aún) */
    public ?int $id = null;

    /** @var string Nombre completo del usuario */
    private string $name = '';

    /** @var string Email único del usuario */
    private string $email = '';

    /** @var string Hash Argon2ID de la contraseña (nunca en texto plano) */
    private string $passwordHash = '';

    /** @var string Rol del usuario: admin, user, guest */
    private string $role = 'user';

    /**
     * Crea una instancia de User desde un array asociativo.
     *
     * Factory method: alternativa más expresiva que un constructor con muchos parámetros.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $user = new self();
        $user->id           = isset($data['id']) ? (int)$data['id'] : null;
        $user->name         = $data['name'] ?? '';
        $user->email        = $data['email'] ?? '';
        $user->passwordHash = $data['password_hash'] ?? $data['passwordHash'] ?? '';
        $user->role         = $data['role'] ?? 'user';

        if (isset($data['created_at']) && $data['created_at']) {
            $user->createdAt = new \DateTimeImmutable($data['created_at']);
        }
        if (isset($data['updated_at']) && $data['updated_at']) {
            $user->updatedAt = new \DateTimeImmutable($data['updated_at']);
        }
        if (isset($data['deleted_at']) && $data['deleted_at']) {
            $user->deletedAt = new \DateTimeImmutable($data['deleted_at']);
        }

        return $user;
    }

    /**
     * Convierte el usuario a array (útil para insertar/actualizar en BD).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'password_hash' => $this->passwordHash,
            'role'          => $this->role,
            'created_at'    => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at'    => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at'    => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Implementación de JsonSerializable::jsonSerialize().
     *
     * ❌ NO HAGAS ESTO: devolver passwordHash en la respuesta JSON
     * ✅ MEJOR ASÍ: omitir datos sensibles en la serialización pública
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }

    // ── Setters con validación ────────────────────────────────────────────────

    /**
     * @throws \InvalidArgumentException Si el nombre está vacío
     */
    public function setName(string $name): void
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('El nombre no puede estar vacío.');
        }
        $this->name = trim($name);
        $this->touch();
    }

    /**
     * @throws \InvalidArgumentException Si el email no es válido
     */
    public function setEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Email inválido: {$email}");
        }
        $this->email = strtolower(trim($email));
        $this->touch();
    }

    /**
     * Establece el hash de contraseña (ya hasheado con Argon2ID).
     *
     * ❌ NO HAGAS ESTO: setPasswordHash(md5($password))
     * ✅ MEJOR ASÍ: setPasswordHash(password_hash($pwd, PASSWORD_ARGON2ID))
     */
    public function setPasswordHash(string $hash): void
    {
        $this->passwordHash = $hash;
        $this->touch();
    }

    /**
     * @throws \InvalidArgumentException Si el rol no es válido
     */
    public function setRole(string $role): void
    {
        $valid = ['admin', 'user', 'guest'];
        if (!in_array($role, $valid, true)) {
            throw new \InvalidArgumentException("Rol inválido: {$role}. Válidos: " . implode(', ', $valid));
        }
        $this->role = $role;
        $this->touch();
    }

    /**
     * Valida los datos del usuario con las reglas del modelo.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function validateUserData(array $data): bool
    {
        return $this->validate($data, [
            'name'     => 'required|string|min:2|max:100',
            'email'    => 'required|email|max:150',
            'password' => 'required|min:8',
            'role'     => 'in:admin,user,guest',
        ]);
    }
}
