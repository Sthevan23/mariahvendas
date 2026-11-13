<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Fotos enviadas pelo admin.
 * - Disco: storage/uploads/media/ (fora do que o Git da Hostinger atualiza)
 * - Cópia no MySQL (tabela media_files): se o arquivo sumir do disco, a foto volta do banco
 * - Servidas por /media/{arquivo}
 */
class MediaStore
{
    public const URL_PREFIX = '/media/';
    private const MAX_UPLOAD = 12 * 1024 * 1024;
    private const MAX_MIRROR = 6 * 1024 * 1024;
    private const MAX_SIDE = 1600;

    private const MIME_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public static function dir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/uploads/media';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function isMediaPath(string $path): bool
    {
        return str_starts_with($path, self::URL_PREFIX);
    }

    public static function nameFromPath(string $path): string
    {
        return basename(substr($path, strlen(self::URL_PREFIX)));
    }

    public static function validName(string $name): bool
    {
        return (bool) preg_match('/^m_[a-zA-Z0-9._-]+\.(jpe?g|png|webp|gif)$/', $name);
    }

    /** @return array{ok:bool,path?:string,error?:string} */
    public static function saveUpload(array $file): array
    {
        $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($code !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'foto muito grande para o servidor',
                UPLOAD_ERR_FORM_SIZE => 'foto muito grande',
                UPLOAD_ERR_PARTIAL => 'envio incompleto, tente de novo',
                UPLOAD_ERR_NO_TMP_DIR => 'servidor sem pasta temporária',
                UPLOAD_ERR_CANT_WRITE => 'servidor não conseguiu gravar',
                UPLOAD_ERR_EXTENSION => 'envio bloqueado pelo servidor',
            ];
            return ['ok' => false, 'error' => $messages[$code] ?? 'nenhum arquivo enviado'];
        }
        if (($file['size'] ?? 0) > self::MAX_UPLOAD) {
            return ['ok' => false, 'error' => 'foto maior que 12 MB'];
        }

        $tmp = (string) $file['tmp_name'];
        $info = @getimagesize($tmp);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        if (!isset(self::MIME_EXT[$mime])) {
            return ['ok' => false, 'error' => 'formato inválido (use JPG, PNG, WebP ou GIF)'];
        }

        $blob = self::optimize($tmp, $mime);
        if ($blob === null) {
            $blob = file_get_contents($tmp);
            $outMime = $mime;
        } else {
            [$blob, $outMime] = $blob;
        }
        if ($blob === false || $blob === '') {
            return ['ok' => false, 'error' => 'não foi possível ler a foto'];
        }

        $name = 'm_' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . self::MIME_EXT[$outMime];
        $dest = self::dir() . '/' . $name;
        if (file_put_contents($dest, $blob) === false) {
            return ['ok' => false, 'error' => 'não foi possível gravar no servidor (permissão em storage/)'];
        }
        @chmod($dest, 0644);

        self::mirror($name, $outMime, $blob);

        return ['ok' => true, 'path' => self::URL_PREFIX . $name];
    }

    /** Reduz fotos grandes de celular (lado maior 1600px). Devolve [binário, mime] ou null. */
    private static function optimize(string $tmp, string $mime): ?array
    {
        if (!function_exists('imagecreatefromstring') || $mime === 'image/gif') {
            return null;
        }
        $info = @getimagesize($tmp);
        if (!$info || max($info[0], $info[1]) <= self::MAX_SIDE) {
            return null;
        }
        $src = @imagecreatefromstring((string) file_get_contents($tmp));
        if (!$src) {
            return null;
        }
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmp);
            $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;
            if ($rot) {
                $rotated = imagerotate($src, $rot, 0);
                if ($rotated) {
                    $src = $rotated;
                }
            }
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = self::MAX_SIDE / max($w, $h);
        $dst = imagecreatetruecolor((int) round($w * $scale), (int) round($h * $scale));
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);

        ob_start();
        if ($mime === 'image/png') {
            imagepng($dst, null, 6);
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($dst, null, 88);
        } else {
            $mime = 'image/jpeg';
            imagejpeg($dst, null, 88);
        }
        $out = (string) ob_get_clean();

        return $out !== '' ? [$out, $mime] : null;
    }

    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS media_files (
            filename VARCHAR(190) NOT NULL PRIMARY KEY,
            mime VARCHAR(64) NOT NULL DEFAULT \'image/jpeg\',
            data LONGBLOB NOT NULL,
            bytes INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private static function mirror(string $name, string $mime, string $blob): void
    {
        if (strlen($blob) > self::MAX_MIRROR) {
            return;
        }
        try {
            $pdo = Database::connection();
            self::ensureTable($pdo);
            $pdo->prepare('REPLACE INTO media_files (filename, mime, data, bytes) VALUES (?, ?, ?, ?)')
                ->execute([$name, $mime, $blob, strlen($blob)]);
        } catch (Throwable $e) {
            // o disco já guardou a foto; o envio segue ok
        }
    }

    /** @return array{0:string,1:string}|null [binário, mime] */
    public static function read(string $name): ?array
    {
        if (!self::validName($name)) {
            return null;
        }
        $path = self::dir() . '/' . $name;
        if (is_file($path) && is_readable($path)) {
            $raw = file_get_contents($path);
            if ($raw !== false && $raw !== '') {
                $mime = (string) (@getimagesizefromstring($raw)['mime'] ?? 'application/octet-stream');
                return [$raw, $mime];
            }
        }
        try {
            $pdo = Database::connection();
            self::ensureTable($pdo);
            $stmt = $pdo->prepare('SELECT mime, data FROM media_files WHERE filename = ? LIMIT 1');
            $stmt->execute([$name]);
            $row = $stmt->fetch();
            if ($row && $row['data'] !== '') {
                // restaura no disco para as próximas leituras
                @file_put_contents($path, $row['data']);
                return [$row['data'], $row['mime'] ?: 'image/jpeg'];
            }
        } catch (Throwable $e) {
        }
        return null;
    }

    /** Confere se a foto existe no disco ou no banco, sem carregar o arquivo inteiro. */
    public static function exists(string $path): bool
    {
        $name = self::nameFromPath($path);
        if (!self::validName($name)) {
            return false;
        }
        if (is_file(self::dir() . '/' . $name)) {
            return true;
        }
        try {
            $pdo = Database::connection();
            self::ensureTable($pdo);
            $stmt = $pdo->prepare('SELECT 1 FROM media_files WHERE filename = ? LIMIT 1');
            $stmt->execute([$name]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function delete(string $path): void
    {
        $name = self::nameFromPath($path);
        if (!self::validName($name)) {
            return;
        }
        $file = self::dir() . '/' . $name;
        if (is_file($file)) {
            @unlink($file);
        }
        try {
            Database::connection()->prepare('DELETE FROM media_files WHERE filename = ?')->execute([$name]);
        } catch (Throwable $e) {
        }
    }
}
