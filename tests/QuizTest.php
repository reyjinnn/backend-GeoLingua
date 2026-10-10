<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/services/QuizService.php';
require_once dirname(__DIR__) . '/services/ModuleProgressService.php';
require_once dirname(__DIR__) . '/repositories/QuizRepository.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Setup tables
$db->exec('CREATE TABLE quizzes (id INTEGER PRIMARY KEY, module_id INTEGER, title TEXT, passing_score INTEGER, time_limit_minutes INTEGER)');
$db->exec('CREATE TABLE quiz_questions (id INTEGER PRIMARY KEY, quiz_id INTEGER, question_text TEXT, question_type TEXT, explanation TEXT, score_weight INTEGER, order_index INTEGER)');
$db->exec('CREATE TABLE quiz_options (id INTEGER PRIMARY KEY, question_id INTEGER, option_text TEXT, is_correct INTEGER)');
$db->exec('CREATE TABLE user_quiz_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, quiz_id INTEGER, score INTEGER, is_passed INTEGER, attempt_duration_seconds INTEGER, created_at TEXT)');
$db->exec('CREATE TABLE user_module_progress (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, module_id INTEGER, status TEXT, highest_quiz_score INTEGER DEFAULT 0, completed_at TEXT)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, level_id INTEGER, order_index INTEGER)');
$db->exec('CREATE TABLE user_learning_languages (user_id INTEGER, course_id INTEGER, is_primary INTEGER)');

// Seed test data
$db->exec("INSERT INTO quizzes VALUES (1, 1, 'Kuis Test', 70, 15)");
$db->exec("INSERT INTO quiz_questions VALUES
    (1, 1, 'Q1?', 'multiple_choice', '', 10, 1),
    (2, 1, 'Q2?', 'multiple_choice', '', 10, 2),
    (3, 1, 'Q3?', 'multiple_choice', '', 10, 3),
    (4, 1, 'Q4?', 'multiple_choice', '', 10, 4),
    (5, 1, 'Q5?', 'multiple_choice', '', 10, 5),
    (6, 1, 'Q6?', 'multiple_choice', '', 10, 6),
    (7, 1, 'Q7?', 'multiple_choice', '', 10, 7),
    (8, 1, 'Q8?', 'multiple_choice', '', 10, 8),
    (9, 1, 'Q9?', 'multiple_choice', '', 10, 9),
    (10, 1, 'Q10?', 'multiple_choice', '', 10, 10)");

// 10 questions × 4 options. Correct = first option of each.
for ($q = 1; $q <= 10; $q++) {
    for ($opt = 0; $opt < 4; $opt++) {
        $id = ($q - 1) * 4 + $opt + 1;
        $isCorrect = $opt === 0 ? 1 : 0;
        $db->exec("INSERT INTO quiz_options VALUES ({$id}, {$q}, 'Option {$opt}', {$isCorrect})");
    }
}

$db->exec("INSERT INTO modules VALUES (1, 1, 1, 1), (2, 1, 1, 2), (3, 1, 1, 3)");
$db->exec("INSERT INTO user_learning_languages VALUES (1, 1, 1)");

function checkQuiz(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectQuizError(callable $action, string $code): void
{
    try {
        $action();
    } catch (ApiException $e) {
        checkQuiz($e->errorCode === $code, "Expected {$code}; received {$e->errorCode}");
        return;
    }
    throw new RuntimeException("Expected {$code} error");
}

$repo = new QuizRepository($db);
$service = new QuizService($repo);
$progressService = new ModuleProgressService($db);

// Test 1: Get quiz for module
$quiz = $service->quizForModule(1);
checkQuiz($quiz['quiz_id'] === 1, 'Quiz ID should be 1');
checkQuiz($quiz['passing_score'] === 70, 'Passing score should be 70');
checkQuiz(count($quiz['questions']) === 10, 'Should have 10 questions');
checkQuiz(count($quiz['questions'][0]['options']) === 4, 'Each question should have 4 options');
checkQuiz(!isset($quiz['questions'][0]['options'][0]['is_correct']), 'Options should NOT include is_correct');

// Helper: build answers array
$buildAnswers = function (array $correctQuestionIds) {
    $answers = [];
    for ($q = 1; $q <= 10; $q++) {
        $optId = in_array($q, $correctQuestionIds, true)
            ? ($q - 1) * 4 + 1   // correct (first option)
            : ($q - 1) * 4 + 2;  // wrong (second option)
        $answers[] = ['question_id' => $q, 'selected_option_id' => $optId];
    }
    return $answers;
};

// Test 2: Submit all correct → score 100
$result = $service->submitQuiz(1, 1, $buildAnswers(range(1, 10)), 180);
checkQuiz($result['score'] === 100, 'Score should be 100');
checkQuiz($result['is_passed'] === true, 'Should pass');

// Test 3: Submit 7/10 correct → score 70 → pass
$result = $service->submitQuiz(1, 1, $buildAnswers([1, 2, 3, 4, 5, 6, 7]), 180);
checkQuiz($result['score'] === 70, 'Score should be 70');
checkQuiz($result['is_passed'] === true, 'Should pass (70 >= 70)');

// Test 4: Submit 6/10 correct → score 60 → fail
$result = $service->submitQuiz(1, 1, $buildAnswers([1, 2, 3, 4, 5, 6]), 180);
checkQuiz($result['score'] === 60, 'Score should be 60');
checkQuiz($result['is_passed'] === false, 'Should fail (60 < 70)');

// Test 5: Quiz not found
expectQuizError(fn() => $service->quizForModule(999), 'QUIZ_NOT_FOUND');
expectQuizError(fn() => $service->submitQuiz(1, 999, [], 0), 'QUIZ_NOT_FOUND');

// Test 6: Complete module & unlock next
$unlockedId = $progressService->completeModuleAndUnlockNext(1, 1, 100);
checkQuiz($unlockedId === 2, 'Should unlock module 2');

// Verify DB
$status = $db->query("SELECT status FROM user_module_progress WHERE user_id = 1 AND module_id = 1")->fetchColumn();
checkQuiz($status === 'completed', 'Module 1 status should be completed');
$nextStatus = $db->query("SELECT status FROM user_module_progress WHERE user_id = 1 AND module_id = 2")->fetchColumn();
checkQuiz($nextStatus === 'unlocked', 'Module 2 status should be unlocked');

// Test 7: Attempts saved (3 attempts: Test 2, 3, 4)
$count = (int) $db->query('SELECT COUNT(*) FROM user_quiz_attempts')->fetchColumn();
checkQuiz($count === 3, "Expected 3 attempts, got {$count}");

echo "Quiz test passed\n";