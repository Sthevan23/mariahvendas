<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::isAdmin()) {
            $this->redirect('/admin');
        }
        $this->render('admin/auth/login', ['title' => 'Admin Ateliê Mariah'], 'layouts/admin-auth');
    }

    public function login(): void
    {
        $this->verifyCsrf();
        if (!Auth::attempt(trim((string) $this->input('email')), (string) $this->input('password')) || !Auth::isAdmin()) {
            Auth::logout();
            $this->flash('error', 'Acesso administrativo inválido.');
            $this->redirect('/admin/login');
        }
        $this->redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
