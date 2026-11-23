<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDO;

/**
 * Contador de visitas próprio (sem serviços externos, sem guardar IP).
 * Visitante único = cookie anônimo de 1 ano. Visitas do admin logado e de robôs não contam.
 */
class Analytics
{
    private const COOKIE = 'mv_vid';

    private static function table(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            day DATE NOT NULL,
            created_at DATETIME NOT NULL,
            visitor CHAR(16) NOT NULL,
            path VARCHAR(255) NOT NULL,
            referrer VARCHAR(120) NOT NULL DEFAULT '',
            device VARCHAR(10) NOT NULL DEFAULT 'desktop',
            INDEX idx_day (day),
            INDEX idx_visitor (visitor),
            INDEX idx_path (path)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** Registra a visita da requisição atual (chamado no index.php, antes das rotas). */
    public static function track(): void
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                return;
            }
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $path = rtrim($path, '/') ?: '/';
            if (preg_match('#^/(admin|media|assets|uploads|robots\.txt|sitemap\.xml|favicon)#', $path)) {
                return;
            }
            if (Auth::user() !== null) {
                return; // dona/admin navegando na própria loja
            }
            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($ua === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless|lighthouse|pingdom|uptime/i', $ua)) {
                return;
            }
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (($_SERVER['HTTP_SEC_PURPOSE'] ?? '') !== '' )) {
                return; // chamadas AJAX e pré-carregamento do navegador
            }

            $vid = (string) ($_COOKIE[self::COOKIE] ?? '');
            if (!preg_match('/^[a-f0-9]{16}$/', $vid)) {
                $vid = bin2hex(random_bytes(8));
                $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
                if (!headers_sent()) {
                    setcookie(self::COOKIE, $vid, [
                        'expires' => time() + 31536000,
                        'path' => '/',
                        'secure' => $https,
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }
            }

            $ref = '';
            $refHost = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST);
            $myHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
            if (is_string($refHost) && $refHost !== '' && !hash_equals(strtolower($myHost), strtolower($refHost))) {
                $ref = substr(preg_replace('/^www\./i', '', strtolower($refHost)), 0, 120);
            }
            // Campanhas: ?utm_source=instagram vira origem "instagram"
            $utm = (string) ($_GET['utm_source'] ?? '');
            if ($utm !== '' && preg_match('/^[a-z0-9._-]{1,40}$/i', $utm)) {
                $ref = strtolower($utm);
            }

            $device = preg_match('/mobile|android|iphone|ipod/i', $ua) ? 'mobile' : (preg_match('/ipad|tablet/i', $ua) ? 'tablet' : 'desktop');

            $pdo = Database::connection();
            self::table($pdo);
            $pdo->prepare('INSERT INTO page_views (day, created_at, visitor, path, referrer, device) VALUES (?,?,?,?,?,?)')
                ->execute([date('Y-m-d'), date('Y-m-d H:i:s'), $vid, substr($path, 0, 255), $ref, $device]);
        } catch (\Throwable $e) {
            // Estatística nunca pode derrubar a loja.
        }
    }

    /** Resumo do período (últimos $days dias, incluindo hoje). */
    public static function summary(int $days): array
    {
        $pdo = Database::connection();
        self::table($pdo);
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $q = static function (string $sql, array $args = []) use ($pdo): array {
            $st = $pdo->prepare($sql);
            $st->execute($args);
            return $st->fetchAll();
        };
        $one = static function (string $sql, array $args = []) use ($pdo) {
            $st = $pdo->prepare($sql);
            $st->execute($args);
            return $st->fetchColumn();
        };

        $today = date('Y-m-d');
        $out = [
            'days' => $days,
            'visitors_today' => (int) $one('SELECT COUNT(DISTINCT visitor) FROM page_views WHERE day = ?', [$today]),
            'views_today' => (int) $one('SELECT COUNT(*) FROM page_views WHERE day = ?', [$today]),
            'visitors' => (int) $one('SELECT COUNT(DISTINCT visitor) FROM page_views WHERE day >= ?', [$from]),
            'views' => (int) $one('SELECT COUNT(*) FROM page_views WHERE day >= ?', [$from]),
            'visitors_total' => (int) $one('SELECT COUNT(DISTINCT visitor) FROM page_views'),
            'first_day' => $one('SELECT MIN(day) FROM page_views'),
        ];
        $out['returning'] = (int) $one('SELECT COUNT(*) FROM (SELECT visitor FROM page_views WHERE day >= ? GROUP BY visitor HAVING COUNT(DISTINCT day) > 1) t', [$from]);

        $byDay = [];
        foreach ($q('SELECT day, COUNT(DISTINCT visitor) v, COUNT(*) p FROM page_views WHERE day >= ? GROUP BY day', [$from]) as $r) {
            $byDay[$r['day']] = ['visitors' => (int) $r['v'], 'views' => (int) $r['p']];
        }
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $series[] = ['day' => $d] + ($byDay[$d] ?? ['visitors' => 0, 'views' => 0]);
        }
        $out['series'] = $series;

        $out['pages'] = $q('SELECT path, COUNT(*) views, COUNT(DISTINCT visitor) visitors FROM page_views WHERE day >= ? GROUP BY path ORDER BY views DESC LIMIT 10', [$from]);
        $out['sources'] = $q("SELECT IF(referrer = '', 'Acesso direto', referrer) src, COUNT(DISTINCT visitor) visitors FROM page_views WHERE day >= ? GROUP BY src ORDER BY visitors DESC LIMIT 8", [$from]);
        $out['devices'] = $q('SELECT device, COUNT(DISTINCT visitor) visitors FROM page_views WHERE day >= ? GROUP BY device ORDER BY visitors DESC', [$from]);

        // Produtos mais vistos (com o nome vindo do cadastro)
        $prods = $q("SELECT SUBSTRING(path, 10) slug, COUNT(*) views FROM page_views WHERE day >= ? AND path LIKE '/produto/%' GROUP BY slug ORDER BY views DESC LIMIT 8", [$from]);
        foreach ($prods as &$p) {
            $st = $pdo->prepare('SELECT name FROM products WHERE slug = ?');
            $st->execute([$p['slug']]);
            $p['name'] = (string) ($st->fetchColumn() ?: $p['slug']);
        }
        $out['products'] = $prods;

        return $out;
    }
}
