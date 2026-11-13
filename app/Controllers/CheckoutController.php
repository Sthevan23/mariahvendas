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
        if ($warnings = CartService::reconcileStock()) {
            $this->flash('error', implode(' ', $warnings));
            $this->redirect('/carrinho');
            return;
        }
        if (!CartService::items()) {
            $this->flash('error', 'Sua sacola está vazia.');
            $this->redirect('/carrinho');
            return;
        }

        $shipping = self::shippingEnabled()
            ? Database::connection()->query('SELECT * FROM shipping_methods WHERE is_active = 1')->fetchAll()
            : [];
        $payments = Database::connection()->query('SELECT * FROM payment_methods WHERE is_active = 1')->fetchAll();

        $this->render('checkout/index', [
            'title' => 'Finalizar pedido',
            'items' => CartService::items(),
            'totals' => CartService::totals(),
            'shippingEnabled' => self::shippingEnabled(),
            'shippingMethods' => $shipping,
            'paymentMethods' => $payments,
            'user' => Auth::user(),
        ]);
    }

    private static function shippingEnabled(): bool
    {
        return (bool) (require dirname(__DIR__, 2) . '/config/app.php')['shipping_enabled'];
    }

    /**
     * Consulta o CEP na ViaCEP.
     * Retorna o array da API, ['erro' => true] se o CEP não existe, ou null se a consulta falhou.
     */
    private static function lookupCep(string $cep): ?array
    {
        $url = "https://viacep.com.br/ws/{$cep}/json/";
        $body = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
        } else {
            $ctx = stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => true]]);
            $body = @file_get_contents($url, false, $ctx);
        }
        if (!is_string($body) || $body === '') {
            return null;
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return null;
        }
        return $data;
    }

    private static function isDivinopolis(array $address): bool
    {
        $city = mb_strtolower(trim((string) ($address['localidade'] ?? '')));
        $city = strtr($city, ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
        $uf = strtoupper(trim((string) ($address['uf'] ?? '')));
        return $city === 'divinopolis' && $uf === 'MG';
    }

    public function store(): void
    {
        $this->verifyCsrf();

        if (!CartService::items()) {
            $this->flash('error', 'Sua sacola está vazia.');
            $this->redirect('/carrinho');
        }

        // Outra pessoa pode ter comprado a peça enquanto esta estava na sacola.
        if ($warnings = CartService::reconcileStock()) {
            $this->flash('error', implode(' ', $warnings));
            $this->redirect('/carrinho');
            return;
        }

        $paymentId = (int) $this->input('payment_method_id');
        $pdo = Database::connection();

        $shippingId = null;
        $shipping = null;
        if (self::shippingEnabled()) {
            $shippingId = (int) $this->input('shipping_method_id');
            $ship = $pdo->prepare('SELECT * FROM shipping_methods WHERE id = ? AND is_active = 1');
            $ship->execute([$shippingId]);
            $shipping = $ship->fetch();
        }
        $shippingPrice = $shipping ? (float) $shipping['price'] : 0.0;

        $pay = $pdo->prepare('SELECT * FROM payment_methods WHERE id = ? AND is_active = 1');
        $pay->execute([$paymentId]);
        $payment = $pay->fetch();

        if ((self::shippingEnabled() && !$shipping) || !$payment) {
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

        // Entrega por enquanto só em Divinópolis-MG; o CEP é conferido na ViaCEP.
        $cep = preg_replace('/\D/', '', (string) $this->input('shipping_zip'));
        if (strlen($cep) !== 8) {
            $this->flash('error', 'Informe um CEP válido com 8 números.');
            $this->redirect('/checkout');
            return;
        }
        $address = self::lookupCep($cep);
        if ($address === null) {
            $this->flash('error', 'Não conseguimos validar o CEP agora. Tente novamente ou fale com a gente pelo WhatsApp.');
            $this->redirect('/checkout');
            return;
        }
        if (!empty($address['erro'])) {
            $this->flash('error', 'CEP não encontrado. Confira os números.');
            $this->redirect('/checkout');
            return;
        }
        if (!self::isDivinopolis($address)) {
            $this->flash('error', 'Por enquanto entregamos somente em Divinópolis-MG. Fale com a gente pelo WhatsApp para outras regiões.');
            $this->redirect('/checkout');
            return;
        }

        $totals = CartService::totals($shippingPrice);
        $coupon = $totals['coupon']['code'] ?? null;

        $orderData = [
            'user_id' => Auth::id(),
            'customer_name' => $name,
            'customer_email' => $email ?: null,
            'customer_phone' => $phone,
            'shipping_address' => trim((string) $this->input('shipping_address')),
            'shipping_city' => (string) ($address['localidade'] ?? ''),
            'shipping_state' => (string) ($address['uf'] ?? ''),
            'shipping_zip' => $cep,
            'shipping_method_id' => $shippingId,
            'shipping_price' => $shippingPrice,
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
            // Compra simultânea: a transação foi desfeita, então o estoque não foi baixado.
            $warnings = CartService::reconcileStock();
            if ($warnings) {
                $this->flash('error', 'Poxa, outra pessoa acabou de levar esta peça. ' . implode(' ', $warnings));
                $this->redirect('/carrinho');
                return;
            }
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
        if (self::shippingEnabled()) {
            $lines[] = 'Frete: ' . Helpers::money($order['shipping_price']);
        }
        $lines[] = '*Total: ' . Helpers::money($order['total']) . '*';
        $lines[] = 'Pagamento: ' . ($order['payment_name'] ?? '');
        if (self::shippingEnabled()) {
            $lines[] = 'Entrega: ' . ($order['shipping_name'] ?? '');
        }
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
