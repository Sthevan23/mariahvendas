<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\RememberMe;

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
        $this->verifyCsrf('/admin/login');
        $email = strtolower(trim((string) $this->input('email')));
        $password = (string) $this->input('password');

        if (!Auth::attempt($email, $password)) {
            $this->flash('error', 'E-mail ou senha incorretos. Use o e-mail e a senha definidos para o painel.');
            $this->redirect('/admin/login');
        }

        if (!Auth::isAdmin()) {
            Auth::logout();
            $this->flash('error', 'Esta conta não é administradora. Acesse pelo login da loja em /login.');
            $this->redirect('/admin/login');
        }

        if ($this->input('remember')) {
            RememberMe::issue((int) Auth::id());
        }

        $this->redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
