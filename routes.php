<?php

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\FinanceController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\StockController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\ProductController;
use App\Core\Router;
use App\Middleware\AdminAuth;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/catalogo', [CatalogController::class, 'index']);
$router->get('/categoria/{slug}', [CatalogController::class, 'index']);
$router->get('/busca', [CatalogController::class, 'search']);
$router->get('/produto/{slug}', [ProductController::class, 'show']);

$router->get('/carrinho', [CartController::class, 'index']);
$router->post('/carrinho/adicionar', [CartController::class, 'add']);
$router->post('/carrinho/atualizar', [CartController::class, 'update']);
$router->post('/carrinho/remover', [CartController::class, 'remove']);
$router->post('/carrinho/cupom', [CartController::class, 'coupon']);
$router->get('/favoritos', [CartController::class, 'favorites']);
$router->post('/favoritos/toggle', [CartController::class, 'toggleFavorite']);

$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'store']);

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/cadastro', [AuthController::class, 'registerForm']);
$router->post('/cadastro', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/conta', [AuthController::class, 'account']);
$router->get('/conta/pedido/{number}', [AuthController::class, 'order']);

$router->post('/newsletter', [PageController::class, 'newsletter']);
$router->get('/privacidade', [PageController::class, 'privacy']);
$router->get('/trocas', [PageController::class, 'exchange']);
$router->get('/robots.txt', [PageController::class, 'robots']);
$router->get('/sitemap.xml', [PageController::class, 'sitemap']);

$router->get('/admin/login', [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);

$admin = [AdminAuth::class];
$router->get('/admin', [DashboardController::class, 'index'], $admin);
$router->get('/admin/produtos', [AdminProductController::class, 'index'], $admin);
$router->get('/admin/produtos/novo', [AdminProductController::class, 'create'], $admin);
$router->post('/admin/produtos', [AdminProductController::class, 'store'], $admin);
$router->get('/admin/produtos/{id}/editar', [AdminProductController::class, 'edit'], $admin);
$router->post('/admin/produtos/{id}', [AdminProductController::class, 'update'], $admin);
$router->post('/admin/produtos/{id}/excluir', [AdminProductController::class, 'destroy'], $admin);
$router->get('/admin/pedidos', [AdminOrderController::class, 'index'], $admin);
$router->get('/admin/pedidos/{id}', [AdminOrderController::class, 'show'], $admin);
$router->post('/admin/pedidos/{id}/status', [AdminOrderController::class, 'updateStatus'], $admin);
$router->get('/admin/estoque', [StockController::class, 'index'], $admin);
$router->post('/admin/estoque', [StockController::class, 'adjust'], $admin);
$router->get('/admin/financeiro', [FinanceController::class, 'index'], $admin);
$router->post('/admin/financeiro/despesa', [FinanceController::class, 'storeExpense'], $admin);
$router->get('/admin/financeiro/exportar', [FinanceController::class, 'export'], $admin);
$router->get('/admin/clientes', [AdminCustomerController::class, 'index'], $admin);
$router->get('/admin/configuracoes', [SettingsController::class, 'index'], $admin);
$router->post('/admin/configuracoes', [SettingsController::class, 'save'], $admin);
$router->post('/admin/configuracoes/categoria', [SettingsController::class, 'saveCategory'], $admin);
$router->post('/admin/configuracoes/cupom', [SettingsController::class, 'saveCoupon'], $admin);

return $router;
