<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Helpers;

class Category
{
    public static function allActive(): array
    {
        $rows = Database::connection()
            ->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name')
            ->fetchAll();

        // Esconde categorias sem produtos (exceto coleção Bali, que é virtual)
        $pdo = Database::connection();
        $out = [];
        foreach ($rows as $c) {
            if (in_array($c['slug'], ['jeans', 'acessorios'], true)) {
                continue;
            }
            if ($c['slug'] === 'colecao-atelier' || in_array($c['slug'], ['novidades', 'promocoes'], true)) {
                $out[] = $c;
                continue;
            }
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE is_active = 1 AND category_id = ?');
            $stmt->execute([(int) $c['id']]);
            if ((int) $stmt->fetchColumn() > 0) {
                $out[] = $c;
            }
        }
        return $out;
    }

    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM categories ORDER BY sort_order, name')
            ->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO categories (name, slug, image, description, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'] ?? Helpers::slug($data['name']),
            $data['image'] ?? null,
            $data['description'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE categories SET name = ?, slug = ?, image = ?, description = ?, is_active = ?, sort_order = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'] ?? Helpers::slug($data['name']),
            $data['image'] ?? null,
            $data['description'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0,
            $id,
        ]);
    }
}
