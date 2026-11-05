<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug): void
    {
        $product = Product::findBySlug($slug);
        if (!$product || !(int) $product['is_active']) {
            http_response_code(404);
            $this->render('pages/404', ['title' => 'Produto não encontrado']);
            return;
        }

        $images = Product::images((int) $product['id']);
        $variants = Product::variants((int) $product['id']);
        $related = Product::related((int) $product['id'], $product['category_id'] ? (int) $product['category_id'] : null);
        $defaultVariant = Product::firstAvailableVariant($variants);

        $sizes = [];
        foreach ($variants as $v) {
            if (!in_array($v['size'], $sizes, true)) {
                $sizes[] = $v['size'];
            }
        }
        $colors = [];
        foreach ($variants as $v) {
            $colors[$v['color']] = $v['color_hex'];
        }

        $this->render('product/show', [
            'title' => $product['name'],
            'meta_description' => mb_substr(strip_tags((string) $product['description']), 0, 160),
            'product' => $product,
            'images' => $images,
            'variants' => $variants,
            'sizes' => $sizes,
            'colors' => $colors,
            'defaultVariant' => $defaultVariant,
            'related' => $related,
            'og_image' => $images[0]['path'] ?? null,
        ]);
    }
}
