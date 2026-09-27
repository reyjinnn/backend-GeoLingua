<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Perintah ini hanya dapat dijalankan melalui CLI.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/repositories/AdminRepository.php';

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name = $argv[3] ?? 'Admin GeoLingua';

if (!$email || !$password) {
    echo "Penggunaan: php scripts/create_admin.php <email> <password> [nama]\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Error: Format email tidak valid.\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Error: Password minimal 8 karakter.\n");
    exit(1);
}

$adminRepo = AdminRepository::fromConfig();
$hash = password_hash($password, PASSWORD_DEFAULT);

$created = $adminRepo->initAdmin($email, $hash, $name);
if ($created) {
    echo "Sukses: Akun admin awal berhasil dibuat ({$email}).\n";
    exit(0);
}

// If admin already exists, allow creating an additional admin if explicit
echo "Pemberitahuan: Admin utama sudah ada di sistem. Membuat akun admin baru...\n";
$db = databaseConnection();
$roleQuery = $db->prepare('SELECT id FROM roles WHERE name = "admin"');
$roleQuery->execute();
$roleId = $roleQuery->fetchColumn();

// Check if email already used
$userCheck = $db->prepare('SELECT id FROM users WHERE email = :email');
$userCheck->execute(['email' => $email]);
if ($userCheck->fetchColumn()) {
    fwrite(STDERR, "Error: User dengan email {$email} sudah ada.\n");
    exit(1);
}

$insert = $db->prepare('INSERT INTO users (role_id, email, password_hash, full_name) VALUES (:role_id, :email, :hash, :name)');
$insert->execute([
    'role_id' => (int) $roleId,
    'email' => $email,
    'hash' => $hash,
    'name' => $name
]);

echo "Sukses: Admin tambahan berhasil dibuat ({$email}).\n";
