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
        $data['slug'] = $this->uniqueSlug($data['slug']);
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
        $current = Product::find((int) $id);
        if (!$current) {
            $this->flash('error', 'Produto não encontrado.');
            $this->redirect('/admin/produtos');
        }
        $data = $this->productPayload();
        $data['slug'] = $current['slug'];
        Product::update((int) $id, $data);
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

    public function deleteImage(string $id, string $imageId): void
    {
        $this->verifyCsrf('/admin/produtos/' . (int) $id . '/editar');
        $image = Product::deleteImage((int) $id, (int) $imageId);
        if ($image) {
            $this->removeUploadedFile($image['path']);
            $this->flash('success', 'Foto removida.');
        } else {
            $this->flash('error', 'Foto não encontrada.');
        }
        $this->redirect('/admin/produtos/' . (int) $id . '/editar');
    }

    public function primaryImage(string $id, string $imageId): void
    {
        $this->verifyCsrf('/admin/produtos/' . (int) $id . '/editar');
        Product::makePrimaryImage((int) $id, (int) $imageId);
        $this->flash('success', 'Foto definida como capa.');
        $this->redirect('/admin/produtos/' . (int) $id . '/editar');
    }

    private function uniqueSlug(string $slug): string
    {
        $candidate = $slug;
        $n = 2;
        while (Product::findBySlug($candidate)) {
            $candidate = $slug . '-' . $n++;
        }
        return $candidate;
    }

    /** Apaga o arquivo só se ele estiver em uploads/products e não for usado por outra foto. */
    private function removeUploadedFile(string $path): void
    {
        if (!str_starts_with($path, '/uploads/products/') || Product::imagePathInUse($path)) {
            return;
        }
        $file = dirname(__DIR__, 3) . '/public' . $path;
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function syncMedia(int $productId): void
    {
        $urls = array_filter(array_map('trim', explode("
", (string) $this->input('image_urls', ''))));
        $sort = Product::nextImageSort($productId);
        foreach ($urls as $url) {
            if (preg_match('#^(https?://|/uploads/)#i', $url)) {
                Product::addImage($productId, $url, null, $sort++);
            }
        }

        if (!empty($_FILES['images']['name'][0])) {
            $dir = dirname(__DIR__, 3) . '/public/uploads/products/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            foreach ($_FILES['images']['name'] as $i => $name) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }
                $tmp = $_FILES['images']['tmp_name'][$i];
                if ($_FILES['images']['size'][$i] > 8 * 1024 * 1024 || @getimagesize($tmp) === false) {
                    continue;
                }
                $filename = uniqid('p_', true) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                if (move_uploaded_file($tmp, $dir . $filename)) {
                    Product::addImage($productId, '/uploads/products/' . $filename, null, $sort++);
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

        foreach ($sizes as $i => $sizeField) {
            $color = trim((string) ($colors[$i] ?? ''));
            // Aceita vários tamanhos no mesmo campo ("P, M, G" / "P M G" / "P/M/G").
            $list = preg_split('/[\s,;\/|]+/', trim((string) $sizeField), -1, PREG_SPLIT_NO_EMPTY);
            if (!$list || $color === '') {
                continue;
            }
            foreach (array_unique(array_map('mb_strtoupper', $list)) as $size) {
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
}
