<?php

declare(strict_types=1);

/**
 * Garante usuário admin para o painel.
 * CLI: php database/ensure_admin.php
 *
 * Opcional no .env: ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME, ADMIN_PHONE
 */
function mariah_ensure_admin(): string
{
    $pdo = App\Core\Database::connection();
    $email = trim((string) getenv('ADMIN_EMAIL'));
    if ($email === '') {
        throw new RuntimeException('Defina ADMIN_EMAIL no .env antes de criar o admin.');
    }
    $password = (string) getenv('ADMIN_PASSWORD');
    if ($password === '') {
        throw new RuntimeException('Defina ADMIN_PASSWORD no .env antes de criar o admin.');
    }
    $name = trim((string) (getenv('ADMIN_NAME') ?: 'Administrador(a)'));
    $phone = trim((string) (getenv('ADMIN_PHONE') ?: '37998086607'));

    $stmt = $pdo->prepare('SELECT id FROM users WHERE role = ? LIMIT 1');
    $stmt->execute(['admin']);
    $existingId = $stmt->fetchColumn();

    $legacyEmails = [
        'admin@mandalla.com',
        'admin@mariahaltocostura.com.br',
        'admin@mariahvendas.com.br',
    ];
    $placeholders = implode(',', array_fill(0, count($legacyEmails), '?'));
    $legacy = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) IN ($placeholders) OR role = 'admin' LIMIT 1");
    $legacy->execute(array_map('strtolower', $legacyEmails));
    $existingId = $legacy->fetchColumn() ?: $existingId;

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existingId) {
        $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, password = ?, role = ? WHERE id = ?')
            ->execute([$name, $email, $phone, $hash, 'admin', (int) $existingId]);
        return "Admin atualizado: {$email}";
    }

    $pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)')
        ->execute([$name, $email, $phone, $hash, 'admin']);

    return "Admin criado: {$email}";
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require dirname(__DIR__) . '/bootstrap.php';
    echo mariah_ensure_admin() . "\n";
    echo "Acesse: /admin/login\n";
}
