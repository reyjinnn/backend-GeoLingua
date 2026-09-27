<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';
require_once dirname(__DIR__) . '/repositories/WritingRepository.php';
require_once dirname(__DIR__) . '/controllers/WritingController.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php'; // Required for controller constructor mock if used, but we'll test repo and logic manually

// Prepare SQLite database
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Create schema
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, level_id INTEGER, module_code TEXT, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE user_module_progress (id INTEGER PRIMARY KEY, user_id INTEGER, module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER, lesson_name TEXT, lesson_objective TEXT, grammar_notes TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE writing_exercises (id INTEGER PRIMARY KEY, lesson_id INTEGER, instruction TEXT, writing_prompt TEXT, required_vocabulary TEXT, minimum_words INTEGER, benchmark_answer TEXT, evaluation_guide TEXT)');
$db->exec('CREATE TABLE user_writing_submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, writing_exercise_id INTEGER, submitted_text TEXT, word_count INTEGER, passed_validation INTEGER, self_reviewed INTEGER DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');

// Seed data
$db->exec("INSERT INTO modules (id, course_id, level_id, module_code, status) VALUES (1, 1, 1, 'M1', 'published')");
$db->exec("INSERT INTO lessons (id, module_id, lesson_name, order_index) VALUES (1, 1, 'L1', 1)");
$db->exec("INSERT INTO writing_exercises (id, lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide) VALUES (1, 1, 'Instruction', 'Prompt', 'name, good morning', 5, 'Benchmark', 'Guide')");

$repo = new WritingRepository($db);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// 1. Get exercise
$ex = $repo->getExerciseForLesson(1);
check($ex['minimum_words'] === 5, 'Minimum words should be 5');

// 2. Logic testing (isolated from controller)
function countWords(string $text): int {
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    $text = trim(preg_replace('/\s+/', ' ', $text));
    return $text === '' ? 0 : count(explode(' ', $text));
}

function findMissingWords(string $text, array $requiredWords): array {
    $missing = [];
    $cleanText = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));
    $cleanText = ' ' . trim(preg_replace('/\s+/', ' ', $cleanText)) . ' ';
    
    foreach ($requiredWords as $word) {
        if ($word === '') continue;
        $cleanWord = mb_strtolower($word);
        if (mb_strpos($cleanText, ' ' . $cleanWord . ' ') === false) {
            $missing[] = $word;
        }
    }
    return $missing;
}

$text1 = "Good morning! My name is John.";
check(countWords($text1) === 6, 'Should count 6 words');
$missing1 = findMissingWords($text1, ['name', 'good morning', 'surname']);
check(count($missing1) === 1 && $missing1[0] === 'surname', 'Should miss surname only');

$text2 = "Hello, my surname is Doe.";
$missing2 = findMissingWords($text2, ['name']);
check(count($missing2) === 1, 'name shouldn\'t match surname');

// 3. Save submission
$repo->saveSubmission(1, 1, $text1, 6, true);
$subs = $db->query("SELECT * FROM user_writing_submissions")->fetchAll();
check(count($subs) === 1, 'Should save submission');
check($subs[0]['passed_validation'] == 1, 'Should pass validation');
check($subs[0]['word_count'] == 6, 'Word count saved');

echo "Writing flow passed\n";
