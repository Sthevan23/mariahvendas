<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Helpers;
use PDO;

class Product
{
    public static function withImage(array $product): array
    {
        // Usa a primeira foto que realmente existe no servidor (evita imagem quebrada).
        $img = self::images((int) $product['id'], true)[0] ?? null;
        $product['image'] = $img['path'] ?? null;
        $product['image_alt'] = $img['alt'] ?? $product['name'];
        $product['stock_total'] = self::stockTotal((int) $product['id']);
        return $product;
    }

    public static function stockTotal(int $productId): int
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(SUM(stock),0) FROM product_variants WHERE product_id = ?');
        $stmt->execute([$productId]);
        return (int) $stmt->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Com $onlyExisting, descarta fotos locais (/uploads/...) cujo arquivo sumiu do servidor. */
    public static function images(int $productId, bool $onlyExisting = false): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order'
        );
        $stmt->execute([$productId]);
        $rows = $stmt->fetchAll();
        if (!$onlyExisting) {
            return $rows;
        }
        $public = dirname(__DIR__, 2) . '/public';
        return array_values(array_filter($rows, static function (array $r) use ($public): bool {
            $path = (string) $r['path'];
            return !str_starts_with($path, '/uploads/') || is_file($public . $path);
        }));
    }

    public static function variants(int $productId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM product_variants WHERE product_id = ?'
        );
        $stmt->execute([$productId]);
        $rows = $stmt->fetchAll();
        usort($rows, [self::class, 'compareVariants']);
        return $rows;
    }

    public static function compareVariants(array $a, array $b): int
    {
        $order = array_flip(['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG', 'U']);
        $sa = $order[$a['size']] ?? 100;
        $sb = $order[$b['size']] ?? 100;
        if ($sa !== $sb) {
            return $sa <=> $sb;
        }
        return strcasecmp((string) $a['color'], (string) $b['color']);
    }

    public static function firstAvailableVariant(array $variants): ?array
    {
        foreach ($variants as $variant) {
            if ((int) $variant['stock'] > 0) {
                return $variant;
            }
        }
        return $variants[0] ?? null;
    }

    public static function findVariant(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM product_variants WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function query(array $filters = []): array
    {
        $sql = 'SELECT p.*, c.name AS category_name FROM products p
            LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
        $params = [];

        if (!empty($filters['active_only'])) {
            $sql .= ' AND p.is_active = 1';
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['category_slug'])) {
            if ($filters['category_slug'] === 'colecao-atelier') {
                $sql .= " AND p.slug LIKE 'mariah-look-%'";
            } else {
                $sql .= ' AND c.slug = ?';
                $params[] = $filters['category_slug'];
            }
        }
        if (!empty($filters['exclude_ids']) && is_array($filters['exclude_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['exclude_ids'])));
            if ($ids) {
                $sql .= ' AND p.id NOT IN (' . implode(',', $ids) . ')';
            }
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['featured'])) {
            $sql .= ' AND p.is_featured = 1';
        }
        if (!empty($filters['is_new'])) {
            $sql .= ' AND p.is_new = 1';
        }
        if (!empty($filters['is_sale'])) {
            $sql .= ' AND p.is_sale = 1';
        }
        if (!empty($filters['is_bestseller'])) {
            $sql .= ' AND p.is_bestseller = 1';
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $sql .= ' AND p.price >= ?';
            $params[] = (float) $filters['min_price'];
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $sql .= ' AND p.price <= ?';
            $params[] = (float) $filters['max_price'];
        }
        if (!empty($filters['size']) || !empty($filters['color']) || !empty($filters['in_stock'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id';
            if (!empty($filters['size'])) {
                $sql .= ' AND v.size = ?';
                $params[] = $filters['size'];
            }
            if (!empty($filters['color'])) {
                $sql .= ' AND v.color = ?';
                $params[] = $filters['color'];
            }
            if (!empty($filters['in_stock'])) {
                $sql .= ' AND v.stock > 0';
            }
            $sql .= ')';
        }

        $order = match ($filters['sort'] ?? 'newest') {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'bestsellers' => 'p.sold_count DESC, p.name ASC',
            'name' => 'p.name ASC',
            default => 'p.id ASC',
        };
        $sql .= ' ORDER BY ' . $order;

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . max(1, (int) $filters['limit']);
            if (!empty($filters['offset'])) {
                $sql .= ' OFFSET ' . max(0, (int) $filters['offset']);
            }
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        return array_map([self::class, 'withImage'], $products);
    }

    public static function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE 1=1';
        $params = [];
        if (!empty($filters['active_only'])) {
            $sql .= ' AND p.is_active = 1';
        }
        if (!empty($filters['category_slug'])) {
            if ($filters['category_slug'] === 'colecao-atelier') {
                $sql .= " AND p.slug LIKE 'mariah-look-%'";
            } else {
                $sql .= ' AND c.slug = ?';
                $params[] = $filters['category_slug'];
            }
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['is_new'])) {
            $sql .= ' AND p.is_new = 1';
        }
        if (!empty($filters['is_sale'])) {
            $sql .= ' AND p.is_sale = 1';
        }
        if (!empty($filters['size']) || !empty($filters['color']) || !empty($filters['in_stock'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id';
            if (!empty($filters['size'])) {
                $sql .= ' AND v.size = ?';
                $params[] = $filters['size'];
            }
            if (!empty($filters['color'])) {
                $sql .= ' AND v.color = ?';
                $params[] = $filters['color'];
            }
            if (!empty($filters['in_stock'])) {
                $sql .= ' AND v.stock > 0';
            }
            $sql .= ')';
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $sql .= ' AND p.price >= ?';
            $params[] = (float) $filters['min_price'];
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $sql .= ' AND p.price <= ?';
            $params[] = (float) $filters['max_price'];
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function related(int $productId, ?int $categoryId, int $limit = 4): array
    {
        $pdo = Database::connection();
        if ($categoryId) {
            $stmt = $pdo->prepare(
                'SELECT * FROM products WHERE is_active = 1 AND id != ? AND category_id = ?
                 ORDER BY is_featured DESC, sold_count DESC LIMIT ?'
            );
            $stmt->bindValue(1, $productId, PDO::PARAM_INT);
            $stmt->bindValue(2, $categoryId, PDO::PARAM_INT);
            $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare(
                'SELECT * FROM products WHERE is_active = 1 AND id != ?
                 ORDER BY is_featured DESC, sold_count DESC LIMIT ?'
            );
            $stmt->bindValue(1, $productId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return array_map([self::class, 'withImage'], $stmt->fetchAll());
    }

    public static function sizes(): array
    {
        $sizes = Database::connection()->query('SELECT DISTINCT size FROM product_variants')->fetchAll(PDO::FETCH_COLUMN);
        $order = ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG', 'U'];
        usort($sizes, function ($a, $b) use ($order) {
            $ia = array_search($a, $order, true);
            $ib = array_search($b, $order, true);
            $ia = $ia === false ? 100 : $ia;
            $ib = $ib === false ? 100 : $ib;
            return $ia <=> $ib;
        });
        return $sizes;
    }

    public static function colors(): array
    {
        return Database::connection()->query('SELECT DISTINCT color, color_hex FROM product_variants ORDER BY color')->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, name, slug, description, price, compare_at_price, is_active, is_featured, is_new, is_sale, is_bestseller, cost_price)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'] ?: null,
            $data['name'],
            $data['slug'] ?? Helpers::slug($data['name']),
            $data['description'] ?? '',
            $data['price'],
            $data['compare_at_price'] ?: null,
            $data['is_active'] ?? 1,
            $data['is_featured'] ?? 0,
            $data['is_new'] ?? 0,
            $data['is_sale'] ?? 0,
            $data['is_bestseller'] ?? 0,
            $data['cost_price'] ?? 0,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, compare_at_price = ?,
             is_active = ?, is_featured = ?, is_new = ?, is_sale = ?, is_bestseller = ?, cost_price = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );
        $stmt->execute([
            $data['category_id'] ?: null,
            $data['name'],
            $data['slug'] ?? Helpers::slug($data['name']),
            $data['description'] ?? '',
            $data['price'],
            $data['compare_at_price'] ?: null,
            $data['is_active'] ?? 1,
            $data['is_featured'] ?? 0,
            $data['is_new'] ?? 0,
            $data['is_sale'] ?? 0,
            $data['is_bestseller'] ?? 0,
            $data['cost_price'] ?? 0,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function addImage(int $productId, string $path, ?string $alt = null, int $sort = 0): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$productId, $path, $alt, $sort]);
    }

    public static function nextImageSort(int $productId): int
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM product_images WHERE product_id = ?');
        $stmt->execute([$productId]);
        return (int) $stmt->fetchColumn();
    }

    public static function findImage(int $productId, int $imageId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM product_images WHERE id = ? AND product_id = ?');
        $stmt->execute([$imageId, $productId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function deleteImage(int $productId, int $imageId): ?array
    {
        $image = self::findImage($productId, $imageId);
        if (!$image) {
            return null;
        }
        Database::connection()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imageId]);
        return $image;
    }

    /** Coloca a foto escolhida em primeiro lugar (capa) e reordena as demais. */
    public static function makePrimaryImage(int $productId, int $imageId): void
    {
        $pdo = Database::connection();
        $ids = $pdo->prepare('SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
        $ids->execute([$productId]);
        $order = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
        if (!in_array($imageId, $order, true)) {
            return;
        }
        $order = array_values(array_merge([$imageId], array_diff($order, [$imageId])));
        $upd = $pdo->prepare('UPDATE product_images SET sort_order = ? WHERE id = ?');
        foreach ($order as $i => $id) {
            $upd->execute([$i, $id]);
        }
    }

    /** Verdadeiro se o arquivo ainda é usado por outro registro (evita apagar foto compartilhada). */
    public static function imagePathInUse(string $path): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM product_images WHERE path = ?');
        $stmt->execute([$path]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function upsertVariant(int $productId, string $size, string $color, ?string $hex, int $stock, ?string $sku = null): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, stock FROM product_variants WHERE product_id = ? AND size = ? AND color = ?');
        $stmt->execute([$productId, $size, $color]);
        $existing = $stmt->fetch();

        if ($existing) {
            $upd = $pdo->prepare('UPDATE product_variants SET stock = ?, color_hex = ?, sku = ? WHERE id = ?');
            $upd->execute([$stock, $hex, $sku, $existing['id']]);
            return (int) $existing['id'];
        }

        $ins = $pdo->prepare(
            'INSERT INTO product_variants (product_id, size, color, color_hex, sku, stock) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$productId, $size, $color, $hex, $sku, $stock]);
        return (int) $pdo->lastInsertId();
    }

    public static function lowStock(int $threshold = 3, int $limit = 6): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.id AS product_id, p.name AS product_name,
                    MIN(v.stock) AS stock,
                    SUM(CASE WHEN v.stock <= ? THEN 1 ELSE 0 END) AS variants_low
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE p.is_active = 1
             GROUP BY p.id
             HAVING MIN(v.stock) <= ?
             ORDER BY stock ASC, p.name ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $threshold, PDO::PARAM_INT);
        $stmt->bindValue(2, $threshold, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Destaques quando ainda não há pedidos pagos no sistema. */
    public static function topBySoldCount(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT name AS product_name, sold_count AS qty, (price * sold_count) AS revenue
             FROM products
             WHERE is_active = 1 AND sold_count > 0
             ORDER BY sold_count DESC, name ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
