<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/services/AuthService.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE)');
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, role_id INTEGER NOT NULL, full_name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL)');
$db->exec('CREATE TABLE user_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, token TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL)');
$db->exec('CREATE TABLE languages (id INTEGER PRIMARY KEY, code TEXT NOT NULL)');
$db->exec('CREATE TABLE levels (id INTEGER PRIMARY KEY, code TEXT NOT NULL)');
$db->exec('CREATE TABLE courses (id INTEGER PRIMARY KEY, base_language_id INTEGER, target_language_id INTEGER)');
$db->exec('CREATE TABLE user_learning_languages (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, course_id INTEGER NOT NULL, current_level_id INTEGER NOT NULL, is_primary INTEGER NOT NULL)');
$db->exec("INSERT INTO roles (id, name) VALUES (1, 'learner')");

$users = new UserRepository($db);
$service = new AuthService($users);
$middleware = new AuthMiddleware($users);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectApiError(callable $action, string $code): void
{
    try {
        $action();
    } catch (ApiException $error) {
        check($error->errorCode === $code, "Expected {$code}; received {$error->errorCode}");
        return;
    }
    throw new RuntimeException("Expected {$code} error");
}

$registered = $service->register(['full_name' => 'Raihan Test', 'email' => 'RAIHAN@example.com', 'password' => 'Password123!']);
check(strlen($registered['token']) === 64, 'Registration must return a 64-character token');
check($registered['user']['email'] === 'raihan@example.com', 'Email should be normalized');
check($registered['user']['role'] === 'learner', 'Registration must assign learner role');
check(password_verify('Password123!', $users->findByEmail('raihan@example.com')['password_hash']), 'Password must be bcrypt hashed');
check($middleware->authenticate('Bearer ' . $registered['token'])['user']['id'] === $registered['user']['id'], 'Registration token should authenticate');

expectApiError(fn() => $service->register(['full_name' => 'A', 'email' => 'bad', 'password' => 'short']), 'VALIDATION_FAILED');
expectApiError(fn() => $service->register(['full_name' => 'Another User', 'email' => 'raihan@example.com', 'password' => 'Password123!']), 'EMAIL_ALREADY_EXISTS');
expectApiError(fn() => $service->login(['email' => 'raihan@example.com', 'password' => 'Wrong123!']), 'INVALID_CREDENTIALS');

$loggedIn = $service->login(['email' => 'raihan@example.com', 'password' => 'Password123!']);
check($loggedIn['token'] !== $registered['token'], 'Login should create a fresh token');
check(!isset($loggedIn['user']['password_hash']), 'Password hash must not be returned');
expectApiError(fn() => $middleware->authenticate('Bearer invalid'), 'UNAUTHORIZED');

$users->insertToken($registered['user']['id'], str_repeat('a', 64), '2000-01-01 00:00:00');
expectApiError(fn() => $middleware->authenticate('Bearer ' . str_repeat('a', 64)), 'UNAUTHORIZED');
$users->revokeToken($loggedIn['token']);
expectApiError(fn() => $middleware->authenticate('Bearer ' . $loggedIn['token']), 'UNAUTHORIZED');

echo "Auth flow passed\n";
