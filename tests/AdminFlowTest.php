<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/repositories/AdminRepository.php';

// Prepare SQLite database
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Create schema
$db->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT)');
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, role_id INTEGER, email TEXT, password_hash TEXT, full_name TEXT)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY AUTOINCREMENT, course_id INTEGER, level_id INTEGER, module_code TEXT, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY AUTOINCREMENT, module_id INTEGER, lesson_name TEXT, lesson_objective TEXT, grammar_notes TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE lesson_vocabularies (lesson_id INTEGER, vocabulary_id INTEGER)');
$db->exec('CREATE TABLE exercises (id INTEGER PRIMARY KEY AUTOINCREMENT, lesson_id INTEGER)');
$db->exec('CREATE TABLE writing_exercises (id INTEGER PRIMARY KEY AUTOINCREMENT, lesson_id INTEGER)');
$db->exec('CREATE TABLE quizzes (id INTEGER PRIMARY KEY AUTOINCREMENT, module_id INTEGER)');
$db->exec('CREATE TABLE quiz_questions (id INTEGER PRIMARY KEY AUTOINCREMENT, quiz_id INTEGER)');
$db->exec('CREATE TABLE quiz_options (id INTEGER PRIMARY KEY AUTOINCREMENT, question_id INTEGER, option_text TEXT, is_correct INTEGER)');

// Seed initial roles
$db->exec("INSERT INTO roles (name) VALUES ('admin'), ('learner')");

$repo = new AdminRepository($db);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// 0. Check hasAnyAdmin before any admin exists
check($repo->hasAnyAdmin() === false, 'Initially no admin exists');

// 1. Init admin
$success = $repo->initAdmin('admin@geolingua.com', 'hashedpwd', 'Administrator');
check($success === true, 'First admin should succeed');
$users = $db->query("SELECT * FROM users")->fetchAll();
check(count($users) === 1, 'Admin user created');
check($users[0]['email'] === 'admin@geolingua.com', 'Email matches');
check($repo->hasAnyAdmin() === true, 'Admin exists after init');

// 2. Init admin again (should fail)
$success2 = $repo->initAdmin('admin2@geolingua.com', 'pwd', 'Admin 2');
check($success2 === false, 'Second admin init should fail');

// 3. Create module (should always start as 'draft')
$moduleId = $repo->createModule([
    'course_id' => 1,
    'level_id' => 1,
    'module_code' => 'M1',
    'title' => 'Module 1',
    'status' => 'published', // Attempt bypass
    'prerequisite_module_id' => null
]);
check($moduleId === 1, 'Module created with ID 1');
$modCreated = $db->query("SELECT status FROM modules WHERE id = 1")->fetch();
check($modCreated['status'] === 'draft', 'Module creation must enforce draft status');

// 4. Create lesson
$lessonId = $repo->createLesson([
    'module_id' => $moduleId,
    'lesson_name' => 'Lesson 1'
]);
check($lessonId === 1, 'Lesson created with ID 1');

// 5. Publish (incomplete)
$details1 = $repo->getModuleDetailsForPublishing($moduleId);
check($details1['has_lesson'] === true, 'Has lesson');
check($details1['has_vocabulary'] === false, 'No vocab');

// Add requirements
$db->exec("INSERT INTO lesson_vocabularies (lesson_id, vocabulary_id) VALUES (1, 1)");
$db->exec("INSERT INTO exercises (lesson_id) VALUES (1)");
$db->exec("INSERT INTO writing_exercises (lesson_id) VALUES (1)");
$db->exec("INSERT INTO quizzes (module_id) VALUES (1)");
for ($i=1; $i<=10; $i++) {
    $db->exec("INSERT INTO quiz_questions (quiz_id) VALUES (1)");
    $db->exec("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES ($i, 'Opt 1', 1)");
    $db->exec("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES ($i, 'Opt 2', 0)");
}

$details2 = $repo->getModuleDetailsForPublishing($moduleId);
check($details2['has_vocabulary'] === true, 'Has vocab');
check($details2['has_drill'] === true, 'Has drill');
check($details2['has_writing'] === true, 'Has writing');
check($details2['has_quiz'] === true, 'Has quiz');
check($details2['quiz_question_count'] === 10, 'Has 10 questions');
check($details2['invalid_quiz_questions'] === 0, 'All quiz questions are valid');

$repo->updateModuleStatus($moduleId, 'published');
$mod = $db->query("SELECT status FROM modules WHERE id = 1")->fetch();
check($mod['status'] === 'published', 'Module published');

echo "Admin flow passed\n";
