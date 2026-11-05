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
        ]);
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
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: " . Helpers::setting('store_url', 'http://localhost:8080') . "/sitemap.xml\n";
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim((string) (require dirname(__DIR__, 2) . '/config/app.php')['url'], '/');
        $products = Database::connection()->query('SELECT slug, updated_at FROM products WHERE is_active = 1')->fetchAll();
        $cats = Database::connection()->query('SELECT slug FROM categories WHERE is_active = 1')->fetchAll();

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (['/', '/catalogo', '/carrinho'] as $path) {
            echo '<url><loc>' . htmlspecialchars($base . $path) . '</loc></url>';
        }
        foreach ($cats as $c) {
            echo '<url><loc>' . htmlspecialchars($base . '/categoria/' . $c['slug']) . '</loc></url>';
        }
        foreach ($products as $p) {
            echo '<url><loc>' . htmlspecialchars($base . '/produto/' . $p['slug']) . '</loc><lastmod>' . substr($p['updated_at'], 0, 10) . '</lastmod></url>';
        }
        echo '</urlset>';
    }
}
