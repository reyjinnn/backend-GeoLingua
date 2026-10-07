<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/services/WritingValidationService.php';
require_once dirname(__DIR__) . '/repositories/WritingRepository.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Setup tables
$db->exec('CREATE TABLE writing_exercises (id INTEGER PRIMARY KEY, lesson_id INTEGER, instruction TEXT, writing_prompt TEXT, required_vocabulary TEXT, minimum_words INTEGER, benchmark_answer TEXT, evaluation_guide TEXT)');
$db->exec('CREATE TABLE user_writing_submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, writing_exercise_id INTEGER, submitted_text TEXT, word_count INTEGER, passed_validation INTEGER, self_reviewed INTEGER DEFAULT 0, created_at TEXT)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER)');
$db->exec('CREATE TABLE user_learning_languages (user_id INTEGER, course_id INTEGER, is_primary INTEGER)');

// Seed test data
$db->exec("INSERT INTO writing_exercises VALUES (1, 1, 'Tuliskan perkenalan diri.', 'Sebutkan salam, nama, kota.', 'hello,name,live,meet', 15, 'Hello! My name is Rian.', 'Periksa huruf kapital.')");
$db->exec("INSERT INTO lessons VALUES (1, 1)");
$db->exec("INSERT INTO modules VALUES (1, 1)");
$db->exec("INSERT INTO user_learning_languages VALUES (1, 1, 1)");

function checkWriting(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectWritingError(callable $action, string $code): void
{
    try {
        $action();
    } catch (ApiException $e) {
        checkWriting($e->errorCode === $code, "Expected {$code}; received {$e->errorCode}");
        return;
    }
    throw new RuntimeException("Expected {$code} error");
}

$repo = new WritingRepository($db);
$service = new WritingValidationService($repo);

// Test 1: Prompt for lesson
$prompt = $service->promptForLesson(1);
checkWriting($prompt['id'] === 1, 'Prompt ID should be 1');
checkWriting($prompt['required_vocabulary'] === ['hello', 'name', 'live', 'meet'], 'Required vocab should be parsed');
checkWriting($prompt['minimum_words'] === 15, 'Minimum words should be 15');
checkWriting($prompt['instruction'] === 'Tuliskan perkenalan diri.', 'Instruction should match');

// Test 2: Valid submission
$result = $service->validateSubmission(1, 1, 'Hello my name is Rian and I live in Jakarta nice to meet you today friends');
checkWriting($result['passed_validation'] === true, 'Should pass validation');
checkWriting($result['missing_required_words'] === [], 'No missing words');
checkWriting($result['word_count'] >= 15, 'Word count should be >= 15');

// Test 3: Missing required word
$result = $service->validateSubmission(1, 1, 'Hello my name is Rian and I live in Jakarta');
checkWriting($result['passed_validation'] === false, 'Should fail (missing "meet")');
checkWriting(in_array('meet', $result['missing_required_words'], true), 'Should list "meet" as missing');
checkWriting($result['word_count'] === 10, 'Word count should be 10');

// Test 4: Too short (all words present, but < 15 words)
$result = $service->validateSubmission(1, 1, 'Hello name live meet');
checkWriting($result['passed_validation'] === false, 'Should fail (too short)');
checkWriting($result['word_count'] === 4, 'Word count should be 4');
checkWriting($result['missing_required_words'] === [], 'All words present');

// Test 5: Case-insensitive matching
$result = $service->validateSubmission(1, 1, 'HELLO NAME LIVE MEET hello name live meet hello name live meet hello name live');
checkWriting($result['missing_required_words'] === [], 'Case-insensitive should work');
checkWriting($result['passed_validation'] === true, 'Should pass (15+ words)');

// Test 6: Prompt for non-existent lesson
expectWritingError(fn() => $service->promptForLesson(999), 'WRITING_NOT_FOUND');

// Test 7: Submission for non-existent exercise
expectWritingError(fn() => $service->validateSubmission(1, 999, 'Hello'), 'WRITING_NOT_FOUND');

// Test 8: exerciseBelongsToUserCourse
checkWriting($repo->exerciseBelongsToUserCourse(1, 1) === true, 'User 1 should access exercise 1');
checkWriting($repo->exerciseBelongsToUserCourse(1, 999) === false, 'User 999 should not access');
checkWriting($repo->exerciseBelongsToUserCourse(999, 1) === false, 'Exercise 999 should not exist');

// Test 9: Submission saved to DB (4 submissions: Test 2, 3, 4, 5)
$count = (int) $db->query('SELECT COUNT(*) FROM user_writing_submissions')->fetchColumn();
checkWriting($count === 4, "Expected 4 submissions saved, got {$count}");

echo "Writing test passed\n";