<?php

namespace App\Middleware;

use App\Core\Auth;

class AdminAuth
{
    public function handle(): bool
    {
        if (!Auth::isAdmin()) {
            header('Location: /admin/login');
            return false;
        }
        return true;
    }
}
