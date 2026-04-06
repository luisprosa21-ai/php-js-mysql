<?php

declare(strict_types=1);

namespace Lab\Services;

use Lab\Core\DatabaseInterface;
use Lab\Models\Order;
use Lab\Traits\Loggable;

/**
 * Servicio de gestión de pedidos.
 *
 * Maneja la creación, consulta y cancelación de pedidos.
 * Las operaciones de creación usan transacciones para garantizar
 * la integridad de los datos (ACID).
 */
class OrderService
{
    use Loggable;

    /**
     * @param DatabaseInterface $db Instancia de la base de datos
     */
    public function __construct(private DatabaseInterface $db) {}

    /**
     * Crea un nuevo pedido con sus items dentro de una transacción.
     *
     * @param int   $userId ID del usuario que realiza el pedido
     * @param array $items  Array de items [{product_id, quantity, price}, ...]
     * @return Order El pedido creado
     * @throws \InvalidArgumentException Si los items están vacíos
     * @throws \RuntimeException         Si hay error en la BD (se hace ROLLBACK)
     */
    public function create(int $userId, array $items): Order
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('El pedido debe tener al menos un item.');
        }

        $order = new Order();
        $order->setUserId($userId);
        $order->initTimestamps();

        foreach ($items as $item) {
            $order->addItem($item);
        }

        $this->db->transaction(function () use ($order) {
            // Insertar el pedido principal
            $this->db->query(
                'INSERT INTO orders (user_id, status, total, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [
                    $order->getUserId(),
                    $order->getStatus(),
                    $order->calculateTotal(),
                    $order->getCreatedAt()?->format('Y-m-d H:i:s'),
                    $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
                ]
            );
            $order->id = (int)$this->db->lastInsertId();

            // Insertar cada item del pedido
            foreach ($order->getItems() as $item) {
                $this->db->query(
                    'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)',
                    [$order->id, $item['product_id'], $item['quantity'], $item['price']]
                );
            }
        });

        $this->info("Pedido creado: ID {$order->id}", ['user_id' => $userId, 'total' => $order->getTotal()]);
        return $order;
    }

    /**
     * Busca un pedido por su ID, incluyendo sus items.
     *
     * @param int $id ID del pedido
     * @return Order|null null si no existe
     */
    public function findById(int $id): ?Order
    {
        $row = $this->db->query('SELECT * FROM orders WHERE id = ?', [$id])->fetch();
        if (!$row) {
            return null;
        }

        $items = $this->db->query(
            'SELECT * FROM order_items WHERE order_id = ?',
            [$id]
        )->fetchAll();

        $row['items'] = array_map(fn($item) => [
            'product_id' => (int)$item['product_id'],
            'quantity'   => (int)$item['quantity'],
            'price'      => (float)$item['unit_price'],
        ], $items);

        return Order::fromArray($row);
    }

    /**
     * Cancela un pedido si puede ser cancelado.
     *
     * @param int $id ID del pedido
     * @return bool true si se canceló, false si no se puede cancelar o no existe
     */
    public function cancel(int $id): bool
    {
        $order = $this->findById($id);
        if ($order === null || !$order->canBeCancelled()) {
            return false;
        }

        $order->setStatus('cancelled');
        $this->db->query(
            'UPDATE orders SET status = ?, updated_at = ? WHERE id = ?',
            [$order->getStatus(), $order->getUpdatedAt()?->format('Y-m-d H:i:s'), $id]
        );

        $this->info("Pedido cancelado: ID {$id}");
        return true;
    }

    /**
     * Devuelve todos los pedidos de un usuario.
     *
     * @param int $userId ID del usuario
     * @return Order[]
     */
    public function getByUser(int $userId): array
    {
        $rows = $this->db->query(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        )->fetchAll();

        return array_map(fn($row) => Order::fromArray($row), $rows);
    }
}
