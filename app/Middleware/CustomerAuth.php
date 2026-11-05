<?php

namespace App\Middleware;

use App\Core\Auth;

class CustomerAuth
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/conta';
            header('Location: /login');
            return false;
        }
        return true;
    }
}
