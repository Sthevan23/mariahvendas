<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User;

class ProfileController extends Controller
{
    public function index(): void
    {
        $user = User::find((int) Auth::id());
        $this->render('admin/profile/index', [
            'title' => 'Minha conta',
            'user' => $user,
        ], 'layouts/admin');
    }

    public function updatePassword(): void
    {
        $this->verifyCsrf();
        $current = (string) $this->input('current_password');
        $next = (string) $this->input('new_password');
        $confirm = (string) $this->input('new_password_confirm');

        $user = User::find((int) Auth::id());
        if (!$user || !password_verify($current, $user['password'])) {
            $this->flash('error', 'Senha atual incorreta.');
            $this->redirect('/admin/conta');
        }

        if (strlen($next) < 8) {
            $this->flash('error', 'A nova senha deve ter pelo menos 8 caracteres.');
            $this->redirect('/admin/conta');
        }

        if ($next !== $confirm) {
            $this->flash('error', 'A confirmação não confere com a nova senha.');
            $this->redirect('/admin/conta');
        }

        $hash = password_hash($next, PASSWORD_DEFAULT);
        Database::connection()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, (int) $user['id']]);

        $this->flash('success', 'Senha atualizada com sucesso.');
        $this->redirect('/admin/conta');
    }
}
