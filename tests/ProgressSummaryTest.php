<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/repositories/ProgressRepository.php';
require_once dirname(__DIR__) . '/controllers/ProgressController.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec('CREATE TABLE user_learning_languages (id INTEGER PRIMARY KEY, user_id INTEGER, course_id INTEGER, is_primary INTEGER)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, module_code TEXT, title TEXT, status TEXT)');
$db->exec('CREATE TABLE user_module_progress (user_id INTEGER, module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER)');
$db->exec('CREATE TABLE lesson_vocabularies (lesson_id INTEGER, vocabulary_id INTEGER)');
$db->exec('CREATE TABLE quizzes (id INTEGER PRIMARY KEY, module_id INTEGER)');
$db->exec('CREATE TABLE user_quiz_attempts (id INTEGER PRIMARY KEY, user_id INTEGER, quiz_id INTEGER, score INTEGER, is_passed INTEGER, created_at TEXT)');
$db->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT)');
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, role_id INTEGER, full_name TEXT, email TEXT)');
$db->exec('CREATE TABLE user_tokens (user_id INTEGER, token TEXT, expires_at TEXT)');

$db->exec("INSERT INTO user_learning_languages VALUES (1, 1, 10, 1), (2, 1, 20, 0), (3, 2, 10, 1)");
$db->exec("INSERT INTO modules VALUES
    (101, 10, 'M01', 'Introductions', 'published'),
    (102, 10, 'M02', 'Daily life', 'published'),
    (103, 20, 'M03', 'Other course', 'published'),
    (104, 10, 'M04', 'Archived module', 'archived')");
$db->exec("INSERT INTO user_module_progress VALUES
    (1, 101, 'completed'), (1, 102, 'in_progress'), (1, 103, 'completed'), (1, 104, 'completed'),
    (2, 102, 'completed')");
$db->exec('INSERT INTO lessons VALUES (201, 101), (202, 101), (203, 103), (204, 104), (205, 102)');
$db->exec('INSERT INTO lesson_vocabularies VALUES (201, 1), (201, 2), (202, 2), (202, 3), (203, 4), (204, 5), (205, 6)');
$db->exec('INSERT INTO quizzes VALUES (301, 101), (302, 102), (303, 103), (304, 104)');
$db->exec("INSERT INTO user_quiz_attempts VALUES
    (401, 1, 301, 80, 1, '2026-09-25 10:00:00'),
    (402, 1, 302, 60, 0, '2026-09-26 10:00:00'),
    (403, 1, 303, 100, 1, '2026-09-27 10:00:00'),
    (404, 1, 304, 100, 1, '2026-09-27 11:00:00'),
    (405, 2, 301, 40, 0, '2026-09-27 12:00:00')");
$db->exec("INSERT INTO roles VALUES (1, 'learner'), (2, 'admin')");
$db->exec("INSERT INTO users VALUES (1, 1, 'Learner', 'learner@example.com'), (2, 2, 'Admin', 'admin@example.com')");
$db->exec("INSERT INTO user_tokens VALUES
    (1, '" . str_repeat('a', 64) . "', '2099-01-01 00:00:00'),
    (2, '" . str_repeat('b', 64) . "', '2099-01-01 00:00:00')");

function checkProgress(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$repository = new ProgressRepository($db);
$summary = $repository->summaryForUser(1);
checkProgress($summary['completed_modules'] === 2, 'Completed modules should remain counted after archival');
checkProgress($summary['vocabulary_from_completed_modules'] === 4, 'Vocabulary should be distinct and limited to completed modules');
checkProgress($summary['average_quiz_score'] === 80.0, 'Average should include archived-module attempts in the active course');
checkProgress(array_column($summary['quiz_attempts'], 'id') === [404, 402, 401], 'Quiz history should be recent first and scoped to the learner/course');
checkProgress($summary['quiz_attempts'][1]['is_passed'] === false, 'Failed attempts should keep a boolean status');

$db->exec('UPDATE user_learning_languages SET is_primary = CASE WHEN course_id = 20 THEN 1 ELSE 0 END WHERE user_id = 1');
$otherCourse = $repository->summaryForUser(1);
checkProgress($otherCourse['completed_modules'] === 1 && $otherCourse['vocabulary_from_completed_modules'] === 1, 'Changing active course should change the module and vocabulary scope');
checkProgress($otherCourse['average_quiz_score'] === 100.0 && count($otherCourse['quiz_attempts']) === 1, 'Quiz results should follow the active course');

$withoutCourse = $repository->summaryForUser(3);
checkProgress($withoutCourse['completed_modules'] === 0 && $withoutCourse['vocabulary_from_completed_modules'] === 0, 'A learner without a course should have zero counts');
checkProgress($withoutCourse['average_quiz_score'] === null && $withoutCourse['quiz_attempts'] === [], 'A learner without a course should have no quiz history');

$db->exec('UPDATE user_learning_languages SET is_primary = CASE WHEN course_id = 10 THEN 1 ELSE 0 END WHERE user_id = 1');
$controller = new ProgressController($repository, new AuthMiddleware(new UserRepository($db)));
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . str_repeat('a', 64);
ob_start();
$controller->summary();
$response = json_decode((string) ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
checkProgress($response['success'] === true && $response['data']['completed_modules'] === 2, 'Authenticated learner should receive their summary');

$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . str_repeat('b', 64);
try {
    $controller->summary();
    throw new RuntimeException('Admin should not access the learner progress endpoint');
} catch (ApiException $error) {
    checkProgress($error->errorCode === 'FORBIDDEN', 'Admin should receive FORBIDDEN');
}

unset($_SERVER['HTTP_AUTHORIZATION']);
try {
    $controller->summary();
    throw new RuntimeException('A missing token should not access learner progress');
} catch (ApiException $error) {
    checkProgress($error->errorCode === 'UNAUTHORIZED', 'Missing token should receive UNAUTHORIZED');
}

echo "Progress summary passed\n";
