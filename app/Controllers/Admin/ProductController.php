<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Helpers;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(): void
    {
        $products = Product::query(['search' => $this->input('q')]);
        $this->render('admin/products/index', [
            'title' => 'Produtos',
            'products' => $products,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->render('admin/products/form', [
            'title' => 'Novo produto',
            'product' => null,
            'images' => [],
            'variants' => [],
            'categories' => Category::all(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->productPayload();
        $id = Product::create($data);
        $this->syncMedia($id);
        $this->syncVariants($id);
        $this->flash('success', 'Produto cadastrado.');
        $this->redirect('/admin/produtos');
    }

    public function edit(string $id): void
    {
        $product = Product::find((int) $id);
        if (!$product) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect('/admin/produtos');
        }
        $this->render('admin/products/form', [
            'title' => 'Editar produto',
            'product' => $product,
            'images' => Product::images((int) $id),
            'variants' => Product::variants((int) $id),
            'categories' => Category::all(),
        ], 'layouts/admin');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        Product::update((int) $id, $this->productPayload());
        $this->syncMedia((int) $id);
        $this->syncVariants((int) $id);
        $this->flash('success', 'Produto atualizado.');
        $this->redirect('/admin/produtos/' . $id . '/editar');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        Product::delete((int) $id);
        $this->flash('success', 'Produto excluído.');
        $this->redirect('/admin/produtos');
    }

    private function productPayload(): array
    {
        return [
            'category_id' => (int) $this->input('category_id') ?: null,
            'name' => trim((string) $this->input('name')),
            'slug' => Helpers::slug((string) $this->input('name')),
            'description' => trim((string) $this->input('description')),
            'price' => (float) str_replace(',', '.', (string) $this->input('price')),
            'compare_at_price' => $this->input('compare_at_price') !== '' ? (float) str_replace(',', '.', (string) $this->input('compare_at_price')) : null,
            'cost_price' => (float) str_replace(',', '.', (string) $this->input('cost_price', '0')),
            'is_active' => $this->input('is_active') ? 1 : 0,
            'is_featured' => $this->input('is_featured') ? 1 : 0,
            'is_new' => $this->input('is_new') ? 1 : 0,
            'is_sale' => $this->input('is_sale') ? 1 : 0,
            'is_bestseller' => $this->input('is_bestseller') ? 1 : 0,
        ];
    }

    private function syncMedia(int $productId): void
    {
        $urls = array_filter(array_map('trim', explode("\n", (string) $this->input('image_urls', ''))));
        foreach ($urls as $i => $url) {
            if ($url !== '') {
                Product::addImage($productId, $url, null, $i);
            }
        }

        if (!empty($_FILES['images']['name'][0])) {
            $dir = dirname(__DIR__, 3) . '/public/uploads/products/';
            foreach ($_FILES['images']['name'] as $i => $name) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }
                $filename = uniqid('p_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $dir . $filename)) {
                    Product::addImage($productId, '/uploads/products/' . $filename, null, $i);
                }
            }
        }
    }

    private function syncVariants(int $productId): void
    {
        $sizes = $_POST['variant_size'] ?? [];
        $colors = $_POST['variant_color'] ?? [];
        $hexes = $_POST['variant_hex'] ?? [];
        $stocks = $_POST['variant_stock'] ?? [];

        foreach ($sizes as $i => $size) {
            $size = trim((string) $size);
            $color = trim((string) ($colors[$i] ?? ''));
            if ($size === '' || $color === '') {
                continue;
            }
            Product::upsertVariant(
                $productId,
                $size,
                $color,
                $hexes[$i] ?? null,
                max(0, (int) ($stocks[$i] ?? 0))
            );
        }
    }
}
