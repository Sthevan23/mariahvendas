<?php

declare(strict_types=1);

/**
 * Garante usuário admin para o painel.
 * Uso: php database/ensure_admin.php
 *
 * Opcional no .env:
 *   ADMIN_EMAIL=seu@email.com
 *   ADMIN_PASSWORD=SenhaSegura123
 */
require dirname(__DIR__) . '/bootstrap.php';

$pdo = App\Core\Database::connection();
$email = trim((string) (getenv('ADMIN_EMAIL') ?: 'admin@mariahvendas.com.br'));
$password = (string) (getenv('ADMIN_PASSWORD') ?: 'Mariah@2026');
$name = trim((string) (getenv('ADMIN_NAME') ?: 'Administradora'));
$phone = trim((string) (getenv('ADMIN_PHONE') ?: '37998086607'));

$stmt = $pdo->prepare('SELECT id FROM users WHERE role = ? LIMIT 1');
$stmt->execute(['admin']);
$existingId = $stmt->fetchColumn();

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($existingId) {
    $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?')
        ->execute([$name, $email, $phone, $hash, (int) $existingId]);
    echo "Admin atualizado: {$email}\n";
} else {
    $pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)')
        ->execute([$name, $email, $phone, $hash, 'admin']);
    echo "Admin criado: {$email}\n";
}

echo "Acesse: /admin/login\n";
