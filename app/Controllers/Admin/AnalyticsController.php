<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\Analytics;

class AnalyticsController extends Controller
{
    public function index(): void
    {
        $days = (int) ($_GET['dias'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }
        $this->render('admin/analytics/index', [
            'title' => 'Visitas do site',
            'stats' => Analytics::summary($days),
        ], 'layouts/admin');
    }
}
