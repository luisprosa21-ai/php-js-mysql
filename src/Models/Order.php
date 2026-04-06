<?php

declare(strict_types=1);

namespace Lab\Models;

use Lab\Traits\Timestampable;

/**
 * Modelo de Pedido (Order).
 *
 * Representa un pedido de un usuario con sus items y estado.
 * Los pedidos tienen una máquina de estados simple:
 *   pending → confirmed → shipped → delivered
 *   pending → cancelled
 */
class Order implements \JsonSerializable
{
    use Timestampable;

    /** Estados válidos del pedido */
    private const VALID_STATUSES = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    /** @var int|null ID del pedido en la BD */
    public ?int $id = null;

    /** @var int ID del usuario que realizó el pedido */
    private int $userId = 0;

    /** @var string Estado actual del pedido */
    private string $status = 'pending';

    /** @var array<int, array{product_id: int, quantity: int, price: float}> Items del pedido */
    private array $items = [];

    /** @var float Total calculado del pedido */
    private float $total = 0.0;

    /**
     * Añade un item al pedido y recalcula el total.
     *
     * @param array{product_id: int, quantity: int, price: float} $item
     * @throws \InvalidArgumentException Si el item no tiene los campos necesarios
     */
    public function addItem(array $item): void
    {
        if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
            throw new \InvalidArgumentException('El item debe tener product_id, quantity y price.');
        }
        if ((int)$item['quantity'] <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }
        if ((float)$item['price'] < 0) {
            throw new \InvalidArgumentException('El precio no puede ser negativo.');
        }

        $this->items[] = [
            'product_id' => (int)$item['product_id'],
            'quantity'   => (int)$item['quantity'],
            'price'      => round((float)$item['price'], 2),
        ];

        $this->calculateTotal();
        $this->touch();
    }

    /**
     * Calcula y actualiza el total del pedido.
     *
     * Total = suma de (precio × cantidad) de cada item.
     *
     * @return float El total calculado
     */
    public function calculateTotal(): float
    {
        $this->total = array_reduce(
            $this->items,
            fn(float $carry, array $item) => $carry + ($item['price'] * $item['quantity']),
            0.0
        );
        return round($this->total, 2);
    }

    /**
     * Determina si el pedido puede ser cancelado.
     *
     * Solo se puede cancelar si está en estado 'pending' o 'confirmed'.
     * Un pedido ya enviado o entregado no puede cancelarse.
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    /**
     * Crea un Order desde un array asociativo.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $order = new self();
        $order->id     = isset($data['id']) ? (int)$data['id'] : null;
        $order->userId = (int)($data['user_id'] ?? $data['userId'] ?? 0);
        $order->status = $data['status'] ?? 'pending';
        $order->items  = $data['items'] ?? [];
        $order->total  = (float)($data['total'] ?? 0.0);

        if (isset($data['created_at']) && $data['created_at']) {
            $order->createdAt = new \DateTimeImmutable($data['created_at']);
        }
        if (isset($data['updated_at']) && $data['updated_at']) {
            $order->updatedAt = new \DateTimeImmutable($data['updated_at']);
        }

        return $order;
    }

    /**
     * Convierte el pedido a array para persistencia.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->userId,
            'status'     => $this->status,
            'items'      => $this->items,
            'total'      => $this->total,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Serialización JSON del pedido.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->userId,
            'status'       => $this->status,
            'items'        => $this->items,
            'total'        => $this->total,
            'can_cancel'   => $this->canBeCancelled(),
            'created_at'   => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at'   => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getUserId(): int { return $this->userId; }
    public function getStatus(): string { return $this->status; }
    public function getItems(): array { return $this->items; }
    public function getTotal(): float { return $this->total; }

    // ── Setters ───────────────────────────────────────────────────────────────

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @throws \InvalidArgumentException Si el estado no es válido
     */
    public function setStatus(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                "Estado inválido: {$status}. Válidos: " . implode(', ', self::VALID_STATUSES)
            );
        }
        $this->status = $status;
        $this->touch();
    }
}
