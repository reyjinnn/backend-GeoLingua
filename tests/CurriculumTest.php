<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/services/CurriculumService.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Setup tables
$db->exec('CREATE TABLE user_learning_languages (id INTEGER PRIMARY KEY, user_id INTEGER, course_id INTEGER, current_level_id INTEGER, is_primary INTEGER DEFAULT 1)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, level_id INTEGER, module_code TEXT, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE user_module_progress (user_id INTEGER, module_id INTEGER, status TEXT, highest_quiz_score INTEGER DEFAULT 0, completed_at TEXT)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER, lesson_name TEXT, lesson_objective TEXT, grammar_notes TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE lesson_vocabularies (lesson_id INTEGER, vocabulary_id INTEGER, order_index INTEGER)');
$db->exec('CREATE TABLE quizzes (id INTEGER PRIMARY KEY, module_id INTEGER, title TEXT, passing_score INTEGER)');

// Seed test data
$db->exec("INSERT INTO user_learning_languages VALUES (1, 1, 1, 1, 1), (2, 2, 1, 1, 1)");
$db->exec("INSERT INTO modules VALUES
    (1, 1, 1, 'M01', 'Introduction', 'Topic 1', 'Desc 1', 'Obj 1', 30, 1, NULL, 'published'),
    (2, 1, 1, 'M02', 'Daily', 'Topic 2', 'Desc 2', 'Obj 2', 30, 2, 1, 'published'),
    (3, 1, 1, 'M03', 'Family', 'Topic 3', 'Desc 3', 'Obj 3', 30, 3, 2, 'published')");
$db->exec("INSERT INTO lessons VALUES
    (1, 1, 'Lesson 1', 'Obj', NULL, 1),
    (2, 1, 'Lesson 2', 'Obj', NULL, 2)");
$db->exec("INSERT INTO lesson_vocabularies VALUES (1, 1, 1), (1, 2, 2), (2, 3, 1)");

function checkCurriculum(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectCurriculumError(callable $action, string $code): void
{
    try {
        $action();
    } catch (ApiException $e) {
        checkCurriculum($e->errorCode === $code, "Expected {$code}; received {$e->errorCode}");
        return;
    }
    throw new RuntimeException("Expected {$code} error");
}

$repo = new CurriculumRepository($db);
$service = new CurriculumService($repo);

// Test 1: New user — Module 1 unlocked, 2 & 3 locked
$modules = $service->listModulesForUser(1);
checkCurriculum(count($modules) === 3, 'Should return 3 modules');
checkCurriculum($modules[0]['status'] === 'unlocked', 'Module 1 should be unlocked');
checkCurriculum($modules[1]['status'] === 'locked', 'Module 2 should be locked');
checkCurriculum($modules[2]['status'] === 'locked', 'Module 3 should be locked');

// Test 2: Module 1 completed — Module 2 unlocked
$db->exec("INSERT INTO user_module_progress VALUES (1, 1, 'completed', 80, '2026-01-01')");
$modules = $service->listModulesForUser(1);
checkCurriculum($modules[0]['is_completed'] === true, 'Module 1 should be completed');
checkCurriculum($modules[0]['progress_percentage'] === 100, 'Module 1 progress 100%');
checkCurriculum($modules[1]['status'] === 'unlocked', 'Module 2 should be unlocked');
checkCurriculum($modules[2]['status'] === 'locked', 'Module 3 should still be locked');

// Test 3: Module 2 completed — Module 3 unlocked
$db->exec("INSERT INTO user_module_progress VALUES (1, 2, 'completed', 90, '2026-01-02')");
$modules = $service->listModulesForUser(1);
checkCurriculum($modules[1]['is_completed'] === true, 'Module 2 completed');
checkCurriculum($modules[2]['status'] === 'unlocked', 'Module 3 should be unlocked');

// Test 4: Module detail accessible
$detail = $service->moduleWithLessons(1, 1);
checkCurriculum($detail['id'] === 1, 'Module detail ID 1');
checkCurriculum(count($detail['lessons']) === 2, 'Module 1 has 2 lessons');
checkCurriculum($detail['lessons'][0]['vocabulary_count'] === 2, 'Lesson 1 has 2 vocab');
checkCurriculum($detail['lessons'][1]['vocabulary_count'] === 1, 'Lesson 2 has 1 vocab');

// Test 5: Locked module detail (user 2, module 2) — MODULE_LOCKED
expectCurriculumError(fn() => $service->moduleWithLessons(2, 2), 'MODULE_LOCKED');

// Test 6: Nonexistent module — MODULE_NOT_FOUND
expectCurriculumError(fn() => $service->moduleWithLessons(1, 999), 'MODULE_NOT_FOUND');

// Test 7: User without active course — NO_ACTIVE_COURSE
expectCurriculumError(fn() => $service->listModulesForUser(999), 'NO_ACTIVE_COURSE');

echo "Curriculum test passed\n";