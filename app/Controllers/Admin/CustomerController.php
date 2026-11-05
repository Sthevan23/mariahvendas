<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;

class CustomerController extends Controller
{
    public function index(): void
    {
        $this->render('admin/customers/index', [
            'title' => 'Clientes',
            'customers' => User::customers(),
        ], 'layouts/admin');
    }
}
