<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/repositories/CurriculumRepository.php';

// Prepare SQLite database
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Create tables
$db->exec('CREATE TABLE modules (id INTEGER PRIMARY KEY, course_id INTEGER, level_id INTEGER, module_code TEXT, title TEXT, topic TEXT, description TEXT, learning_objectives TEXT, estimated_duration_minutes INTEGER, order_index INTEGER, prerequisite_module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE user_module_progress (id INTEGER PRIMARY KEY, user_id INTEGER, module_id INTEGER, status TEXT)');
$db->exec('CREATE TABLE lessons (id INTEGER PRIMARY KEY, module_id INTEGER, lesson_name TEXT, lesson_objective TEXT, grammar_notes TEXT, order_index INTEGER)');
$db->exec('CREATE TABLE vocabularies (id INTEGER PRIMARY KEY, target_language_id INTEGER, word TEXT, pronunciation TEXT, part_of_speech TEXT, difficulty TEXT)');
$db->exec('CREATE TABLE vocabulary_translations (id INTEGER PRIMARY KEY, vocabulary_id INTEGER, base_language_id INTEGER, translation TEXT, definition TEXT, example_sentence TEXT, example_translation TEXT, notes TEXT)');
$db->exec('CREATE TABLE lesson_vocabularies (lesson_id INTEGER, vocabulary_id INTEGER, order_index INTEGER, PRIMARY KEY (lesson_id, vocabulary_id))');

// Seed test data
// 2 modules, 1 requires the other
$db->exec("INSERT INTO modules (id, course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, status) VALUES (1, 1, 1, 'M1', 'Mod 1', 'T1', 'Desc 1', 'Obj 1', 30, 1, 'published')");
$db->exec("INSERT INTO modules (id, course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, prerequisite_module_id, status) VALUES (2, 1, 1, 'M2', 'Mod 2', 'T2', 'Desc 2', 'Obj 2', 30, 2, 1, 'published')");

$db->exec("INSERT INTO lessons (id, module_id, lesson_name, lesson_objective, grammar_notes, order_index) VALUES (1, 1, 'L1', 'Obj L1', 'Grammar 1', 1)");
$db->exec("INSERT INTO vocabularies (id, target_language_id, word, pronunciation, part_of_speech, difficulty) VALUES (1, 2, 'hello', 'həˈloʊ', 'int', 'easy')");
$db->exec("INSERT INTO vocabulary_translations (id, vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes) VALUES (1, 1, 1, 'halo', 'def', 'ex', 'ex_trans', 'note')");
$db->exec("INSERT INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index) VALUES (1, 1, 1)");

$repo = new CurriculumRepository($db);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// 1. Module listing for User 1 (no progress)
$modules = $repo->getModulesForCourseAndLevel(1, 1, 1);
check(count($modules) === 2, 'Should list 2 modules');
check($modules[0]['progress_status'] === 'unlocked', 'Module 1 should be unlocked');
check($modules[1]['progress_status'] === 'locked', 'Module 2 should be locked due to missing prerequisite');

// 2. Fetch module 2 details directly (should return locked status)
$mod2 = $repo->getModuleById(2, 1, 1, 1);
check($mod2['progress_status'] === 'locked', 'Module 2 details should show locked');

// 3. User 1 completes Module 1
$db->exec("INSERT INTO user_module_progress (user_id, module_id, status) VALUES (1, 1, 'completed')");

// 4. Check Module 2 again
$modulesAfter = $repo->getModulesForCourseAndLevel(1, 1, 1);
check($modulesAfter[1]['progress_status'] === 'unlocked', 'Module 2 should be unlocked after prerequisite is met');

$mod2After = $repo->getModuleById(2, 1, 1, 1);
check($mod2After['progress_status'] === 'unlocked', 'Module 2 details should show unlocked');

// 5. Lesson and Vocabulary
$lesson = $repo->getLessonById(1, 1);
check($lesson['lesson_name'] === 'L1', 'Lesson should match');
check($lesson['progress_status'] === 'completed', 'Lesson inherits module progress (if completed, wait, logic for lesson progress is same as module unlocking)');
// Actually, if module 1 is completed, the lesson in module 1 is still technically 'unlocked' or 'completed' based on prereq, but our logic only checks the prereq of the module.
// Since module 1 has no prereq, lesson 1 is 'unlocked' unless module is completed, wait, my logic checks if lesson has no prereq, it's unlocked. That's fine.

$vocab = $repo->getVocabularyForLesson(1, 1);
check(count($vocab) === 1, 'Should fetch 1 vocabulary');
check($vocab[0]['word'] === 'hello', 'Vocabulary should match');

echo "Curriculum flow passed\n";
