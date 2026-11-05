<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Helpers;
use PDO;

class Order
{
    public static function create(array $order, array $items): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $number = Helpers::orderNumber();
            $stmt = $pdo->prepare(
                'INSERT INTO orders (number, user_id, customer_name, customer_email, customer_phone, shipping_address, shipping_city, shipping_state, shipping_zip, shipping_method_id, shipping_price, payment_method_id, payment_status, status, subtotal, discount, total, coupon_code, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $number,
                $order['user_id'] ?? null,
                $order['customer_name'],
                $order['customer_email'] ?? null,
                $order['customer_phone'],
                $order['shipping_address'] ?? null,
                $order['shipping_city'] ?? null,
                $order['shipping_state'] ?? null,
                $order['shipping_zip'] ?? null,
                $order['shipping_method_id'] ?? null,
                $order['shipping_price'] ?? 0,
                $order['payment_method_id'] ?? null,
                $order['payment_status'] ?? 'pending',
                $order['status'] ?? 'awaiting_confirmation',
                $order['subtotal'],
                $order['discount'] ?? 0,
                $order['total'],
                $order['coupon_code'] ?? null,
                $order['notes'] ?? null,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, color, unit_price, quantity, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stockStmt = $pdo->prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?');
            $moveStmt = $pdo->prepare(
                'INSERT INTO stock_movements (variant_id, type, quantity, note) VALUES (?, ?, ?, ?)'
            );
            $soldStmt = $pdo->prepare('UPDATE products SET sold_count = sold_count + ? WHERE id = ?');

            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['variant_id'],
                    $item['product_name'],
                    $item['size'],
                    $item['color'],
                    $item['unit_price'],
                    $item['quantity'],
                    $item['total'],
                ]);

                $stockStmt->execute([$item['quantity'], $item['variant_id'], $item['quantity']]);
                if ($stockStmt->rowCount() === 0) {
                    throw new \RuntimeException('Estoque insuficiente para ' . $item['product_name']);
                }

                $moveStmt->execute([
                    $item['variant_id'],
                    'out',
                    $item['quantity'],
                    'Pedido ' . $number,
                ]);
                $soldStmt->execute([$item['quantity'], $item['product_id']]);
            }

            $hist = $pdo->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
            $hist->execute([$orderId, $order['status'] ?? 'awaiting_confirmation', 'Pedido criado']);

            if (!empty($order['coupon_code'])) {
                // Só incrementa se ainda houver usos disponíveis (checagem e escrita na mesma transação).
                $couponStmt = $pdo->prepare(
                    'UPDATE coupons SET used_count = used_count + 1
                     WHERE code = ? AND (usage_limit IS NULL OR used_count < usage_limit)'
                );
                $couponStmt->execute([$order['coupon_code']]);
                if ($couponStmt->rowCount() === 0) {
                    throw new \RuntimeException('Este cupom acabou de se esgotar. Remova-o da sacola e tente novamente.');
                }
            }

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT o.*, sm.name AS shipping_name, pm.name AS payment_name
             FROM orders o
             LEFT JOIN shipping_methods sm ON sm.id = o.shipping_method_id
             LEFT JOIN payment_methods pm ON pm.id = o.payment_method_id
             WHERE o.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByNumber(string $number): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT o.*, sm.name AS shipping_name, pm.name AS payment_name
             FROM orders o
             LEFT JOIN shipping_methods sm ON sm.id = o.shipping_method_id
             LEFT JOIN payment_methods pm ON pm.id = o.payment_method_id
             WHERE o.number = ?'
        );
        $stmt->execute([$number]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function items(int $orderId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public static function history(int $orderId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public static function all(?string $status = null): array
    {
        if ($status) {
            $stmt = Database::connection()->prepare('SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC');
            $stmt->execute([$status]);
            return $stmt->fetchAll();
        }
        return Database::connection()->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status, ?string $note = null, ?int $userId = null, ?string $paymentStatus = null): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $current = $pdo->prepare('SELECT status FROM orders WHERE id = ?');
            $current->execute([$id]);
            $previousStatus = $current->fetchColumn();

            // Ao cancelar, devolve o estoque — só uma vez, mesmo se o status for salvo de novo como cancelado.
            if ($status === 'cancelled' && $previousStatus !== 'cancelled') {
                self::restoreStock($pdo, $id, $userId);
            }

            if ($paymentStatus) {
                $stmt = $pdo->prepare('UPDATE orders SET status = ?, payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                $stmt->execute([$status, $paymentStatus, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
                $stmt->execute([$status, $id]);
            }

            $hist = $pdo->prepare('INSERT INTO order_status_history (order_id, status, note, user_id) VALUES (?, ?, ?, ?)');
            $hist->execute([$id, $status, $note, $userId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function restoreStock(PDO $pdo, int $orderId, ?int $userId): void
    {
        $items = $pdo->prepare('SELECT variant_id, quantity, product_name FROM order_items WHERE order_id = ? AND variant_id IS NOT NULL');
        $items->execute([$orderId]);

        $stockStmt = $pdo->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?');
        $moveStmt = $pdo->prepare(
            'INSERT INTO stock_movements (variant_id, type, quantity, note, user_id) VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($items->fetchAll() as $item) {
            $stockStmt->execute([$item['quantity'], $item['variant_id']]);
            $moveStmt->execute([
                $item['variant_id'],
                'in',
                $item['quantity'],
                'Cancelamento do pedido #' . $orderId,
                $userId,
            ]);
        }
    }

    public static function revenue(string $from, string $to): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid' AND date(created_at) BETWEEN date(?) AND date(?)"
        );
        $stmt->execute([$from, $to]);
        return (float) $stmt->fetchColumn();
    }

    public static function count(string $from, string $to, ?string $paymentStatus = null): int
    {
        $sql = 'SELECT COUNT(*) FROM orders WHERE date(created_at) BETWEEN date(?) AND date(?)';
        $params = [$from, $to];
        if ($paymentStatus) {
            $sql .= ' AND payment_status = ?';
            $params[] = $paymentStatus;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function awaiting(): array
    {
        return Database::connection()->query(
            "SELECT * FROM orders WHERE status IN ('awaiting_confirmation','awaiting_payment') ORDER BY created_at DESC LIMIT 10"
        )->fetchAll();
    }

    public static function topProducts(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT oi.product_name, SUM(oi.quantity) AS qty, SUM(oi.total) AS revenue
             FROM order_items oi JOIN orders o ON o.id = oi.order_id
             WHERE o.payment_status = \'paid\'
             GROUP BY oi.product_name ORDER BY qty DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function salesByDay(int $days = 14): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT date(created_at) AS day, COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END),0) AS total
             FROM orders WHERE date(created_at) >= date('now', ?)
             GROUP BY date(created_at) ORDER BY day ASC"
        );
        $stmt->execute(['-' . $days . ' days']);
        return $stmt->fetchAll();
    }
}
