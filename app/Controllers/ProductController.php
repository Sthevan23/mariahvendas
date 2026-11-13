<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;
use App\Models\Product;

class ProductController extends Controller
{
    /** Dados estruturados (schema.org) para o Google mostrar preço e disponibilidade. */
    private function productJsonLd(array $product, array $images, array $variants): array
    {
        $inStock = false;
        foreach ($variants as $v) {
            if ((int) $v['stock'] > 0) {
                $inStock = true;
                break;
            }
        }
        $photos = array_map(fn($i) => Helpers::absoluteUrl($i['path']), array_slice($images, 0, 5));
        $colors = array_values(array_unique(array_map(fn($v) => $v['color'], $variants)));
        $url = Helpers::absoluteUrl('/produto/' . $product['slug']);
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'],
            'description' => mb_substr(strip_tags((string) $product['description']), 0, 300) ?: $product['name'],
            'url' => $url,
            'brand' => ['@type' => 'Brand', 'name' => (string) Helpers::setting('store_name', 'Ateliê Mariah')],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'BRL',
                'price' => number_format((float) $product['price'], 2, '.', ''),
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $url,
            ],
        ];
        if ($photos) {
            $data['image'] = $photos;
        }
        if ($colors) {
            $data['color'] = implode(', ', $colors);
        }
        return $data;
    }

    public function show(string $slug): void
    {
        $product = Product::findBySlug($slug);
        if (!$product || !(int) $product['is_active']) {
            http_response_code(404);
            $this->render('pages/404', ['title' => 'Produto não encontrado']);
            return;
        }

        $images = Product::images((int) $product['id'], true);
        $variants = Product::variants((int) $product['id']);
        $related = Product::related((int) $product['id'], $product['category_id'] ? (int) $product['category_id'] : null);
        $defaultVariant = Product::firstAvailableVariant($variants);

        $sizes = [];
        foreach ($variants as $v) {
            if (!in_array($v['size'], $sizes, true)) {
                $sizes[] = $v['size'];
            }
        }
        // Vestidos em tamanhos de letra mostram a grade completa; o que não tem estoque aparece riscado.
        $standardSizes = ['PP', 'P', 'M', 'G', 'GG'];
        if ($sizes && !array_diff($sizes, array_merge($standardSizes, ['XG', 'XGG']))) {
            $sizes = array_values(array_unique(array_merge($standardSizes, $sizes)));
            usort($sizes, static fn($a, $b) => (array_search($a, ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG']) ?: 0) <=> (array_search($b, ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG']) ?: 0));
        }
        $colors = [];
        foreach ($variants as $v) {
            $colors[$v['color']] = Helpers::swatchHex($v['color'], $v['color_hex']);
        }

        $this->render('product/show', [
            'title' => $product['name'],
            'meta_description' => mb_substr(trim($product['name'] . ' — ' . strip_tags((string) $product['description'])), 0, 160),
            'og_type' => 'product',
            'jsonld' => $this->productJsonLd($product, $images, $variants),
            'product' => $product,
            'images' => $images,
            'variants' => $variants,
            'sizes' => $sizes,
            'colors' => $colors,
            'defaultVariant' => $defaultVariant,
            'related' => $related,
            'og_image' => $images[0]['path'] ?? Helpers::setting('og_image', '/assets/brand/og-share.jpg'),
        ]);
    }
}
