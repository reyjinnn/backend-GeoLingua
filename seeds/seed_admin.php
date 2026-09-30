<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$db = databaseConnection();

$email = 'admin@geolingua.org';
$password = 'AdminGeo2026!';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

$roleStmt = $db->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
$roleStmt->execute(['name' => 'admin']);
$roleId = $roleStmt->fetchColumn();

if ($roleId === false) {
    fwrite(STDERR, "Role admin belum di-seed. Import db.sql dulu.\n");
    exit(1);
}

$stmt = $db->prepare(
    'INSERT INTO users (role_id, full_name, email, password_hash, interface_language)
     VALUES (:role_id, :full_name, :email, :password_hash, :interface_language)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name)'
);
$stmt->execute([
    'role_id' => $roleId,
    'full_name' => 'Master Administrator',
    'email' => $email,
    'password_hash' => $hash,
    'interface_language' => 'id',
]);

echo "✓ Admin seeded: {$email} / {$password}\n";