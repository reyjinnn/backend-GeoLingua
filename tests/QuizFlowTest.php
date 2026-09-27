<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/repositories/QuizRepository.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';
require_once dirname(__DIR__) . '/controllers/QuizController.php';

// Prepare SQLite database
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Create schema
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, level_id INTEGER, module_code TEXT, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE user_module_progress (id INTEGER PRIMARY KEY, user_id INTEGER, module_id INTEGER, status TEXT, highest_quiz_score INTEGER DEFAULT 0, completed_at TIMESTAMP NULL)');
$db->exec('CREATE TABLE quizzes (id INTEGER PRIMARY KEY, module_id INTEGER, title TEXT, passing_score INTEGER, time_limit_minutes INTEGER)');
$db->exec('CREATE TABLE quiz_questions (id INTEGER PRIMARY KEY, quiz_id INTEGER, question_text TEXT, question_type TEXT, score_weight INTEGER, order_index INTEGER)');
$db->exec('CREATE TABLE quiz_options (id INTEGER PRIMARY KEY, question_id INTEGER, option_text TEXT, is_correct INTEGER)');
$db->exec('CREATE TABLE user_quiz_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, quiz_id INTEGER, score INTEGER, is_passed INTEGER, attempt_duration_seconds INTEGER, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');

// Seed data
$db->exec("INSERT INTO modules (id, course_id, level_id, module_code, status) VALUES (1, 1, 1, 'M1', 'published')");
$db->exec("INSERT INTO quizzes (id, module_id, passing_score) VALUES (1, 1, 70)");
$db->exec("INSERT INTO quiz_questions (id, quiz_id, score_weight) VALUES (1, 1, 10)");
$db->exec("INSERT INTO quiz_questions (id, quiz_id, score_weight) VALUES (2, 1, 10)");
$db->exec("INSERT INTO quiz_options (id, question_id, is_correct) VALUES (1, 1, 1), (2, 1, 0), (3, 2, 1)");

$repo = new QuizRepository($db);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// 1. Get Quiz with answers
$quiz = $repo->getQuizWithAnswers(1);
check($quiz['passing_score'] === 70, 'Passing score 70');
check(count($quiz['questions']) === 2, '2 questions');
check($quiz['questions'][1]['correct_option_ids'][0] === 1, 'Q1 correct option is 1');
check($quiz['questions'][2]['correct_option_ids'][0] === 3, 'Q2 correct option is 3');

// 2. Logic testing
$totalWeight = 20;
$earned = 0;
// Answer Q1 correct, Q2 incorrect
$answers = [1 => [1], 2 => [2]];
foreach ($quiz['questions'] as $qId => $qData) {
    if (isset($answers[$qId]) && $answers[$qId] === $qData['correct_option_ids']) {
        $earned += $qData['score_weight'];
    }
}
$score = (int) round(($earned / $totalWeight) * 100);
check($score === 50, 'Score should be 50');
check($score < $quiz['passing_score'], 'Should fail');

// 3. Save failed transaction
$repo->saveQuizResultTransaction(1, 1, 1, 50, false, 60);
$prog = $db->query("SELECT * FROM user_module_progress")->fetchAll();
check(count($prog) === 1, 'Progress created');
check($prog[0]['status'] === 'in_progress', 'Status in_progress because failed');

// 4. Save passed transaction
$repo->saveQuizResultTransaction(1, 1, 1, 100, true, 60);
$prog = $db->query("SELECT * FROM user_module_progress")->fetchAll();
check(count($prog) === 1, 'Progress updated (upsert)');
check($prog[0]['status'] === 'completed', 'Status completed because passed');
check($prog[0]['highest_quiz_score'] == 100, 'Highest score updated');

// 5. Save failed transaction again (retake)
$repo->saveQuizResultTransaction(1, 1, 1, 40, false, 60);
$prog = $db->query("SELECT * FROM user_module_progress")->fetchAll();
check($prog[0]['status'] === 'completed', 'Status should remain completed (do not lock)');
check($prog[0]['highest_quiz_score'] == 100, 'Highest score remains 100');

echo "Quiz flow passed\n";
