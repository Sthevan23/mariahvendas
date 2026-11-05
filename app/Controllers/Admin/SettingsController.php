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
            'hero_title', 'hero_subtitle', 'hero_image', 'exchange_policy', 'privacy_policy', 'delivery_info',
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
        $pdo = Database::connection();
        $pdo->prepare(
            'INSERT INTO coupons (code, type, value, min_subtotal, is_active, expires_at) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            strtoupper(trim((string) $this->input('code'))),
            $this->input('type') === 'fixed' ? 'fixed' : 'percent',
            (float) $this->input('value'),
            (float) $this->input('min_subtotal', 0),
            $this->input('is_active') ? 1 : 0,
            $this->input('expires_at') ?: null,
        ]);
        $this->flash('success', 'Cupom criado.');
        $this->redirect('/admin/configuracoes');
    }
}
