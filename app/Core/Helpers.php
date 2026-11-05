<?php

namespace App\Core;

class Helpers
{
    public static function money(float|int|string|null $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function slug(string $text): string
    {
        $map = [
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
            'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n','ý'=>'y','ÿ'=>'y',
            'À'=>'a','Á'=>'a','Â'=>'a','Ã'=>'a','Ä'=>'a',
            'È'=>'e','É'=>'e','Ê'=>'e','Ë'=>'e',
            'Ì'=>'i','Í'=>'i','Î'=>'i','Ï'=>'i',
            'Ò'=>'o','Ó'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o',
            'Ù'=>'u','Ú'=>'u','Û'=>'u','Ü'=>'u',
            'Ç'=>'c','Ñ'=>'n',
        ];
        $text = strtr($text, $map);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = strtolower($text);
        $text = preg_replace('~[^a-z0-9]+~', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'item';
    }

    public static function asset(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public static function setting(string $key, mixed $default = null): mixed
    {
        static $cache = null;
        if ($cache === null) {
            $pdo = Database::connection();
            $rows = $pdo->query('SELECT key, value FROM settings')->fetchAll();
            $cache = [];
            foreach ($rows as $row) {
                $cache[$row['key']] = $row['value'];
            }
        }
        return $cache[$key] ?? $default;
    }

    public static function flash(): ?array
    {
        if (empty($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    public static function isFavorite(int $productId): bool
    {
        $favorites = $_SESSION['favorites'] ?? [];
        return in_array($productId, $favorites, true);
    }

    public static function cartCount(): int
    {
        $cart = $_SESSION['cart'] ?? [];
        $count = 0;
        foreach ($cart as $item) {
            $count += (int) $item['qty'];
        }
        return $count;
    }

    public static function orderNumber(): string
    {
        return 'MD' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public static function whatsappLink(string $message): string
    {
        $phone = preg_replace('/\\D+/', '', (string) self::setting('whatsapp', '5511999999999'));
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }
}
