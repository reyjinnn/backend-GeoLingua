<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/ApiException.php';
require_once dirname(__DIR__) . '/helpers/ResponseHelper.php';
require_once dirname(__DIR__) . '/repositories/UserRepository.php';
require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';
require_once dirname(__DIR__) . '/repositories/DrillRepository.php';
require_once dirname(__DIR__) . '/repositories/WritingRepository.php';
require_once dirname(__DIR__) . '/repositories/QuizRepository.php';
require_once dirname(__DIR__) . '/controllers/CurriculumController.php';
require_once dirname(__DIR__) . '/controllers/DrillController.php';
require_once dirname(__DIR__) . '/controllers/WritingController.php';
require_once dirname(__DIR__) . '/controllers/QuizController.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';

// Prepare SQLite database
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Create tables
$db->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT)');
$db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, role_id INTEGER, email TEXT, password_hash TEXT, full_name TEXT)');
$db->exec('CREATE TABLE user_tokens (id INTEGER PRIMARY KEY, user_id INTEGER, token TEXT, expires_at TEXT)');
$db->exec('CREATE TABLE user_learning_languages (id INTEGER PRIMARY KEY, user_id INTEGER, course_id INTEGER, current_level_id INTEGER, is_primary INTEGER)');
$db->exec('CREATE TABLE languages (id INTEGER PRIMARY KEY, code TEXT, name TEXT, native_name TEXT)');
$db->exec('CREATE TABLE levels (id INTEGER PRIMARY KEY, code TEXT, name TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE courses (id INTEGER PRIMARY KEY, title TEXT, base_language_id INTEGER, target_language_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, level_id INTEGER, module_code TEXT, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE user_module_progress (id INTEGER PRIMARY KEY, user_id INTEGER, module_id INTEGER, status TEXT, highest_quiz_score INTEGER DEFAULT 0, completed_at TIMESTAMP NULL)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER, lesson_name TEXT, lesson_objective TEXT, grammar_notes TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE vocabularies (id INTEGER PRIMARY KEY, target_language_id INTEGER, word TEXT, pronunciation TEXT, part_of_speech TEXT, difficulty TEXT)');
$db->exec('CREATE TABLE vocabulary_translations (id INTEGER PRIMARY KEY, vocabulary_id INTEGER, base_language_id INTEGER, translation TEXT, definition TEXT, example_sentence TEXT, example_translation TEXT, notes TEXT)');
$db->exec('CREATE TABLE lesson_vocabularies (lesson_id INTEGER, vocabulary_id INTEGER, order_index INTEGER, PRIMARY KEY (lesson_id, vocabulary_id))');
$db->exec('CREATE TABLE exercises (id INTEGER PRIMARY KEY, lesson_id INTEGER, vocabulary_id INTEGER, question_type TEXT, prompt TEXT, correct_answer TEXT, explanation TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE exercise_options (id INTEGER PRIMARY KEY, exercise_id INTEGER, option_text TEXT, is_correct INTEGER)');
$db->exec('CREATE TABLE user_exercise_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, exercise_id INTEGER, is_correct INTEGER, user_answer TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
$db->exec('CREATE TABLE writing_exercises (id INTEGER PRIMARY KEY, lesson_id INTEGER, instruction TEXT, writing_prompt TEXT, required_vocabulary TEXT, minimum_words INTEGER, benchmark_answer TEXT, evaluation_guide TEXT)');
$db->exec('CREATE TABLE user_writing_submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, writing_exercise_id INTEGER, submitted_text TEXT, word_count INTEGER, passed_validation INTEGER, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
$db->exec('CREATE TABLE quizzes (id INTEGER PRIMARY KEY, module_id INTEGER, title TEXT, passing_score INTEGER, time_limit_minutes INTEGER)');
$db->exec('CREATE TABLE quiz_questions (id INTEGER PRIMARY KEY, quiz_id INTEGER, question_text TEXT, question_type TEXT, score_weight INTEGER, order_index INTEGER)');
$db->exec('CREATE TABLE quiz_options (id INTEGER PRIMARY KEY, question_id INTEGER, option_text TEXT, is_correct INTEGER)');
$db->exec('CREATE TABLE user_quiz_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, quiz_id INTEGER, score INTEGER, is_passed INTEGER, attempt_duration_seconds INTEGER, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');

// Seed data
$token = hash('sha256', 'learner_test_token');
$db->exec("INSERT INTO roles (id, name) VALUES (1, 'learner'), (2, 'admin')");
$db->exec("INSERT INTO users (id, role_id, email, password_hash, full_name) VALUES (1, 1, 'learner@example.com', 'hash', 'Learner One')");
$db->exec("INSERT INTO user_tokens (id, user_id, token, expires_at) VALUES (1, 1, '$token', '2099-01-01 00:00:00')");

$db->exec("INSERT INTO languages (id, code, name, native_name) VALUES (1, 'id', 'Indonesian', 'Bahasa Indonesia'), (2, 'en', 'English', 'English')");
$db->exec("INSERT INTO levels (id, code, name, order_index) VALUES (1, 'A1', 'Beginner', 1), (2, 'A2', 'Elementary', 2)");
$db->exec("INSERT INTO courses (id, title, base_language_id, target_language_id, status) VALUES (1, 'ID -> EN', 1, 2, 'active')");
$db->exec("INSERT INTO user_learning_languages (id, user_id, course_id, current_level_id, is_primary) VALUES (1, 1, 1, 1, 1)");

// Modules: M1 unlocked, M2 requires M1
$db->exec("INSERT INTO modules (id, course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, status) VALUES (1, 1, 1, 'M1', 'Modul 1', 'Greetings', 'Desc 1', 'Obj 1', 30, 1, 'published')");
$db->exec("INSERT INTO modules (id, course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, prerequisite_module_id, status) VALUES (2, 1, 1, 'M2', 'Modul 2', 'Introductions', 'Desc 2', 'Obj 2', 30, 2, 1, 'published')");

// Lesson 1 under Module 1
$db->exec("INSERT INTO lessons (id, module_id, lesson_name, lesson_objective, grammar_notes, order_index) VALUES (1, 1, 'Lesson 1', 'Say Hello', 'Notes 1', 1)");
$db->exec("INSERT INTO vocabularies (id, target_language_id, word, pronunciation, part_of_speech, difficulty) VALUES (1, 2, 'hello', 'həˈloʊ', 'noun', 'easy')");
$db->exec("INSERT INTO vocabulary_translations (id, vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes) VALUES (1, 1, 1, 'halo', 'salam', 'Hello, world!', 'Halo dunia!', 'catatan')");
$db->exec("INSERT INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index) VALUES (1, 1, 1)");

// Drill 1 (Typing) and Drill 2 (MCQ)
$db->exec("INSERT INTO exercises (id, lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index) VALUES (1, 1, 1, 'typing', 'Ketik halo', 'hello', 'Penjelasan typing', 1)");
$db->exec("INSERT INTO exercises (id, lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index) VALUES (2, 1, 1, 'mcq_meaning', 'Pilih arti kata hello', 'halo', 'Penjelasan MCQ', 2)");
$db->exec("INSERT INTO exercise_options (id, exercise_id, option_text, is_correct) VALUES (10, 2, 'halo', 1), (11, 2, 'selamat tinggal', 0)");

// Writing exercise
$db->exec("INSERT INTO writing_exercises (id, lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide) VALUES (1, 1, 'Tulis perkenalan singkat.', 'Tulis sapaan', 'hello', 5, 'Hello my name is John', 'Panduan periksa')");

// Quiz on Module 1 (2 questions, passing score 70)
$db->exec("INSERT INTO quizzes (id, module_id, title, passing_score, time_limit_minutes) VALUES (1, 1, 'Kuis Modul 1', 70, 10)");
$db->exec("INSERT INTO quiz_questions (id, quiz_id, question_text, question_type, score_weight, order_index) VALUES (1, 1, 'Arti kata hello adalah?', 'mcq', 10, 1)");
$db->exec("INSERT INTO quiz_options (id, question_id, option_text, is_correct) VALUES (101, 1, 'halo', 1), (102, 1, 'pagi', 0)");
$db->exec("INSERT INTO quiz_questions (id, quiz_id, question_text, question_type, score_weight, order_index) VALUES (2, 1, 'Bahasa Inggris dari halo adalah?', 'mcq', 10, 2)");
$db->exec("INSERT INTO quiz_options (id, question_id, option_text, is_correct) VALUES (201, 2, 'hello', 1), (202, 2, 'bye', 0)");

$userRepo = new UserRepository($db);
$curriculumRepo = new CurriculumRepository($db);
$drillRepo = new DrillRepository($db);
$writingRepo = new WritingRepository($db);
$quizRepo = new QuizRepository($db);
$authMiddleware = new AuthMiddleware($userRepo);

$curriculumCtrl = new CurriculumController($curriculumRepo, $userRepo, $authMiddleware);
$drillCtrl = new DrillController($drillRepo, $curriculumRepo, $userRepo, $authMiddleware);
$writingCtrl = new WritingController($writingRepo, $curriculumRepo, $userRepo, $authMiddleware);
$quizCtrl = new QuizController($quizRepo, $curriculumRepo, $userRepo, $authMiddleware);

$_SERVER['HTTP_AUTHORIZATION'] = "Bearer $token";

function check(bool $cond, string $msg): void {
    if (!$cond) throw new RuntimeException("Assertion failed: $msg");
}

// TEST 1: Curriculum Module List returns status, is_completed, progress_percentage (G-04)
$modules = $curriculumRepo->getModulesForCourseAndLevel(1, 1, 1);
check($modules[0]['status'] === 'unlocked', 'M1 status must be unlocked');
check($modules[0]['is_completed'] === false, 'M1 is_completed must be false');
check($modules[0]['progress_percentage'] === 0, 'M1 progress_percentage must be 0');
check($modules[1]['status'] === 'locked', 'M2 status must be locked');

// TEST 2: Module Detail returns quiz and vocabulary_count (G-04)
$m1 = $curriculumRepo->getModuleById(1, 1, 1, 1);
check($m1['quiz']['title'] === 'Kuis Modul 1', 'M1 quiz title matches');
check($m1['lessons'][0]['vocabulary_count'] === 1, 'Lesson 1 vocabulary_count is 1');

// TEST 3: Drill Question and Options contract (G-02, G-03)
$drills = $drillRepo->getExercisesForLesson(1);
check(isset($drills[1]['options'][0]['text']), 'Drill option has text field');
$drillOptionTexts = array_map(fn($o) => $o['text'], $drills[1]['options']);
check(in_array('halo', $drillOptionTexts, true), 'Drill option texts contain halo');

// Evaluating option ID (from DrillPage.tsx) vs correct_answer 'halo'
$exMCQ = $drillRepo->getExerciseWithAnswer(2);
check($drillRepo->evaluateAnswer($exMCQ, '10') === true, 'Submitting option ID 10 evaluates to true');
check($drillRepo->evaluateAnswer($exMCQ, '11') === false, 'Submitting option ID 11 evaluates to false');
check($drillRepo->evaluateAnswer($exMCQ, ' halo ') === true, 'Submitting string text evaluates to true');

// TEST 4: Writing Exercise contract (G-02)
$writing = $writingRepo->getExerciseForLesson(1);
check($writing['writing_prompt'] === 'Tulis sapaan', 'Writing prompt matches');

// TEST 5: Quiz API Access and Option Validation (G-05)
// Attempting to access quiz for Module 2 (locked) must throw 403
try {
    $quizCtrl->getQuiz(2);
    check(false, 'Should not allow access to locked module quiz');
} catch (ApiException $e) {
    echo "Caught expected ApiException: status={$e->status}, message={$e->getMessage()}\n";
    check($e->status === 403, 'Access to locked module quiz must return 403');
}

// Quiz 1 getQuiz check
$quizData = $quizRepo->getQuizForModule(1);
check($quizData['quiz_id'] === 1, 'Quiz has quiz_id alias');
check(isset($quizData['questions'][0]['options'][0]['text']), 'Quiz option has text field');
$q1OptionTexts = array_map(fn($o) => $o['text'], $quizData['questions'][0]['options']);
check(in_array('halo', $q1OptionTexts, true), 'Quiz option contains halo text');

// Quiz submit: passing score unlocks Module 2
$saveResult = $quizRepo->saveQuizResultTransaction(1, 1, 1, 100, true, 45);
check($saveResult['next_module_unlocked'] === true, 'Next module unlocked is true');
check($saveResult['unlocked_module_id'] === 2, 'Unlocked module ID is 2');

// Verify Module 2 is now unlocked in module listing!
$modulesAfter = $curriculumRepo->getModulesForCourseAndLevel(1, 1, 1);
check($modulesAfter[0]['status'] === 'completed', 'M1 is now completed');
check($modulesAfter[0]['is_completed'] === true, 'M1 is_completed is true');
check($modulesAfter[1]['status'] === 'unlocked', 'M2 is now unlocked!');

echo "LearnerFlowIntegrationTest passed successfully!\n";
