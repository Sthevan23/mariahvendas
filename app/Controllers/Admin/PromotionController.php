<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Category;

class PromotionController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();
        $products = $pdo->query(
            'SELECT p.id, p.name, p.price, p.compare_at_price, p.is_sale, p.is_active, p.category_id, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.is_sale DESC, p.name ASC'
        )->fetchAll();

        $this->render('admin/promotions/index', [
            'title' => 'Promoções',
            'products' => $products,
            'categories' => Category::all(),
        ], 'layouts/admin');
    }

    /**
     * Aplica desconto percentual. O preço atual vira "preço de" (riscado) e o novo preço
     * fica com o desconto. Se o produto já estiver em promoção, o desconto parte do preço "de".
     */
    public function apply(): void
    {
        $this->verifyCsrf('/admin/promocoes');

        $percent = (float) str_replace(',', '.', (string) $this->input('percent', '0'));
        if ($percent <= 0 || $percent >= 90) {
            $this->flash('error', 'Informe um desconto entre 1% e 89%.');
            $this->redirect('/admin/promocoes');
        }

        $pdo = Database::connection();
        $scope = (string) $this->input('scope', 'selected');
        $ids = [];

        if ($scope === 'all') {
            $ids = $pdo->query('SELECT id FROM products')->fetchAll(\PDO::FETCH_COLUMN);
        } elseif ($scope === 'category') {
            $stmt = $pdo->prepare('SELECT id FROM products WHERE category_id = ?');
            $stmt->execute([(int) $this->input('category_id')]);
            $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $ids = array_map('intval', (array) ($_POST['product_ids'] ?? []));
        }

        $select = $pdo->prepare('SELECT id, price, compare_at_price, is_sale FROM products WHERE id = ?');
        $update = $pdo->prepare('UPDATE products SET price = ?, compare_at_price = ?, is_sale = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?');

        $count = 0;
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            $select->execute([$id]);
            $p = $select->fetch();
            if (!$p) {
                continue;
            }
            $base = ((int) $p['is_sale'] === 1 && (float) $p['compare_at_price'] > (float) $p['price'])
                ? (float) $p['compare_at_price']
                : (float) $p['price'];
            $newPrice = round($base * (1 - $percent / 100), 2);
            $update->execute([$newPrice, round($base, 2), $id]);
            $count++;
        }

        $this->flash('success', $count > 0
            ? "Promoção de {$percent}% aplicada em {$count} produto(s)."
            : 'Nenhum produto selecionado.');
        $this->redirect('/admin/promocoes');
    }

    /** Encerra promoções: volta ao preço "de" e tira a tag de promoção. */
    public function end(): void
    {
        $this->verifyCsrf('/admin/promocoes');
        $pdo = Database::connection();

        $restore = $pdo->prepare(
            'UPDATE products SET price = compare_at_price, compare_at_price = NULL, is_sale = 0, updated_at = CURRENT_TIMESTAMP
             WHERE is_sale = 1 AND compare_at_price > price'
        );
        $restore->execute();
        $restored = $restore->rowCount();

        $pdo->prepare(
            'UPDATE products SET is_sale = 0, updated_at = CURRENT_TIMESTAMP WHERE is_sale = 1'
        )->execute();

        $this->flash('success', "Promoções encerradas ({$restored} produto(s) voltaram ao preço normal).");
        $this->redirect('/admin/promocoes');
    }
}
