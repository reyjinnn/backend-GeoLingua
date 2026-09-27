<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';
require_once dirname(__DIR__) . '/repositories/DrillRepository.php';
require_once dirname(__DIR__) . '/controllers/DrillController.php';

// Prepare SQLite database
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Create schema
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, level_id INTEGER, module_code TEXT, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE user_module_progress (id INTEGER PRIMARY KEY, user_id INTEGER, module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER, lesson_name TEXT, lesson_objective TEXT, grammar_notes TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE vocabularies (id INTEGER PRIMARY KEY, target_language_id INTEGER, word TEXT, pronunciation TEXT, part_of_speech TEXT, difficulty TEXT)');
$db->exec('CREATE TABLE exercises (id INTEGER PRIMARY KEY, lesson_id INTEGER, vocabulary_id INTEGER, question_type TEXT, prompt TEXT, correct_answer TEXT, explanation TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE exercise_options (id INTEGER PRIMARY KEY, exercise_id INTEGER, option_text TEXT, is_correct INTEGER)');
$db->exec('CREATE TABLE user_exercise_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, exercise_id INTEGER, is_correct INTEGER, user_answer TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');

// Seed data
$db->exec("INSERT INTO modules (id, course_id, level_id, module_code, status) VALUES (1, 1, 1, 'M1', 'published')");
$db->exec("INSERT INTO lessons (id, module_id, lesson_name, order_index) VALUES (1, 1, 'L1', 1)");
$db->exec("INSERT INTO vocabularies (id, target_language_id, word, pronunciation, part_of_speech, difficulty) VALUES (1, 2, 'hello', 'həˈloʊ', 'int', 'easy')");
$db->exec("INSERT INTO exercises (id, lesson_id, vocabulary_id, question_type, prompt, correct_answer, order_index) VALUES (1, 1, 1, 'typing', 'Ketik halo', 'hello', 1)");
$db->exec("INSERT INTO exercises (id, lesson_id, vocabulary_id, question_type, prompt, correct_answer, order_index) VALUES (2, 1, 1, 'mcq_meaning', 'Arti hello', 'halo', 2)");
$db->exec("INSERT INTO exercise_options (id, exercise_id, option_text, is_correct) VALUES (1, 2, 'halo', 1), (2, 2, 'bukan', 0)");

$drillRepo = new DrillRepository($db);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// 1. Get drills for lesson
$exercises = $drillRepo->getExercisesForLesson(1);
check(count($exercises) === 2, 'Should get 2 exercises');
check($exercises[0]['question_type'] === 'typing', 'First is typing');
check(!isset($exercises[0]['options']), 'Typing has no options');
check(isset($exercises[1]['options']) && count($exercises[1]['options']) === 2, 'MCQ has 2 options');
check(isset($exercises[1]['options'][0]['text']), 'Option must have text alias for frontend compatibility');

// 2. Evaluate answers through repository evaluateAnswer (G-03 fix)
$ex1 = $drillRepo->getExerciseWithAnswer(1);
check($drillRepo->evaluateAnswer($ex1, ' Hello ') === true, 'Typing should be case-insensitive and trimmed');
check($drillRepo->evaluateAnswer($ex1, 'hallo') === false, 'Typing should fail incorrect answer');

$ex2 = $drillRepo->getExerciseWithAnswer(2);
// Option 1 has option_text 'halo' which matches correct_answer 'halo'
check($drillRepo->evaluateAnswer($ex2, '1') === true, 'Submitting option ID 1 should evaluate to true');
// Option 2 has option_text 'bukan'
check($drillRepo->evaluateAnswer($ex2, '2') === false, 'Submitting option ID 2 should evaluate to false');
// Submitting direct string text 'halo'
check($drillRepo->evaluateAnswer($ex2, ' HaLo ') === true, 'Submitting option text should evaluate to true');

// 3. Save attempt
$drillRepo->saveAttempt(1, 1, true, ' Hello ');
$attempts = $db->query("SELECT * FROM user_exercise_attempts")->fetchAll();
check(count($attempts) === 1, 'Should save attempt');
check($attempts[0]['is_correct'] == 1, 'Attempt marked correct');
check($attempts[0]['user_answer'] === ' Hello ', 'Original answer saved');

echo "Drill flow passed\n";
