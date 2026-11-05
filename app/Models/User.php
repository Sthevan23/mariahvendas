<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'customer',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function customers(): array
    {
        $sql = "SELECT u.*,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
            (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.payment_status = 'paid') AS total_spent,
            (SELECT MAX(o.created_at) FROM orders o WHERE o.user_id = u.id) AS last_order_at
            FROM users u WHERE u.role = 'customer' ORDER BY u.created_at DESC";
        return Database::connection()->query($sql)->fetchAll();
    }
}
