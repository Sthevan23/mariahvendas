<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Order;

class OrderController extends Controller
{
    public function index(): void
    {
        $status = $this->input('status') ?: null;
        $this->render('admin/orders/index', [
            'title' => 'Pedidos',
            'orders' => Order::all($status),
            'status' => $status,
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $order = Order::find((int) $id);
        if (!$order) {
            $this->flash('error', 'Pedido não encontrado.');
            $this->redirect('/admin/pedidos');
        }
        $this->render('admin/orders/show', [
            'title' => 'Pedido ' . $order['number'],
            'order' => $order,
            'items' => Order::items((int) $id),
            'history' => Order::history((int) $id),
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): void
    {
        $this->verifyCsrf();
        $status = (string) $this->input('status');
        $note = trim((string) $this->input('note'));
        $payment = null;

        if ($status === 'payment_approved') {
            $payment = 'paid';
        } elseif ($status === 'cancelled') {
            $payment = 'cancelled';
        }

        Order::updateStatus((int) $id, $status, $note ?: null, Auth::id(), $payment);
        $this->flash('success', 'Status atualizado.');
        $this->redirect('/admin/pedidos/' . $id);
    }
}
