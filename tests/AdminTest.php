<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/controllers/AdminController.php';
require_once dirname(__DIR__) . '/middleware/AdminMiddleware.php';
require_once dirname(__DIR__) . '/repositories/AdminRepository.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Setup tables
$db->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE)');
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, role_id INTEGER NOT NULL, full_name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL)');
$db->exec('CREATE TABLE user_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, token TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL)');
$db->exec('CREATE TABLE courses (id INTEGER PRIMARY KEY, base_language_id INTEGER, target_language_id INTEGER, title TEXT, is_active INTEGER DEFAULT 1)');
$db->exec('CREATE TABLE levels (id INTEGER PRIMARY KEY, code TEXT, name TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY AUTOINCREMENT, course_id INTEGER, level_id INTEGER, module_code TEXT UNIQUE, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER DEFAULT 30, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT DEFAULT \'draft\')');

// Seed
$db->exec("INSERT INTO roles VALUES (1, 'learner'), (2, 'admin')");
$db->exec("INSERT INTO users VALUES (1, 2, 'Admin', 'admin@test.com', 'hash')");
$db->exec("INSERT INTO users VALUES (2, 1, 'Learner', 'learner@test.com', 'hash')");
$db->exec("INSERT INTO user_tokens VALUES (1, 1, '" . str_repeat('a', 64) . "', '2099-01-01 00:00:00')");
$db->exec("INSERT INTO user_tokens VALUES (2, 2, '" . str_repeat('b', 64) . "', '2099-01-01 00:00:00')");
$db->exec("INSERT INTO courses VALUES (1, 1, 2, 'English for Indonesian', 1)");
$db->exec("INSERT INTO levels VALUES (1, 'A1', 'Beginner', 1)");
$db->exec("INSERT INTO modules VALUES (1, 1, 1, 'EN-A1-M01', 'Intro', 'Topic', 'Desc', 'Obj', 30, 1, NULL, 'published')");

function checkAdmin(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectAdminError(callable $action, string $code): void
{
    try {
        $action();
    } catch (ApiException $e) {
        checkAdmin($e->errorCode === $code, "Expected {$code}; received {$e->errorCode}");
        return;
    }
    throw new RuntimeException("Expected {$code} error");
}

$users = new UserRepository($db);
$auth = new AuthMiddleware($users);
$adminMiddleware = new AdminMiddleware($auth);
$repo = new AdminRepository($db);

// Test 1: Admin authorize berhasil
$session = $adminMiddleware->authorize('Bearer ' . str_repeat('a', 64));
checkAdmin($session['user']['role'] === 'admin', 'Admin should authorize');

// Test 2: Learner authorize gagal
expectAdminError(fn() => $adminMiddleware->authorize('Bearer ' . str_repeat('b', 64)), 'FORBIDDEN');

// Test 3: Dashboard metrics
$metrics = $repo->dashboardMetrics();
checkAdmin($metrics['total_users'] === 2, 'Should count 2 users');
checkAdmin($metrics['published_modules'] === 1, 'Should count 1 published');
checkAdmin($metrics['draft_modules'] === 0, 'Should count 0 draft');

// Test 4: All modules
$modules = $repo->allModules();
checkAdmin(count($modules) === 1, 'Should return 1 module');

// Test 5: Create module
$newId = $repo->createModule([
    'course_id' => 1,
    'level_id' => 1,
    'module_code' => 'EN-A1-M02',
    'title' => 'Daily Activities',
    'topic' => 'Daily Routines',
    'description' => 'Desc',
    'learning_objectives' => 'Obj',
    'order_index' => 2,
]);
checkAdmin($newId === 2, 'New module ID should be 2');

$created = $repo->findModuleById(2);
checkAdmin($created['status'] === 'draft', 'New module status should be draft');

// Test 6: Publish module
$repo->setModuleStatus(2, 'published');
checkAdmin($repo->findModuleById(2)['status'] === 'published', 'Should be published');

// Test 7: Update module
$repo->updateModule(2, ['title' => 'Updated']);
checkAdmin($repo->findModuleById(2)['title'] === 'Updated', 'Title should update');

// Test 8: Module code exists
checkAdmin($repo->moduleCodeExists('EN-A1-M01') === true, 'M01 exists');
checkAdmin($repo->moduleCodeExists('EN-A1-M99') === false, 'M99 not exist');

// Test 9: Delete module
$repo->deleteModule(2);
checkAdmin($repo->moduleExists(2) === false, 'Module 2 deleted');

echo "Admin test passed\n";