<?php

declare(strict_types=1);

namespace Lab\Traits;

/**
 * Trait Timestampable — gestiona fechas de creación, actualización y borrado suave.
 *
 * El "soft delete" (borrado suave) es un patrón en el que los registros no se
 * eliminan físicamente de la base de datos, sino que se marcan con una fecha
 * de borrado. Esto permite recuperarlos posteriormente y mantener un historial.
 *
 * Uso:
 *   class MiModelo {
 *       use Timestampable;
 *   }
 *   $obj->initTimestamps();
 *   $obj->touch();          // actualiza updatedAt
 *   $obj->softDelete();     // marca como borrado
 *   $obj->isDeleted();      // true
 *   $obj->restore();        // elimina la marca de borrado
 */
trait Timestampable
{
    /** @var \DateTimeImmutable|null Fecha de creación del registro */
    private ?\DateTimeImmutable $createdAt = null;

    /** @var \DateTimeImmutable|null Fecha de la última actualización */
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var \DateTimeImmutable|null Fecha de borrado suave (null = no borrado) */
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * Inicializa los timestamps al crear el objeto.
     * Debe llamarse en el constructor o en fromArray().
     */
    public function initTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Actualiza el timestamp de última modificación al momento actual.
     *
     * Llama a este método cada vez que se modifique el estado del objeto.
     */
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Marca el registro como borrado (soft delete).
     *
     * ✅ RESULTADO: El registro permanece en la BD pero isDeleted() devuelve true.
     * Para excluir registros borrados en tus queries: WHERE deleted_at IS NULL
     */
    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->touch();
    }

    /**
     * Restaura un registro borrado suavemente.
     *
     * ✅ RESULTADO: deletedAt vuelve a null, isDeleted() devuelve false.
     */
    public function restore(): void
    {
        $this->deletedAt = null;
        $this->touch();
    }

    /**
     * Indica si el registro ha sido borrado suavemente.
     *
     * @return bool true si deletedAt tiene valor, false si no
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Devuelve la fecha de creación.
     *
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Devuelve la fecha de última actualización.
     *
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Devuelve la fecha de borrado suave, o null si no está borrado.
     *
     * @return \DateTimeImmutable|null
     */
    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
