<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Helpers;
use App\Models\Product;

class HomeController extends Controller
{
    public function index(): void
    {
        $collection = Product::query([
            'active_only' => true,
            'category_slug' => 'colecao-atelier',
            'sort' => 'newest',
            'limit' => 12,
        ]);
        if ($collection === []) {
            $collection = Product::query([
                'active_only' => true,
                'sort' => 'newest',
                'limit' => 12,
            ]);
        }

        $sale = Product::query([
            'active_only' => true,
            'is_sale' => true,
            'sort' => 'newest',
            'limit' => 8,
        ]);

        $pdo = Database::connection();
        $reviews = $pdo->query('SELECT * FROM reviews WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 3')->fetchAll();
        $feed = json_decode((string) Helpers::setting('instagram_feed', '[]'), true) ?: [];

        $this->render('home/index', [
            'title' => Helpers::setting('store_name', 'Ateliê Mariah'),
            'meta_description' => Helpers::setting('tagline', 'Vestidos de festa exclusivos — formatura, debutante e eventos'),
            'og_title' => Helpers::setting('store_name', 'Ateliê Mariah'),
            'og_image' => Helpers::setting('og_image', '/assets/brand/og-share.jpg'),
            'storeEmpty' => Product::count(['active_only' => true]) === 0,
            'collection' => $collection,
            'sale' => $sale,
            'reviews' => $reviews,
            'instagram' => $feed,
        ]);
    }
}
