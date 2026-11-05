<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Helpers;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index(): void
    {
        $featured = Product::query([
            'active_only' => true,
            'featured' => true,
            'sort' => 'newest',
            'limit' => 8,
        ]);
        $featuredIds = array_map(fn($p) => (int) $p['id'], $featured);

        $news = Product::query([
            'active_only' => true,
            'is_new' => true,
            'exclude_ids' => $featuredIds,
            'sort' => 'newest',
            'limit' => 8,
        ]);
        $newsIds = array_merge($featuredIds, array_map(fn($p) => (int) $p['id'], $news));

        $bestsellers = Product::query([
            'active_only' => true,
            'is_bestseller' => true,
            'exclude_ids' => $newsIds,
            'sort' => 'bestsellers',
            'limit' => 8,
        ]);
        if (count($bestsellers) < 4) {
            $bestsellers = Product::query([
                'active_only' => true,
                'exclude_ids' => $newsIds,
                'sort' => 'bestsellers',
                'limit' => 8,
            ]);
        }

        $sale = Product::query([
            'active_only' => true,
            'is_sale' => true,
            'sort' => 'newest',
            'limit' => 8,
        ]);

        $pdoCats = Database::connection();
        $categories = [];
        foreach (Category::allActive() as $c) {
            if (in_array($c['slug'], ['novidades', 'promocoes', 'jeans', 'acessorios'], true)) {
                continue;
            }
            if ($c['slug'] === 'colecao-atelier') {
                $categories[] = $c;
                continue;
            }
            $stmt = $pdoCats->prepare('SELECT COUNT(*) FROM products WHERE is_active = 1 AND category_id = ?');
            $stmt->execute([(int) $c['id']]);
            if ((int) $stmt->fetchColumn() > 0) {
                $categories[] = $c;
            }
        }

        $pdo = Database::connection();
        $reviews = $pdo->query('SELECT * FROM reviews WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 6')->fetchAll();
        $feed = json_decode((string) Helpers::setting('instagram_feed', '[]'), true) ?: [];

        $this->render('home/index', [
            'title' => Helpers::setting('store_name', 'Mandalla'),
            'meta_description' => Helpers::setting('tagline', 'Moda feminina premium'),
            'featured' => $featured,
            'news' => $news,
            'bestsellers' => $bestsellers,
            'sale' => $sale,
            'categories' => $categories,
            'reviews' => $reviews,
            'instagram' => $feed,
        ]);
    }
}
