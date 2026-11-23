<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDO;

/**
 * Estatísticas próprias (sem Google Analytics). Guarda: visita, tempo na página, cliques em botões
 * e a cidade aproximada (a partir do IP, que NÃO é guardado). Visitante = cookie anônimo de 1 ano.
 * Visitas do admin logado e de robôs não contam.
 */
class Analytics
{
    private const COOKIE = 'mv_vid';
    private static int $pvId = 0;

    public static function pvId(): int
    {
        return self::$pvId;
    }

    private static function table(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            day DATE NOT NULL,
            created_at DATETIME NOT NULL,
            visitor CHAR(16) NOT NULL,
            path VARCHAR(255) NOT NULL,
            referrer VARCHAR(120) NOT NULL DEFAULT '',
            device VARCHAR(10) NOT NULL DEFAULT 'desktop',
            sid CHAR(12) NOT NULL DEFAULT '',
            dur INT NOT NULL DEFAULT 0,
            INDEX idx_day (day),
            INDEX idx_visitor (visitor),
            INDEX idx_path (path)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            day DATE NOT NULL,
            created_at DATETIME NOT NULL,
            visitor CHAR(16) NOT NULL,
            kind VARCHAR(20) NOT NULL DEFAULT 'link',
            label VARCHAR(80) NOT NULL,
            path VARCHAR(255) NOT NULL DEFAULT '',
            INDEX idx_day (day),
            INDEX idx_label (label)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS visitor_geo (
            visitor CHAR(16) NOT NULL PRIMARY KEY,
            city VARCHAR(80) NOT NULL DEFAULT '',
            region VARCHAR(80) NOT NULL DEFAULT '',
            country VARCHAR(60) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Tabela criada na 1ª versão não tinha sid/dur: acrescenta uma única vez.
        $flag = dirname(__DIR__, 2) . '/storage/analytics_v2.flag';
        if (!is_file($flag)) {
            try {
                if (!$pdo->query("SHOW COLUMNS FROM page_views LIKE 'sid'")->fetchAll()) {
                    $pdo->exec("ALTER TABLE page_views ADD COLUMN sid CHAR(12) NOT NULL DEFAULT '', ADD COLUMN dur INT NOT NULL DEFAULT 0");
                }
                @file_put_contents($flag, '1');
            } catch (\Throwable $e) {
            }
        }
    }

    /** Visitante atual (cookie anônimo); cria se não existir. */
    private static function visitorId(bool $create): string
    {
        $vid = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (preg_match('/^[a-f0-9]{16}$/', $vid)) {
            return $vid;
        }
        if (!$create) {
            return '';
        }
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
        $_COOKIE[self::COOKIE] = $vid;
        return $vid;
    }

    private static function isBot(): bool
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless|lighthouse|pingdom|uptime/i', $ua);
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
            if (Auth::user() !== null || self::isBot()) {
                return; // dona/admin navegando na própria loja, ou robô
            }
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (($_SERVER['HTTP_SEC_PURPOSE'] ?? '') !== '')) {
                return; // chamadas AJAX e pré-carregamento do navegador
            }

            $vid = self::visitorId(true);

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

            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $device = preg_match('/mobile|android|iphone|ipod/i', $ua) ? 'mobile' : (preg_match('/ipad|tablet/i', $ua) ? 'tablet' : 'desktop');

            if (empty($_SESSION['mv_sid'])) {
                $_SESSION['mv_sid'] = bin2hex(random_bytes(6));
            }

            $pdo = Database::connection();
            self::table($pdo);
            $pdo->prepare('INSERT INTO page_views (day, created_at, visitor, path, referrer, device, sid) VALUES (?,?,?,?,?,?,?)')
                ->execute([date('Y-m-d'), date('Y-m-d H:i:s'), $vid, substr($path, 0, 255), $ref, $device, $_SESSION['mv_sid']]);
            self::$pvId = (int) $pdo->lastInsertId();
        } catch (\Throwable $e) {
            // Estatística nunca pode derrubar a loja.
        }
    }

    /** Recebe o aviso do navegador: tempo na página + cliques em botões. */
    public static function ping(array $data): void
    {
        try {
            if (self::isBot() || Auth::user() !== null) {
                return;
            }
            $vid = self::visitorId(false);
            if ($vid === '') {
                return;
            }
            $pdo = Database::connection();
            self::table($pdo);

            $pv = (int) ($data['pv'] ?? 0);
            $dur = max(0, min(3600, (int) ($data['dur'] ?? 0)));
            if ($pv > 0 && $dur > 0) {
                $pdo->prepare('UPDATE page_views SET dur = GREATEST(dur, ?) WHERE id = ? AND visitor = ?')->execute([$dur, $pv, $vid]);
            }

            $path = substr((string) parse_url((string) ($data['path'] ?? '/'), PHP_URL_PATH), 0, 255) ?: '/';
            $kinds = ['whatsapp', 'carrinho', 'comprar', 'tamanho', 'cor', 'menu', 'link', 'botao', 'favorito'];
            $n = 0;
            foreach ((array) ($data['clicks'] ?? []) as $c) {
                if (++$n > 15) {
                    break;
                }
                $label = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($c['l'] ?? ''))));
                $label = mb_substr($label, 0, 80);
                $kind = in_array($c['k'] ?? '', $kinds, true) ? $c['k'] : 'link';
                if ($label === '') {
                    continue;
                }
                $pdo->prepare('INSERT INTO site_events (day, created_at, visitor, kind, label, path) VALUES (?,?,?,?,?,?)')
                    ->execute([date('Y-m-d'), date('Y-m-d H:i:s'), $vid, $kind, $label, $path]);
            }

            // Cidade: descobre uma vez por visitante e guarda só a cidade (nunca o IP).
            $has = $pdo->prepare('SELECT 1 FROM visitor_geo WHERE visitor = ?');
            $has->execute([$vid]);
            if (!$has->fetchColumn()) {
                self::lookupGeo($pdo, $vid);
            }
        } catch (\Throwable $e) {
        }
    }

    private static function lookupGeo(PDO $pdo, string $vid): void
    {
        $ip = '';
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
            $cand = trim(explode(',', (string) ($_SERVER[$h] ?? ''))[0]);
            if (filter_var($cand, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $ip = $cand;
                break;
            }
        }
        $city = $region = $country = '';
        if ($ip !== '') {
            $url = 'https://ipwho.is/' . rawurlencode($ip) . '?fields=success,city,region,country&lang=pt-BR';
            $json = null;
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_CONNECTTIMEOUT => 2]);
                $json = curl_exec($ch);
                curl_close($ch);
            } else {
                $json = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 3]]));
            }
            $d = is_string($json) ? json_decode($json, true) : null;
            if (is_array($d) && !empty($d['success'])) {
                $city = mb_substr((string) ($d['city'] ?? ''), 0, 80);
                $region = mb_substr((string) ($d['region'] ?? ''), 0, 80);
                $country = mb_substr((string) ($d['country'] ?? ''), 0, 60);
            }
        }
        try {
            $pdo->prepare('INSERT INTO visitor_geo (visitor, city, region, country, created_at) VALUES (?,?,?,?,?)')
                ->execute([$vid, $city, $region, $country, date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
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
        $prevFrom = date('Y-m-d', strtotime('-' . (2 * $days - 1) . ' days'));
        $out['prev_visitors'] = (int) $one('SELECT COUNT(DISTINCT visitor) FROM page_views WHERE day >= ? AND day < ?', [$prevFrom, $from]);
        $out['prev_views'] = (int) $one('SELECT COUNT(*) FROM page_views WHERE day >= ? AND day < ?', [$prevFrom, $from]);
        $out['online'] = (int) $one('SELECT COUNT(DISTINCT visitor) FROM page_views WHERE created_at >= ?', [date('Y-m-d H:i:s', time() - 300)]);
        $hours = array_fill(0, 24, 0);
        foreach ($q('SELECT HOUR(created_at) h, COUNT(DISTINCT visitor) v FROM page_views WHERE day >= ? GROUP BY h', [$from]) as $r) {
            $hours[(int) $r['h']] = (int) $r['v'];
        }
        $out['hours'] = $hours;
        $out['returning'] = (int) $one('SELECT COUNT(*) FROM (SELECT visitor FROM page_views WHERE day >= ? GROUP BY visitor HAVING COUNT(DISTINCT day) > 1) t', [$from]);

        // Visitas (sessões): sid; registros antigos sem sid contam cada um como uma visita
        $sess = "IF(sid = '', CONCAT('v', id), sid)";
        $out['sessions'] = (int) $one("SELECT COUNT(DISTINCT $sess) FROM page_views WHERE day >= ?", [$from]);
        $out['pages_per_visit'] = $out['sessions'] > 0 ? round($out['views'] / $out['sessions'], 1) : 0;
        $out['bounce'] = (int) $one("SELECT COUNT(*) FROM (SELECT $sess s FROM page_views WHERE day >= ? GROUP BY s HAVING COUNT(*) = 1) t", [$from]);
        $avg = $one("SELECT AVG(t) FROM (SELECT SUM(dur) t FROM page_views WHERE day >= ? AND dur > 0 GROUP BY $sess) x", [$from]);
        $out['avg_seconds'] = $avg !== null && $avg !== false ? (int) round((float) $avg) : 0;
        $out['avg_today'] = (int) round((float) $one("SELECT AVG(t) FROM (SELECT SUM(dur) t FROM page_views WHERE day = ? AND dur > 0 GROUP BY $sess) x", [$today]));

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

        $out['pages'] = $q('SELECT path, COUNT(*) views, COUNT(DISTINCT visitor) visitors, ROUND(AVG(NULLIF(dur, 0))) secs FROM page_views WHERE day >= ? GROUP BY path ORDER BY views DESC LIMIT 10', [$from]);
        $out['sources'] = $q("SELECT IF(referrer = '', 'Acesso direto', referrer) src, COUNT(DISTINCT visitor) visitors FROM page_views WHERE day >= ? GROUP BY src ORDER BY visitors DESC LIMIT 8", [$from]);
        $out['devices'] = $q('SELECT device, COUNT(DISTINCT visitor) visitors FROM page_views WHERE day >= ? GROUP BY device ORDER BY visitors DESC', [$from]);

        // Cidades (só visitantes cuja cidade foi descoberta)
        $out['cities'] = $q("SELECT g.city, g.region, COUNT(DISTINCT p.visitor) visitors FROM page_views p JOIN visitor_geo g ON g.visitor = p.visitor WHERE p.day >= ? AND g.city <> '' GROUP BY g.city, g.region ORDER BY visitors DESC LIMIT 10", [$from]);
        $out['geo_known'] = (int) $one("SELECT COUNT(DISTINCT p.visitor) FROM page_views p JOIN visitor_geo g ON g.visitor = p.visitor WHERE p.day >= ? AND g.city <> ''", [$from]);

        // Botões e links mais clicados
        $out['clicks'] = $q('SELECT kind, label, COUNT(*) total, COUNT(DISTINCT visitor) people FROM site_events WHERE day >= ? GROUP BY kind, label ORDER BY total DESC LIMIT 12', [$from]);
        $out['clicks_total'] = (int) $one('SELECT COUNT(*) FROM site_events WHERE day >= ?', [$from]);
        $out['whatsapp'] = (int) $one("SELECT COUNT(*) FROM site_events WHERE day >= ? AND kind = 'whatsapp'", [$from]);
        $out['cart'] = (int) $one("SELECT COUNT(*) FROM site_events WHERE day >= ? AND kind IN ('carrinho','comprar')", [$from]);

        // Produtos mais vistos (com o nome vindo do cadastro)
        $prods = $q("SELECT SUBSTRING(path, 10) slug, COUNT(*) views FROM page_views WHERE day >= ? AND path LIKE '/produto/%' GROUP BY slug ORDER BY views DESC LIMIT 8", [$from]);
        foreach ($prods as &$p) {
            $st = $pdo->prepare('SELECT name FROM products WHERE slug = ?');
            $st->execute([$p['slug']]);
            $p['name'] = (string) ($st->fetchColumn() ?: $p['slug']);
        }
        unset($p);
        $out['products'] = $prods;

        return $out;
    }
}
