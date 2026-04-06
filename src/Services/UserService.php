<?php

declare(strict_types=1);

namespace Lab\Services;

use Lab\Core\DatabaseInterface;
use Lab\Models\User;
use Lab\Traits\Loggable;

/**
 * Servicio de gestión de usuarios.
 *
 * Contiene la lógica de negocio relacionada con usuarios: CRUD, paginación,
 * hashing de contraseñas y búsqueda. Usa el trait Loggable para auditoría.
 */
class UserService
{
    use Loggable;

    /**
     * @param DatabaseInterface $db Instancia de la base de datos (inyectada)
     */
    public function __construct(private DatabaseInterface $db) {}

    /**
     * Crea un nuevo usuario en la base de datos.
     *
     * @param array<string, mixed> $data Datos del usuario (name, email, password, role)
     * @return User El usuario creado con su ID asignado
     * @throws \InvalidArgumentException Si los datos no son válidos
     */
    public function create(array $data): User
    {
        $user = new User();

        if (!$user->validateUserData($data)) {
            $errors = implode(', ', array_merge(...array_values($user->getValidationErrors())));
            throw new \InvalidArgumentException("Datos inválidos: {$errors}");
        }

        $user->setName($data['name']);
        $user->setEmail($data['email']);
        $user->setPasswordHash($this->hashPassword($data['password']));
        $user->setRole($data['role'] ?? 'user');
        $user->initTimestamps();

        $this->db->query(
            'INSERT INTO users (name, email, password_hash, role, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $user->getName(),
                $user->getEmail(),
                $user->getPasswordHash(),
                $user->getRole(),
                $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]
        );

        $user->id = (int)$this->db->lastInsertId();
        $this->info("Usuario creado: {$user->getEmail()}", ['id' => $user->id]);

        return $user;
    }

    /**
     * Busca un usuario por su ID.
     *
     * @param int $id
     * @return User|null null si no existe
     */
    public function findById(int $id): ?User
    {
        $row = $this->db->query('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id])->fetch();
        return $row ? User::fromArray($row) : null;
    }

    /**
     * Busca un usuario por su email.
     *
     * @param string $email
     * @return User|null null si no existe
     */
    public function findByEmail(string $email): ?User
    {
        $row = $this->db->query(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL',
            [strtolower(trim($email))]
        )->fetch();

        return $row ? User::fromArray($row) : null;
    }

    /**
     * Actualiza los datos de un usuario existente.
     *
     * @param int                  $id   ID del usuario
     * @param array<string, mixed> $data Campos a actualizar
     * @return User El usuario actualizado
     * @throws \RuntimeException Si el usuario no existe
     */
    public function update(int $id, array $data): User
    {
        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException("Usuario con ID {$id} no encontrado.");
        }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPasswordHash($this->hashPassword($data['password']));
        }
        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }

        $this->db->query(
            'UPDATE users SET name = ?, email = ?, password_hash = ?, role = ?, updated_at = ? WHERE id = ?',
            [
                $user->getName(),
                $user->getEmail(),
                $user->getPasswordHash(),
                $user->getRole(),
                $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
                $user->id,
            ]
        );

        $this->info("Usuario actualizado: ID {$id}");
        return $user;
    }

    /**
     * Elimina (soft delete) un usuario por su ID.
     *
     * @param int $id
     * @return bool true si se eliminó, false si no existía
     */
    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        if ($user === null) {
            return false;
        }

        $user->softDelete();
        $this->db->query(
            'UPDATE users SET deleted_at = ?, updated_at = ? WHERE id = ?',
            [
                $user->getDeletedAt()?->format('Y-m-d H:i:s'),
                $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
                $id,
            ]
        );

        $this->info("Usuario eliminado (soft delete): ID {$id}");
        return true;
    }

    /**
     * Devuelve una página de usuarios.
     *
     * @param int $page    Número de página (empieza en 1)
     * @param int $perPage Usuarios por página
     * @return array{data: User[], total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        $total = (int)$this->db->query(
            'SELECT COUNT(*) as cnt FROM users WHERE deleted_at IS NULL'
        )->fetchColumn();

        $rows = $this->db->query(
            'SELECT * FROM users WHERE deleted_at IS NULL ORDER BY id DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        )->fetchAll();

        return [
            'data'      => array_map(fn($row) => User::fromArray($row), $rows),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Genera un hash seguro de la contraseña usando Argon2ID.
     *
     * Argon2ID es el algoritmo recomendado para hashing de contraseñas en 2024.
     * Es resistente a ataques de GPU y side-channel.
     *
     * ❌ NO HAGAS ESTO: md5($password), sha1($password)
     * ✅ MEJOR ASÍ: password_hash($password, PASSWORD_ARGON2ID)
     *
     * @param string $password Contraseña en texto plano
     * @return string Hash Argon2ID
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,  // 64 MB — 👉 MODIFICA: más memoria = más seguro pero más lento
            'time_cost'   => 4,      // Iteraciones — 👉 MODIFICA: más = más seguro pero más lento
            'threads'     => 1,
        ]);
    }

    /**
     * Verifica si una contraseña coincide con su hash.
     *
     * @param string $password Contraseña en texto plano a verificar
     * @param string $hash     Hash almacenado en la BD
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
