<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Product;

class CartService
{
    public static function items(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        $clean = [];
        foreach ($cart as $key => $item) {
            $product = Product::find((int) ($item['product_id'] ?? 0));
            $variant = Product::findVariant((int) ($item['variant_id'] ?? 0));
            if (!$product || !(int) $product['is_active'] || !$variant) {
                continue;
            }
            $withImage = Product::withImage($product);
            $item['image'] = $withImage['image'] ?? ($item['image'] ?? null);
            $item['price'] = (float) $product['price'];
            $item['name'] = $product['name'];
            $item['slug'] = $product['slug'];
            $item['size'] = $variant['size'];
            $item['color'] = $variant['color'];
            $clean[$key] = $item;
        }
        $_SESSION['cart'] = $clean;
        return array_values($clean);
    }

    public static function add(int $variantId, int $qty = 1): array
    {
        $variant = Product::findVariant($variantId);
        if (!$variant || (int) $variant['stock'] < 1) {
            return ['ok' => false, 'message' => 'Variação indisponível.'];
        }

        $product = Product::find((int) $variant['product_id']);
        if (!$product || !(int) $product['is_active']) {
            return ['ok' => false, 'message' => 'Produto indisponível.'];
        }

        $key = (string) $variantId;
        $cart = $_SESSION['cart'] ?? [];
        $current = (int) ($cart[$key]['qty'] ?? 0);
        $newQty = $current + max(1, $qty);

        if ($newQty > (int) $variant['stock']) {
            return ['ok' => false, 'message' => 'Estoque insuficiente para esta combinação.'];
        }

        $cart[$key] = [
            'variant_id' => (int) $variant['id'],
            'product_id' => (int) $product['id'],
            'name' => $product['name'],
            'slug' => $product['slug'],
            'size' => $variant['size'],
            'color' => $variant['color'],
            'price' => (float) $product['price'],
            'qty' => $newQty,
            'image' => Product::withImage($product)['image'] ?? null,
        ];
        $_SESSION['cart'] = $cart;

        return ['ok' => true, 'message' => 'Produto adicionado à sacola.', 'count' => self::count()];
    }

    public static function update(int $variantId, int $qty): array
    {
        $key = (string) $variantId;
        if (!isset($_SESSION['cart'][$key])) {
            return ['ok' => false, 'message' => 'Item não encontrado.'];
        }

        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
            return ['ok' => true, 'message' => 'Item removido.', 'count' => self::count()];
        }

        $variant = Product::findVariant($variantId);
        if (!$variant || $qty > (int) $variant['stock']) {
            return ['ok' => false, 'message' => 'Estoque insuficiente.'];
        }

        $_SESSION['cart'][$key]['qty'] = $qty;
        return ['ok' => true, 'message' => 'Quantidade atualizada.', 'count' => self::count()];
    }

    public static function remove(int $variantId): void
    {
        unset($_SESSION['cart'][(string) $variantId]);
    }

    public static function clear(): void
    {
        unset($_SESSION['cart'], $_SESSION['coupon']);
    }

    public static function count(): int
    {
        $count = 0;
        foreach (self::items() as $item) {
            $count += (int) $item['qty'];
        }
        return $count;
    }

    public static function subtotal(): float
    {
        $sum = 0;
        foreach (self::items() as $item) {
            $sum += $item['price'] * $item['qty'];
        }
        return $sum;
    }

    public static function applyCoupon(string $code): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([strtoupper(trim($code))]);
        $coupon = $stmt->fetch();

        if (!$coupon) {
            return ['ok' => false, 'message' => 'Cupom inválido.'];
        }
        if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
            return ['ok' => false, 'message' => 'Cupom expirado.'];
        }
        if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            return ['ok' => false, 'message' => 'Cupom esgotado.'];
        }

        $subtotal = self::subtotal();
        if ($subtotal < (float) $coupon['min_subtotal']) {
            return ['ok' => false, 'message' => 'Valor mínimo não atingido para este cupom.'];
        }

        $_SESSION['coupon'] = $coupon;
        return ['ok' => true, 'message' => 'Cupom aplicado.', 'coupon' => $coupon];
    }

    public static function discount(): float
    {
        $coupon = $_SESSION['coupon'] ?? null;
        if (!$coupon) {
            return 0;
        }
        $subtotal = self::subtotal();
        if ($coupon['type'] === 'fixed') {
            return min($subtotal, (float) $coupon['value']);
        }
        return round($subtotal * ((float) $coupon['value'] / 100), 2);
    }

    public static function totals(?float $shipping = 0): array
    {
        $subtotal = self::subtotal();
        $discount = self::discount();
        $shipping = (float) $shipping;
        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => max(0, $subtotal - $discount + $shipping),
            'coupon' => $_SESSION['coupon'] ?? null,
        ];
    }
}
