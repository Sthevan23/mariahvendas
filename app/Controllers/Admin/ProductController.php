<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Helpers;
use App\Models\Category;
use App\Models\Product;
use App\Services\MediaStore;

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
        $uploadErrors = $this->syncMedia($id);
        $this->syncVariants($id);
        Product::saveMeasures($id, trim((string) $this->input('measures')));
        $this->flashSaved('Produto cadastrado.', $uploadErrors);
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
        $uploadErrors = $this->syncMedia((int) $id);
        $this->syncVariants((int) $id);
        Product::saveMeasures((int) $id, trim((string) $this->input('measures')));
        $this->flashSaved('Produto atualizado.', $uploadErrors);
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

    /** Apaga o arquivo só se ele for um upload do admin e não for usado por outra foto. */
    private function removeUploadedFile(string $path): void
    {
        if (MediaStore::isMediaPath($path)) {
            if (!Product::imagePathInUse($path)) {
                MediaStore::delete($path);
            }
            return;
        }
        if (!str_starts_with($path, '/uploads/products/') || Product::imagePathInUse($path)) {
            return;
        }
        $file = dirname(__DIR__, 3) . '/public' . $path;
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /** @return string[] erros de upload, um por foto */
    private function syncMedia(int $productId): array
    {
        $urls = array_filter(array_map('trim', explode("
", (string) $this->input('image_urls', ''))));
        $sort = Product::nextImageSort($productId);
        foreach ($urls as $url) {
            if (preg_match('#^(https?://|/uploads/|/media/)#i', $url)) {
                Product::addImage($productId, $url, null, $sort++);
            }
        }

        $errors = [];
        foreach ($_FILES['images']['name'] ?? [] as $i => $name) {
            if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $result = MediaStore::saveUpload([
                'name' => $name,
                'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
                'size' => $_FILES['images']['size'][$i] ?? 0,
                'error' => $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            ]);
            if ($result['ok']) {
                Product::addImage($productId, $result['path'], null, $sort++);
            } else {
                $errors[] = $name . ': ' . $result['error'];
            }
        }
        return $errors;
    }

    /** Mensagem de sucesso, ou aviso se alguma foto não subiu. */
    private function flashSaved(string $okMessage, array $uploadErrors): void
    {
        if ($uploadErrors) {
            $this->flash('error', $okMessage . ' Mas estas fotos não subiram — ' . implode('; ', $uploadErrors) . '.');
        } else {
            $this->flash('success', $okMessage);
        }
    }

    private function syncVariants(int $productId): void
    {
        $sizes = $_POST['variant_size'] ?? [];
        $colors = $_POST['variant_color'] ?? [];
        $hexes = $_POST['variant_hex'] ?? [];
        $stocks = $_POST['variant_stock'] ?? [];

        $keep = [];
        foreach ($sizes as $i => $sizeField) {
            $color = trim((string) ($colors[$i] ?? ''));
            // Aceita vários tamanhos no mesmo campo ("P, M, G" / "P M G" / "P/M/G").
            $list = preg_split('/[\s,;\/|]+/', trim((string) $sizeField), -1, PREG_SPLIT_NO_EMPTY);
            if (!$list || $color === '') {
                continue;
            }
            foreach (array_unique(array_map('mb_strtoupper', $list)) as $size) {
                $hex = trim((string) ($hexes[$i] ?? ''));
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
                    $hex = Helpers::colorHex($color);
                }
                $keep[] = Product::upsertVariant(
                    $productId,
                    $size,
                    $color,
                    $hex,
                    max(0, (int) ($stocks[$i] ?? 0))
                );
            }
        }

        // Variação que saiu do formulário (cor removida) some do site.
        // Só apaga se o formulário enviou variações, para nunca zerar o produto por engano.
        if ($keep) {
            Product::deleteVariantsExcept($productId, $keep);
        }
    }
}
