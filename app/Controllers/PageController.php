<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Helpers;

class PageController extends Controller
{
    public function newsletter(): void
    {
        $this->verifyCsrf();
        $email = trim((string) $this->input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Informe um e-mail válido.');
            $this->back();
        }

        try {
            $stmt = Database::connection()->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?)');
            $stmt->execute([$email]);
            $this->flash('success', 'Inscrição realizada. Você receberá novidades do Ateliê Mariah.');
        } catch (\Throwable $e) {
            $this->flash('success', 'Este e-mail já está na nossa lista.');
        }
        $this->back();
    }

    public function privacy(): void
    {
        $this->render('pages/policy', [
            'title' => 'Privacidade',
            'heading' => 'Política de privacidade',
            'body' => Helpers::setting('privacy_policy'),
            'extra' => "Medimos visitas, tempo de navegação e cliques de forma anônima e identificamos a cidade aproximada, sem guardar o IP.",
        ]);
    }

    /** Página de links para a bio do Instagram/Pinterest (sem o layout da loja). */
    public function links(): void
    {
        $this->render('pages/links', [], null);
    }

    public function exchange(): void
    {
        $this->render('pages/policy', [
            'title' => 'Trocas',
            'heading' => 'Política de trocas',
            'body' => Helpers::setting('exchange_policy'),
        ]);
    }

    public function robots(): void
    {
        header('Content-Type: text/plain');
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /busca\nDisallow: /favoritos\nDisallow: /cadastro\nDisallow: /carrinho\nDisallow: /checkout\nDisallow: /conta\nDisallow: /login\nSitemap: " . Helpers::appUrl() . "/sitemap.xml\n";
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim((string) (require dirname(__DIR__, 2) . '/config/app.php')['url'], '/');
        $products = Database::connection()->query('SELECT slug, updated_at FROM products WHERE is_active = 1')->fetchAll();
        $cats = Database::connection()->query('SELECT slug FROM categories WHERE is_active = 1')->fetchAll();

        $img = Database::connection()->query('SELECT p.slug, pi.path FROM product_images pi JOIN products p ON p.id = pi.product_id WHERE p.is_active = 1 ORDER BY pi.id')->fetchAll();
        $imgs = [];
        foreach ($img as $i) {
            if (count($imgs[$i['slug']] ?? []) < 5) {
                $imgs[$i['slug']][] = $i['path'];
            }
        }
        $abs = static fn(string $p): string => preg_match('#^https?://#', $p) ? $p : $base . '/' . ltrim($p, '/');

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
        foreach (['/' => '1.0', '/catalogo' => '0.9', '/links' => '0.4', '/trocas' => '0.3', '/privacidade' => '0.2'] as $path => $prio) {
            echo '<url><loc>' . htmlspecialchars($base . $path) . '</loc><changefreq>weekly</changefreq><priority>' . $prio . '</priority></url>';
        }
        foreach ($cats as $c) {
            echo '<url><loc>' . htmlspecialchars($base . '/categoria/' . $c['slug']) . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>';
        }
        foreach ($products as $p) {
            echo '<url><loc>' . htmlspecialchars($base . '/produto/' . $p['slug']) . '</loc><lastmod>' . substr((string) $p['updated_at'], 0, 10) . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority>';
            foreach ($imgs[$p['slug']] ?? [] as $path) {
                echo '<image:image><image:loc>' . htmlspecialchars($abs($path)) . '</image:loc></image:image>';
            }
            echo '</url>';
        }
        echo '</urlset>';
    }
}
