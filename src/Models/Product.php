<?php

declare(strict_types=1);

namespace Lab\Models;

use Lab\Traits\Timestampable;

/**
 * Modelo de Producto.
 *
 * Representa un producto del catálogo con su precio, stock y categoría.
 */
class Product implements \JsonSerializable
{
    use Timestampable;

    /** @var int|null ID del producto en la BD */
    public ?int $id = null;

    /** @var string Nombre del producto */
    private string $name = '';

    /** @var float Precio en EUR */
    private float $price = 0.0;

    /** @var int Unidades disponibles en stock */
    private int $stock = 0;

    /** @var int ID de la categoría a la que pertenece */
    private int $categoryId = 0;

    /**
     * Crea una instancia de Product desde un array asociativo.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $product = new self();
        $product->id         = isset($data['id']) ? (int)$data['id'] : null;
        $product->name       = $data['name'] ?? '';
        $product->price      = (float)($data['price'] ?? 0.0);
        $product->stock      = (int)($data['stock'] ?? 0);
        $product->categoryId = (int)($data['category_id'] ?? $data['categoryId'] ?? 0);

        if (isset($data['created_at']) && $data['created_at']) {
            $product->createdAt = new \DateTimeImmutable($data['created_at']);
        }
        if (isset($data['updated_at']) && $data['updated_at']) {
            $product->updatedAt = new \DateTimeImmutable($data['updated_at']);
        }

        return $product;
    }

    /**
     * Indica si el producto tiene stock disponible.
     *
     * @return bool
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Calcula el precio con un descuento aplicado.
     *
     * @param float $pct Porcentaje de descuento (0.0 a 100.0)
     * @return float Precio con descuento, redondeado a 2 decimales
     * @throws \InvalidArgumentException Si el porcentaje no está en [0, 100]
     */
    public function applyDiscount(float $pct): float
    {
        if ($pct < 0.0 || $pct > 100.0) {
            throw new \InvalidArgumentException("Porcentaje inválido: {$pct}. Debe estar entre 0 y 100.");
        }
        return round($this->price * (1 - $pct / 100), 2);
    }

    /**
     * Convierte el producto a array para persistencia.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'price'       => $this->price,
            'stock'       => $this->stock,
            'category_id' => $this->categoryId,
            'created_at'  => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at'  => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Serialización JSON del producto.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'price'       => $this->price,
            'stock'       => $this->stock,
            'in_stock'    => $this->isInStock(),
            'category_id' => $this->categoryId,
            'created_at'  => $this->createdAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
    public function getStock(): int { return $this->stock; }
    public function getCategoryId(): int { return $this->categoryId; }

    // ── Setters ───────────────────────────────────────────────────────────────

    public function setName(string $name): void
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('El nombre del producto no puede estar vacío.');
        }
        $this->name = trim($name);
        $this->touch();
    }

    public function setPrice(float $price): void
    {
        if ($price < 0) {
            throw new \InvalidArgumentException('El precio no puede ser negativo.');
        }
        $this->price = round($price, 2);
        $this->touch();
    }

    public function setStock(int $stock): void
    {
        if ($stock < 0) {
            throw new \InvalidArgumentException('El stock no puede ser negativo.');
        }
        $this->stock = $stock;
        $this->touch();
    }

    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->touch();
    }
}
