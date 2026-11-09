<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class SettingsController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();
        $settings = [];
        foreach ($pdo->query('SELECT key, value FROM settings') as $row) {
            $settings[$row['key']] = $row['value'];
        }

        $this->render('admin/settings/index', [
            'title' => 'Configurações',
            'settings' => $settings,
            'categories' => $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll(),
            'coupons' => $pdo->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll(),
            'shipping' => $pdo->query('SELECT * FROM shipping_methods ORDER BY id')->fetchAll(),
            'payments' => $pdo->query('SELECT * FROM payment_methods ORDER BY id')->fetchAll(),
        ], 'layouts/admin');
    }

    public function save(): void
    {
        $this->verifyCsrf();
        $keys = [
            'store_name', 'tagline', 'whatsapp', 'instagram', 'email', 'phone', 'address',
            'hero_title', 'hero_subtitle', 'hero_image', 'og_image',
            'exchange_policy', 'privacy_policy', 'delivery_info',
        ];
        $stmt = Database::connection()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
        foreach ($keys as $key) {
            if ($this->input($key) !== null) {
                $stmt->execute([$key, trim((string) $this->input($key))]);
            }
        }
        $this->flash('success', 'Configurações salvas.');
        $this->redirect('/admin/configuracoes');
    }

    public function saveCategory(): void
    {
        $this->verifyCsrf();
        $id = (int) $this->input('id');
        $data = [
            'name' => trim((string) $this->input('name')),
            'image' => trim((string) $this->input('image')),
            'description' => trim((string) $this->input('description')),
            'is_active' => $this->input('is_active') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
        ];
        if ($id) {
            \App\Models\Category::update($id, $data);
        } else {
            \App\Models\Category::create($data);
        }
        $this->flash('success', 'Categoria salva.');
        $this->redirect('/admin/configuracoes');
    }

    public function saveCoupon(): void
    {
        $this->verifyCsrf();
        $code = strtoupper(trim((string) $this->input('code')));
        $type = $this->input('type') === 'fixed' ? 'fixed' : 'percent';
        $value = (float) str_replace(',', '.', (string) $this->input('value'));

        if ($code === '') {
            $this->flash('error', 'Informe o código do cupom.');
            $this->redirect('/admin/configuracoes');
        }
        if ($value <= 0 || ($type === 'percent' && $value > 100)) {
            $this->flash('error', 'Valor inválido: percentual deve ficar entre 0 e 100.');
            $this->redirect('/admin/configuracoes');
        }

        try {
            Database::connection()->prepare(
                'INSERT INTO coupons (code, type, value, min_subtotal, is_active, expires_at) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $code,
                $type,
                $value,
                (float) $this->input('min_subtotal', 0),
                $this->input('is_active') ? 1 : 0,
                $this->input('expires_at') ?: null,
            ]);
        } catch (\PDOException $e) {
            $this->flash('error', 'Já existe um cupom com este código.');
            $this->redirect('/admin/configuracoes');
        }

        $this->flash('success', 'Cupom criado.');
        $this->redirect('/admin/configuracoes');
    }

    public function toggleCoupon(string $id): void
    {
        $this->verifyCsrf('/admin/configuracoes');
        Database::connection()->prepare('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?')->execute([(int) $id]);
        $this->flash('success', 'Status do cupom alterado.');
        $this->redirect('/admin/configuracoes');
    }

    public function deleteCoupon(string $id): void
    {
        $this->verifyCsrf('/admin/configuracoes');
        Database::connection()->prepare('DELETE FROM coupons WHERE id = ?')->execute([(int) $id]);
        $this->flash('success', 'Cupom excluído.');
        $this->redirect('/admin/configuracoes');
    }

    public function uploadHero(): void
    {
        $this->verifyCsrf();
        if (empty($_FILES['hero_file']['name']) || ($_FILES['hero_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Selecione uma imagem JPG, PNG ou WebP.');
            $this->redirect('/admin/configuracoes');
        }

        $ext = strtolower(pathinfo((string) $_FILES['hero_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $this->flash('error', 'Formato não suportado. Use JPG, PNG ou WebP.');
            $this->redirect('/admin/configuracoes');
        }

        $dir = dirname(__DIR__, 3) . '/public/uploads/moda/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $base = 'hero-atelier';
        $filename = $ext === 'jpeg' ? $base . '.jpg' : $base . '.' . ($ext === 'jpg' ? 'jpg' : $ext);
        $dest = $dir . $filename;
        if (!move_uploaded_file($_FILES['hero_file']['tmp_name'], $dest)) {
            $this->flash('error', 'Não foi possível salvar a imagem.');
            $this->redirect('/admin/configuracoes');
        }

        if ($ext === 'png' || $ext === 'webp') {
            $jpgPath = $dir . $base . '.jpg';
            $this->convertHeroToJpeg($dest, $jpgPath, $ext);
            if (is_file($jpgPath)) {
                $filename = $base . '.jpg';
                if ($dest !== $jpgPath && is_file($dest)) {
                    @unlink($dest);
                }
            }
        }

        $publicPath = '/uploads/moda/' . $filename;
        Database::connection()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)')
            ->execute(['hero_image', $publicPath]);

        require_once dirname(__DIR__, 3) . '/database/build_hero_assets.php';
        mariah_rebuild_hero_assets(dirname(__DIR__, 3));

        $this->flash('success', 'Banner da home atualizado.');
        $this->redirect('/admin/configuracoes');
    }

    private function convertHeroToJpeg(string $source, string $jpgPath, string $ext): void
    {
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($source);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($source);
        } else {
            return;
        }
        if (!$img) {
            return;
        }
        imagejpeg($img, $jpgPath, 92);
        imagedestroy($img);
    }
}
