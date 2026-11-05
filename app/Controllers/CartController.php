<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Helpers;
use App\Core\View;
use App\Services\CartService;

class CartController extends Controller
{
    public function index(): void
    {
        $this->render('cart/index', [
            'title' => 'Sacola',
            'items' => CartService::items(),
            'totals' => CartService::totals(),
        ]);
    }

    public function add(): void
    {
        $this->verifyCsrf();
        $variantId = (int) $this->input('variant_id');
        $qty = max(1, (int) $this->input('qty', 1));
        $buyNow = (string) $this->input('buy_now', '') === '1';
        $result = CartService::add($variantId, $qty);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            if ($result['ok'] && $buyNow) {
                $result['redirect'] = '/checkout';
            }
            View::json($result, $result['ok'] ? 200 : 422);
            return;
        }

        $this->flash($result['ok'] ? 'success' : 'error', $result['message']);
        if ($result['ok'] && $buyNow) {
            $this->redirect('/checkout');
        }
        $this->back();
    }

    public function update(): void
    {
        $this->verifyCsrf();
        $variantId = (int) $this->input('variant_id');
        $qty = (int) $this->input('qty', 1);
        $result = CartService::update($variantId, $qty);
        $this->flash($result['ok'] ? 'success' : 'error', $result['message']);
        $this->redirect('/carrinho');
    }

    public function remove(): void
    {
        $this->verifyCsrf();
        CartService::remove((int) $this->input('variant_id'));
        $this->flash('success', 'Item removido da sacola.');
        $this->redirect('/carrinho');
    }

    public function coupon(): void
    {
        $this->verifyCsrf();
        $result = CartService::applyCoupon((string) $this->input('code', ''));
        $this->flash($result['ok'] ? 'success' : 'error', $result['message']);
        $this->redirect('/carrinho');
    }

    public function toggleFavorite(): void
    {
        $this->verifyCsrf();
        $id = (int) $this->input('product_id');
        $favorites = $_SESSION['favorites'] ?? [];
        if (in_array($id, $favorites, true)) {
            $favorites = array_values(array_diff($favorites, [$id]));
            $msg = 'Removido dos favoritos.';
        } else {
            $favorites[] = $id;
            $msg = 'Adicionado aos favoritos.';
        }
        $_SESSION['favorites'] = $favorites;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            View::json(['ok' => true, 'message' => $msg, 'active' => Helpers::isFavorite($id)]);
            return;
        }

        $this->flash('success', $msg);
        $this->back();
    }

    public function favorites(): void
    {
        $ids = $_SESSION['favorites'] ?? [];
        $products = [];
        foreach ($ids as $id) {
            $p = \App\Models\Product::find((int) $id);
            if ($p && (int) $p['is_active']) {
                $products[] = \App\Models\Product::withImage($p);
            }
        }
        $this->render('cart/favorites', [
            'title' => 'Favoritos',
            'products' => $products,
        ]);
    }
}
