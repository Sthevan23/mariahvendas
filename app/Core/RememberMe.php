<?php

namespace App\Core;

use App\Models\User;
use PDO;
use Throwable;

/**
 * "Manter conectado" do painel admin.
 * Guarda um token aleatório no banco (só o hash) e no cookie do navegador, válido por 30 dias.
 * Como fica no banco, continua valendo mesmo depois de um deploy.
 */
class RememberMe
{
    private const COOKIE = 'mariah_remember';
    private const DAYS = 30;

    private static bool $tried = false;

    private static function table(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS remember_tokens (
            selector CHAR(24) NOT NULL PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private static function setCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Cria o token e grava o cookie. Chamar logo após um login de admin bem-sucedido. */
    public static function issue(int $userId): void
    {
        try {
            $pdo = Database::connection();
            self::table($pdo);
            $pdo->exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');
            $selector = bin2hex(random_bytes(12));
            $validator = bin2hex(random_bytes(32));
            $expires = time() + self::DAYS * 86400;
            $pdo->prepare('INSERT INTO remember_tokens (selector, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)')
                ->execute([$selector, $userId, hash('sha256', $validator), date('Y-m-d H:i:s', $expires)]);
            self::setCookie($selector . ':' . $validator, $expires);
        } catch (Throwable $e) {
            // sem o recurso, o login normal segue funcionando
        }
    }

    /** Se não há sessão mas há cookie válido, entra de novo (só contas admin). */
    public static function restore(): bool
    {
        if (self::$tried || empty($_COOKIE[self::COOKIE])) {
            return false;
        }
        self::$tried = true;

        $parts = explode(':', (string) $_COOKIE[self::COOKIE], 2);
        if (count($parts) !== 2 || !ctype_xdigit($parts[0]) || !ctype_xdigit($parts[1])) {
            return false;
        }
        [$selector, $validator] = $parts;

        try {
            $pdo = Database::connection();
            self::table($pdo);
            $stmt = $pdo->prepare('SELECT user_id, token_hash FROM remember_tokens WHERE selector = ? AND expires_at > NOW()');
            $stmt->execute([$selector]);
            $row = $stmt->fetch();
            if (!$row || !hash_equals($row['token_hash'], hash('sha256', $validator))) {
                if ($row) {
                    $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
                }
                self::setCookie('', time() - 3600);
                return false;
            }

            $user = User::find((int) $row['user_id']);
            if (!$user || ($user['role'] ?? '') !== 'admin') {
                return false;
            }

            Auth::loginUser($user);
            // renova o token a cada uso
            $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
            self::issue((int) $user['id']);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Sair: apaga o token deste navegador. */
    public static function forget(): void
    {
        $selector = explode(':', (string) ($_COOKIE[self::COOKIE] ?? ''), 2)[0];
        if ($selector !== '' && ctype_xdigit($selector)) {
            try {
                Database::connection()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
            } catch (Throwable $e) {
            }
        }
        unset($_COOKIE[self::COOKIE]);
        self::setCookie('', time() - 3600);
    }

    /** Troca de senha: derruba todos os aparelhos conectados. */
    public static function forgetAll(int $userId): void
    {
        try {
            $pdo = Database::connection();
            self::table($pdo);
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
        } catch (Throwable $e) {
        }
    }
}
