<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Helpers;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index(): void
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');

        $revenueToday = Order::revenue($today, $today);
        $revenueWeek = Order::revenue($weekStart, $today);
        $revenueMonth = Order::revenue($monthStart, $today);
        $ordersMonth = Order::count($monthStart, $today);
        $paidMonth = Order::count($monthStart, $today, 'paid');
        $ticket = $paidMonth > 0 ? $revenueMonth / $paidMonth : 0;

        $this->render('admin/dashboard', [
            'title' => 'Dashboard',
            'revenueToday' => $revenueToday,
            'revenueWeek' => $revenueWeek,
            'revenueMonth' => $revenueMonth,
            'ordersMonth' => $ordersMonth,
            'ticket' => $ticket,
            'topProducts' => Order::topProducts(),
            'lowStock' => Product::lowStock((int) Helpers::setting('low_stock_threshold', 5)),
            'awaiting' => Order::awaiting(),
            'chart' => Order::salesByDay(14),
        ], 'layouts/admin');
    }
}
