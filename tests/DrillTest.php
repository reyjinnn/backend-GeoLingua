<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/services/DrillEngineService.php';
require_once dirname(__DIR__) . '/repositories/ExerciseRepository.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Setup tables
$db->exec('CREATE TABLE exercises (id INTEGER PRIMARY KEY, lesson_id INTEGER, vocabulary_id INTEGER, question_type TEXT, prompt TEXT, correct_answer TEXT, explanation TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE exercise_options (id INTEGER PRIMARY KEY, exercise_id INTEGER, option_text TEXT, is_correct INTEGER)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER, lesson_name TEXT)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER)');
$db->exec('CREATE TABLE user_learning_languages (user_id INTEGER, course_id INTEGER, is_primary INTEGER)');

// Seed test data
$db->exec("INSERT INTO exercises VALUES
    (1, 1, 1, 'mcq_meaning', 'Pilih arti: Hello', 'Halo', 'Sapaan', 1),
    (2, 1, 2, 'typing', 'Ketik: Nama', 'name', 'Nama = name', 2)");

$db->exec("INSERT INTO exercise_options VALUES
    (1, 1, 'Halo', 1),
    (2, 1, 'Selamat tinggal', 0),
    (3, 1, 'Terima kasih', 0),
    (4, 1, 'Sama-sama', 0)");

$db->exec("INSERT INTO lessons VALUES (1, 1, 'Greetings')");
$db->exec("INSERT INTO modules VALUES (1, 1)");
$db->exec("INSERT INTO user_learning_languages VALUES (1, 1, 1)");

function checkDrill(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectDrillError(callable $action, string $code): void
{
    try {
        $action();
    } catch (ApiException $e) {
        checkDrill($e->errorCode === $code, "Expected {$code}; received {$e->errorCode}");
        return;
    }
    throw new RuntimeException("Expected {$code} error");
}

$repo = new ExerciseRepository($db);
$service = new DrillEngineService($repo);

// Test 1: listDrillsForLesson
$drills = $service->listDrillsForLesson(1);
checkDrill(count($drills) === 2, 'Should return 2 drills');
checkDrill($drills[0]['id'] === 1, 'First drill ID should be 1');
checkDrill($drills[0]['question_type'] === 'mcq_meaning', 'First drill should be MCQ');
checkDrill(count($drills[0]['options']) === 4, 'MCQ should have 4 options');
checkDrill($drills[1]['question_type'] === 'typing', 'Second drill should be typing');
checkDrill(!isset($drills[1]['options']), 'Typing should NOT have options');
checkDrill(isset($drills[1]['placeholder']), 'Typing should have placeholder');

// Test 2: MCQ benar
$result = $service->evaluateAnswer(1, '1');
checkDrill($result['is_correct'] === true, 'MCQ correct answer should be true');
checkDrill($result['correct_answer'] === 'Halo', 'Correct answer should be Halo');

// Test 3: MCQ salah
$result = $service->evaluateAnswer(1, '2');
checkDrill($result['is_correct'] === false, 'MCQ wrong answer should be false');

// Test 4: Typing benar
$result = $service->evaluateAnswer(2, 'name');
checkDrill($result['is_correct'] === true, 'Typing correct answer should be true');

// Test 5: Typing case-insensitive
$result = $service->evaluateAnswer(2, 'NAME');
checkDrill($result['is_correct'] === true, 'Typing should be case-insensitive');

// Test 6: Typing dengan whitespace
$result = $service->evaluateAnswer(2, '  name  ');
checkDrill($result['is_correct'] === true, 'Typing should trim whitespace');

// Test 7: Typing salah
$result = $service->evaluateAnswer(2, 'wrong');
checkDrill($result['is_correct'] === false, 'Typing wrong answer should be false');

// Test 8: Drill not found
expectDrillError(fn() => $service->evaluateAnswer(999, '1'), 'DRILL_NOT_FOUND');

// Test 9: exerciseBelongsToUserCourse
checkDrill($repo->exerciseBelongsToUserCourse(1, 1) === true, 'User 1 should access exercise 1');
checkDrill($repo->exerciseBelongsToUserCourse(1, 999) === false, 'User 999 should not access exercise 1');
checkDrill($repo->exerciseBelongsToUserCourse(999, 1) === false, 'User 1 should not access exercise 999');

echo "Drill test passed\n";