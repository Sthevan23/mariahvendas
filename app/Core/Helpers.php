<?php

namespace App\Core;

class Helpers
{
    public static function money(float|int|string|null $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    /** Cor (#rrggbb) para o nome de uma cor, quando o cadastro não tem o código. Nunca falha: devolve cinza claro. */
    public static function colorHex(?string $name, string $fallback = '#d9d4cf'): string
    {
        $key = mb_strtolower(trim((string) $name), 'UTF-8');
        $key = strtr($key, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
        if ($key === '') {
            return $fallback;
        }
        // Ordem importa: nomes compostos antes dos simples ("azul marinho" antes de "azul").
        $map = [
            'off white' => '#f5f1e6', 'off-white' => '#f5f1e6', 'marfim' => '#fffff0', 'creme' => '#f3e9d2', 'perola' => '#f1ebe0',
            'azul marinho' => '#1f2a44', 'azul royal' => '#2a4bd7', 'azul bebe' => '#a9cdf0', 'azul claro' => '#a9cdf0', 'azul petroleo' => '#1b5361',
            'azul turquesa' => '#2bb5b8', 'azul' => '#2f5fb3', 'marinho' => '#1f2a44', 'turquesa' => '#2bb5b8', 'petroleo' => '#1b5361',
            'verde militar' => '#4b5320', 'verde musgo' => '#5a6b3a', 'verde esmeralda' => '#1f8a5b', 'verde agua' => '#a8dccb', 'verde' => '#3f8a4f',
            'musgo' => '#5a6b3a', 'militar' => '#4b5320', 'esmeralda' => '#1f8a5b',
            'rosa claro' => '#f6c9d4', 'rosa bebe' => '#f6c9d4', 'rosa pink' => '#e6368a', 'rosa chiclete' => '#f28ab2', 'rosa' => '#f09bb4', 'pink' => '#e6368a',
            'vinho' => '#6d1230', 'bordo' => '#6d1230', 'marsala' => '#8e3b46', 'vermelho' => '#c4262e', 'cereja' => '#a31535',
            'terracota' => '#b8593a', 'ferrugem' => '#9c4a2a', 'laranja' => '#ee7d2b', 'coral' => '#f2705e', 'salmao' => '#f4a28c',
            'amarelo' => '#f2c94c', 'mostarda' => '#d4a21f', 'dourado' => '#c9a24b', 'ouro' => '#c9a24b',
            'lilas' => '#c3a6e0', 'lavanda' => '#c7b8e8', 'roxo' => '#6b3fa0', 'uva' => '#5b2a6e', 'violeta' => '#7a3fb0',
            'nude' => '#d2b48c', 'bege' => '#d9c3a0', 'camel' => '#c19a6b', 'areia' => '#dccaa5', 'caramelo' => '#b97a3e',
            'marrom' => '#6b4429', 'chocolate' => '#4a2c1a', 'cafe' => '#5a3a26',
            'preto' => '#111111', 'branco' => '#ffffff', 'cinza claro' => '#cfcfcf', 'cinza' => '#8a8a8a', 'grafite' => '#44474c', 'chumbo' => '#4a4d52',
            'prata' => '#c0c0c0', 'cru' => '#f0ead6', 'estampado' => '#b9a089',
        ];
        if (isset($map[$key])) {
            return $map[$key];
        }
        foreach ($map as $word => $hex) {
            if (str_contains($key, $word)) {
                return $hex;
            }
        }
        return $fallback;
    }

    /**
     * Cor a mostrar na bolinha: o código salvo, ou a cor pelo nome quando o código falta
     * ou é branco/cinza por engano (ex.: #fff, #ddd numa cor que não é branca).
     */
    public static function swatchHex(?string $name, ?string $hex): string
    {
        $hex = strtolower(trim((string) $hex));
        $byName = self::colorHex($name, '');
        $washedOut = $hex === '' || in_array($hex, ['#fff', '#ffffff', '#ddd', '#dddddd', '#eee', '#eeeeee', '#000', '#000000'], true);
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $hex) && (!$washedOut || $byName === '')) {
            return $hex;
        }
        return $byName !== '' ? $byName : ($hex !== '' && preg_match('/^#[0-9a-f]{3,6}$/', $hex) ? $hex : '#d9d4cf');
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
            $rows = $pdo->query('SELECT `key`, value FROM settings')->fetchAll();
            $cache = [];
            foreach ($rows as $row) {
                $cache[$row['key']] = $row['value'];
            }
        }
        return $cache[$key] ?? $default;
    }

    /** Grava uma configuração em qualquer driver (SQLite ou MySQL). */
    public static function saveSetting(string $key, string $value): void
    {
        $pdo = Database::connection();
        // Não usa rowCount(): no MySQL ele dá 0 quando o valor não muda.
        $exists = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE `key` = ?');
        $exists->execute([$key]);
        if ((int) $exists->fetchColumn() > 0) {
            $pdo->prepare('UPDATE settings SET value = ? WHERE `key` = ?')->execute([$value, $key]);
        } else {
            $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?)')->execute([$key, $value]);
        }
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

    public static function appUrl(): string
    {
        static $url = null;
        if ($url === null) {
            $cfg = require dirname(__DIR__, 2) . '/config/app.php';
            $url = rtrim((string) ($cfg['url'] ?? ''), '/');
        }
        return $url !== '' ? $url : 'http://localhost:8080';
    }

    public static function absoluteUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return self::appUrl() . '/' . ltrim($path, '/');
    }
}
