<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Helpers;
use App\Models\Order;
use App\Services\CartService;

class CheckoutController extends Controller
{
    public function index(): void
    {
        if (!CartService::items()) {
            $this->flash('error', 'Sua sacola está vazia.');
            $this->redirect('/carrinho');
            return;
        }

        $shipping = Database::connection()->query('SELECT * FROM shipping_methods WHERE is_active = 1')->fetchAll();
        $payments = Database::connection()->query('SELECT * FROM payment_methods WHERE is_active = 1')->fetchAll();

        $this->render('checkout/index', [
            'title' => 'Finalizar pedido',
            'items' => CartService::items(),
            'totals' => CartService::totals(),
            'shippingMethods' => $shipping,
            'paymentMethods' => $payments,
            'user' => Auth::user(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        if (!CartService::items()) {
            $this->flash('error', 'Sua sacola está vazia.');
            $this->redirect('/carrinho');
        }

        $shippingId = (int) $this->input('shipping_method_id');
        $paymentId = (int) $this->input('payment_method_id');
        $pdo = Database::connection();

        $ship = $pdo->prepare('SELECT * FROM shipping_methods WHERE id = ? AND is_active = 1');
        $ship->execute([$shippingId]);
        $shipping = $ship->fetch();

        $pay = $pdo->prepare('SELECT * FROM payment_methods WHERE id = ? AND is_active = 1');
        $pay->execute([$paymentId]);
        $payment = $pay->fetch();

        if (!$shipping || !$payment) {
            $this->flash('error', 'Selecione entrega e pagamento válidos.');
            $this->redirect('/checkout');
            return;
        }

        $name = trim((string) $this->input('customer_name'));
        $phone = trim((string) $this->input('customer_phone'));
        $email = trim((string) $this->input('customer_email'));

        if ($name === '' || $phone === '') {
            $this->flash('error', 'Informe nome e telefone.');
            $this->redirect('/checkout');
            return;
        }

        $totals = CartService::totals((float) $shipping['price']);
        $coupon = $totals['coupon']['code'] ?? null;

        $orderData = [
            'user_id' => Auth::id(),
            'customer_name' => $name,
            'customer_email' => $email ?: null,
            'customer_phone' => $phone,
            'shipping_address' => trim((string) $this->input('shipping_address')),
            'shipping_city' => trim((string) $this->input('shipping_city')),
            'shipping_state' => trim((string) $this->input('shipping_state')),
            'shipping_zip' => trim((string) $this->input('shipping_zip')),
            'shipping_method_id' => $shippingId,
            'shipping_price' => (float) $shipping['price'],
            'payment_method_id' => $paymentId,
            'payment_status' => 'pending',
            'status' => 'awaiting_payment',
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'total' => $totals['total'],
            'coupon_code' => $coupon,
            'notes' => trim((string) $this->input('notes')),
        ];

        $items = [];
        foreach (CartService::items() as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'product_name' => $item['name'],
                'size' => $item['size'],
                'color' => $item['color'],
                'unit_price' => $item['price'],
                'quantity' => $item['qty'],
                'total' => $item['price'] * $item['qty'],
            ];
        }

        try {
            $orderId = Order::create($orderData, $items);
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/checkout');
            return;
        }

        $order = Order::find($orderId);
        $orderItems = Order::items($orderId);
        CartService::clear();

        $lines = ["*Novo pedido Ateliê Mariah* {$order['number']}", '', "Cliente: {$order['customer_name']}", "Telefone: {$order['customer_phone']}"];
        if ($order['customer_email']) {
            $lines[] = "E-mail: {$order['customer_email']}";
        }
        $lines[] = '';
        foreach ($orderItems as $it) {
            $lines[] = "- {$it['quantity']}x {$it['product_name']} ({$it['size']}/{$it['color']}) — " . Helpers::money($it['total']);
        }
        $lines[] = '';
        $lines[] = 'Subtotal: ' . Helpers::money($order['subtotal']);
        if ($order['discount'] > 0) {
            $lines[] = 'Desconto: -' . Helpers::money($order['discount']);
        }
        $lines[] = 'Frete: ' . Helpers::money($order['shipping_price']);
        $lines[] = '*Total: ' . Helpers::money($order['total']) . '*';
        $lines[] = 'Pagamento: ' . ($order['payment_name'] ?? '');
        $lines[] = 'Entrega: ' . ($order['shipping_name'] ?? '');
        if ($order['shipping_address']) {
            $lines[] = "Endereço: {$order['shipping_address']}, {$order['shipping_city']}-{$order['shipping_state']} {$order['shipping_zip']}";
        }

        $whatsapp = Helpers::whatsappLink(implode("\n", $lines));

        $this->render('checkout/success', [
            'title' => 'Pedido confirmado',
            'order' => $order,
            'items' => $orderItems,
            'whatsapp' => $whatsapp,
        ]);
    }
}
