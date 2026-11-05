<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Product;

class StockController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();
        $rows = $pdo->query(
            'SELECT v.*, p.name AS product_name FROM product_variants v
             JOIN products p ON p.id = v.product_id ORDER BY p.name, v.size, v.color'
        )->fetchAll();
        $movements = $pdo->query(
            'SELECT m.*, v.size, v.color, p.name AS product_name
             FROM stock_movements m
             JOIN product_variants v ON v.id = m.variant_id
             JOIN products p ON p.id = v.product_id
             ORDER BY m.created_at DESC LIMIT 50'
        )->fetchAll();

        $this->render('admin/stock/index', [
            'title' => 'Estoque',
            'rows' => $rows,
            'movements' => $movements,
            'lowStock' => Product::lowStock(),
        ], 'layouts/admin');
    }

    public function adjust(): void
    {
        $this->verifyCsrf();
        $variantId = (int) $this->input('variant_id');
        $qty = (int) $this->input('quantity');
        $type = $this->input('type') === 'out' ? 'out' : 'in';
        $note = trim((string) $this->input('note', 'Reposição manual'));

        $pdo = Database::connection();
        $variant = Product::findVariant($variantId);
        if (!$variant) {
            $this->flash('error', 'Variação não encontrada.');
            $this->redirect('/admin/estoque');
        }

        if ($type === 'out' && $qty > (int) $variant['stock']) {
            $this->flash('error', 'Estoque insuficiente. Operação bloqueada.');
            $this->redirect('/admin/estoque');
        }

        $newStock = $type === 'in'
            ? (int) $variant['stock'] + $qty
            : (int) $variant['stock'] - $qty;

        $pdo->prepare('UPDATE product_variants SET stock = ? WHERE id = ?')->execute([$newStock, $variantId]);
        $pdo->prepare('INSERT INTO stock_movements (variant_id, type, quantity, note, user_id) VALUES (?, ?, ?, ?, ?)')
            ->execute([$variantId, $type, $qty, $note, Auth::id()]);

        $this->flash('success', 'Estoque atualizado.');
        $this->redirect('/admin/estoque');
    }
}
