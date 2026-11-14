<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Helpers;

class SettingsController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();
        $settings = [];
        foreach ($pdo->query('SELECT `key`, value FROM settings') as $row) {
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
        foreach ($keys as $key) {
            if ($this->input($key) !== null) {
                Helpers::saveSetting($key, trim((string) $this->input($key)));
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
        $err = $_FILES['hero_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if (empty($_FILES['hero_file']['name']) || $err !== UPLOAD_ERR_OK) {
            $msg = in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'A foto e muito grande para o servidor. Diminua a imagem (ate uns 5 MB) e tente de novo.'
                : 'Selecione uma imagem JPG, PNG ou WebP.';
            $this->flash('error', $msg);
            $this->redirect('/admin/configuracoes');
        }

        $tmp = $_FILES['hero_file']['tmp_name'];
        $info = @getimagesize($tmp);
        $type = $info[2] ?? 0;
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG => @imagecreatefrompng($tmp),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default => false,
        };
        if (!$src) {
            $this->flash('error', 'Nao consegui ler essa imagem. Use JPG, PNG ou WebP.');
            $this->redirect('/admin/configuracoes');
        }

        $dir = dirname(__DIR__, 3) . '/public/uploads/moda/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Nome novo a cada envio: evita o navegador/servidor mostrarem a capa antiga em cache.
        $base = 'hero-' . date('YmdHis');
        $sw = imagesx($src);
        $sh = imagesy($src);
        $tw = min(2560, $sw);
        $th = (int) round($sh * ($tw / max(1, $sw)));
        $out = imagecreatetruecolor($tw, $th);
        imagecopyresampled($out, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh);
        imagedestroy($src);

        $ok = imagejpeg($out, $dir . $base . '.jpg', 90);
        if (function_exists('imagewebp')) {
            @imagewebp($out, $dir . $base . '.webp', 86);
        }
        imagedestroy($out);

        if (!$ok) {
            $this->flash('error', 'Nao foi possivel salvar a imagem.');
            $this->redirect('/admin/configuracoes');
        }

        $old = Helpers::setting('hero_image');
        Helpers::saveSetting('hero_image', '/uploads/moda/' . $base . '.jpg');

        // Apaga capas antigas enviadas pelo painel (nunca a original do tema).
        if (is_string($old) && preg_match('#^/uploads/moda/hero-\d{14}\.jpg$#', $old)) {
            @unlink($dir . basename($old));
            @unlink($dir . basename($old, '.jpg') . '.webp');
        }

        $this->flash('success', 'Capa do site atualizada.');
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
