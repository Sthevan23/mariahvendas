<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Order;
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isAdmin() ? '/admin' : '/conta');
        }
        $this->render('account/login', ['title' => 'Entrar']);
    }

    public function login(): void
    {
        $this->verifyCsrf();
        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');

        if (!Auth::attempt($email, $password)) {
            $this->flash('error', 'E-mail ou senha inválidos.');
            $this->redirect('/login');
        }

        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);

        if (Auth::isAdmin()) {
            $this->redirect('/admin');
        }
        $this->redirect($intended ?: '/conta');
    }

    public function registerForm(): void
    {
        $this->render('account/register', ['title' => 'Criar conta']);
    }

    public function register(): void
    {
        $this->verifyCsrf();
        $name = trim((string) $this->input('name'));
        $email = trim((string) $this->input('email'));
        $phone = trim((string) $this->input('phone'));
        $password = (string) $this->input('password');

        if ($name === '' || $email === '' || strlen($password) < 6) {
            $this->flash('error', 'Preencha os dados corretamente (senha mín. 6 caracteres).');
            $this->redirect('/cadastro');
        }

        if (User::findByEmail($email)) {
            $this->flash('error', 'Este e-mail já está cadastrado.');
            $this->redirect('/cadastro');
        }

        $id = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => 'customer',
        ]);
        Auth::loginUser(User::find($id));
        $this->flash('success', 'Conta criada com sucesso.');
        $this->redirect('/conta');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/');
    }

    public function account(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        $orders = Order::forUser((int) Auth::id());
        $this->render('account/dashboard', [
            'title' => 'Minha conta',
            'orders' => $orders,
            'user' => Auth::user(),
        ]);
    }

    public function order(string $number): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        $order = Order::findByNumber($number);
        if (!$order || (int) $order['user_id'] !== (int) Auth::id()) {
            $this->flash('error', 'Pedido não encontrado.');
            $this->redirect('/conta');
        }
        $this->render('account/order', [
            'title' => 'Pedido ' . $order['number'],
            'order' => $order,
            'items' => Order::items((int) $order['id']),
            'history' => Order::history((int) $order['id']),
        ]);
    }
}
