<?php

namespace App\Core;

/**
 * Limita tentativas de login falhas por IP + e-mail.
 * Guarda o contador em storage/ratelimit (fora do banco, sem migração).
 */
class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;

    public static function blocked(string $key): bool
    {
        $data = self::read($key);
        return $data['count'] >= self::MAX_ATTEMPTS && (time() - $data['first']) < self::WINDOW_SECONDS;
    }

    public static function hit(string $key): void
    {
        $data = self::read($key);
        if ((time() - $data['first']) >= self::WINDOW_SECONDS) {
            $data = ['first' => time(), 'count' => 0];
        }
        $data['count']++;

        $dir = self::dir();
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        file_put_contents(self::file($key), json_encode($data));
    }

    public static function clear(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            unlink($file);
        }
    }

    private static function read(string $key): array
    {
        $file = self::file($key);
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (!is_array($data) || !isset($data['first'], $data['count'])) {
            return ['first' => time(), 'count' => 0];
        }
        return $data;
    }

    private static function dir(): string
    {
        return dirname(__DIR__, 2) . '/storage/ratelimit';
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . hash('sha256', $key) . '.json';
    }
}
