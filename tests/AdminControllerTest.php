<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/controllers/AdminController.php';
require_once dirname(__DIR__) . '/repositories/AdminRepository.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';

// Prepare SQLite database
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT)');
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, role_id INTEGER, email TEXT, password_hash TEXT, full_name TEXT)');
$db->exec('CREATE TABLE user_sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, token TEXT, expires_at TEXT, ip_address TEXT, user_agent TEXT, created_at TEXT)');
$db->exec("INSERT INTO roles (name) VALUES ('admin'), ('learner')");

$adminRepo = new AdminRepository($db);
$userRepo = new UserRepository($db);
$authMiddleware = new AuthMiddleware($userRepo);
$controller = new AdminController($adminRepo, $authMiddleware);

function assertThrows(callable $fn, int $expectedStatus, string $expectedCode): void
{
    try {
        $fn();
        throw new RuntimeException("Expected ApiException with code $expectedCode was not thrown");
    } catch (ApiException $e) {
        if ($e->status !== $expectedStatus || $e->errorCode !== $expectedCode) {
            throw new RuntimeException("Expected status $expectedStatus/$expectedCode, got {$e->status}/{$e->errorCode} ({$e->getMessage()})");
        }
    }
}

// 1. Setup without ADMIN_SETUP_KEY should throw 403 FORBIDDEN
assertThrows(function () use ($controller) {
    $controller->setupAdmin();
}, 403, 'FORBIDDEN');

// 2. Setup with invalid ADMIN_SETUP_KEY should throw 403 FORBIDDEN
putenv('ADMIN_SETUP_KEY=secret_key_123');
$_SERVER['HTTP_X_ADMIN_SETUP_KEY'] = 'wrong_key';
assertThrows(function () use ($controller) {
    $controller->setupAdmin();
}, 403, 'FORBIDDEN');

// 3. Setup with valid key but invalid payload should throw 400 BAD_REQUEST
$_SERVER['HTTP_X_ADMIN_SETUP_KEY'] = 'secret_key_123';
// php://input is empty in test script, so setupAdmin throws 400
assertThrows(function () use ($controller) {
    $controller->setupAdmin();
}, 400, 'BAD_REQUEST');

echo "AdminControllerTest passed\n";
