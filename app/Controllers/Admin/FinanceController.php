<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Order;

class FinanceController extends Controller
{
    public function index(): void
    {
        $from = $this->input('from', date('Y-m-01'));
        $to = $this->input('to', date('Y-m-d'));

        $revenue = Order::revenue($from, $to);
        $pdo = Database::connection();

        $exp = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE date(expense_date) BETWEEN date(?) AND date(?)');
        $exp->execute([$from, $to]);
        $expensesTotal = (float) $exp->fetchColumn();

        $cogs = $pdo->prepare(
            "SELECT COALESCE(SUM(p.cost_price * oi.quantity),0)
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE o.payment_status = 'paid' AND date(o.created_at) BETWEEN date(?) AND date(?)"
        );
        $cogs->execute([$from, $to]);
        $cost = (float) $cogs->fetchColumn();

        $margins = $pdo->query(
            "SELECT p.name, p.price, p.cost_price,
                    CASE WHEN p.price > 0 THEN ROUND(((p.price - p.cost_price) / p.price) * 100, 1) ELSE 0 END AS margin
             FROM products p ORDER BY margin DESC"
        )->fetchAll();

        $expenses = $pdo->prepare('SELECT * FROM expenses WHERE date(expense_date) BETWEEN date(?) AND date(?) ORDER BY expense_date DESC');
        $expenses->execute([$from, $to]);

        $this->render('admin/finance/index', [
            'title' => 'Financeiro',
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'expensesTotal' => $expensesTotal,
            'cost' => $cost,
            'profit' => $revenue - $cost - $expensesTotal,
            'margins' => $margins,
            'expenses' => $expenses->fetchAll(),
            'chart' => Order::salesByDay(30),
        ], 'layouts/admin');
    }

    public function storeExpense(): void
    {
        $this->verifyCsrf();
        $stmt = Database::connection()->prepare(
            'INSERT INTO expenses (description, amount, category, expense_date) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            trim((string) $this->input('description')),
            (float) str_replace(',', '.', (string) $this->input('amount')),
            trim((string) $this->input('category')),
            $this->input('expense_date', date('Y-m-d')),
        ]);
        $this->flash('success', 'Despesa registrada.');
        $this->redirect('/admin/financeiro');
    }

    public function export(): void
    {
        $from = $this->input('from', date('Y-m-01'));
        $to = $this->input('to', date('Y-m-d'));
        $orders = Database::connection()->prepare(
            "SELECT number, customer_name, total, payment_status, status, created_at
             FROM orders WHERE payment_status = 'paid' AND date(created_at) BETWEEN date(?) AND date(?)
             ORDER BY created_at"
        );
        $orders->execute([$from, $to]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=financeiro-mandalla.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Pedido', 'Cliente', 'Total', 'Pagamento', 'Status', 'Data'], ';');
        foreach ($orders as $row) {
            fputcsv($out, [$row['number'], $row['customer_name'], $row['total'], $row['payment_status'], $row['status'], $row['created_at']], ';');
        }
        fclose($out);
    }
}
