<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$db = databaseConnection();

// Lessons untuk Module 1 (EN-A1-M01)
$lessons = [
    [
        'module_id' => 1,
        'lesson_name' => 'Greetings & Personal Names',
        'lesson_objective' => 'Menguasai sapaan dasar dan cara menyebutkan nama.',
        'grammar_notes' => 'Penggunaan To Be (am, is, are) untuk identitas diri.',
        'order_index' => 1,
    ],
    [
        'module_id' => 1,
        'lesson_name' => 'Origin & Residence',
        'lesson_objective' => 'Menyatakan asal daerah dan tempat tinggal.',
        'grammar_notes' => 'Preposisi from (asal) dan in (tempat tinggal).',
        'order_index' => 2,
    ],
];

$stmt = $db->prepare(
    'INSERT INTO lessons (module_id, lesson_name, lesson_objective, grammar_notes, order_index)
     VALUES (:module_id, :lesson_name, :lesson_objective, :grammar_notes, :order_index)'
);
foreach ($lessons as $lesson) {
    $stmt->execute($lesson);
}
echo "✓ Lessons seeded\n";

// Vocabulary untuk Lesson 1
$vocabularies = [
    ['word' => 'Hello', 'pronunciation' => '/həˈloʊ/', 'pos' => 'interjection', 'translation' => 'Halo / Salam', 'definition' => 'Sapaan universal.', 'example' => 'Hello, nice to meet you.', 'example_trans' => 'Halo, senang bertemu denganmu.'],
    ['word' => 'Name', 'pronunciation' => '/neɪm/', 'pos' => 'noun', 'translation' => 'Nama', 'definition' => 'Sebutan identitas.', 'example' => 'My name is Sarah.', 'example_trans' => 'Nama saya Sarah.'],
    ['word' => 'Meet', 'pronunciation' => '/miːt/', 'pos' => 'verb', 'translation' => 'Bertemu', 'definition' => 'Berjumpa seseorang.', 'example' => 'Pleased to meet you.', 'example_trans' => 'Senang berkenalan dengan Anda.'],
    ['word' => 'Nice', 'pronunciation' => '/naɪs/', 'pos' => 'adjective', 'translation' => 'Menyenangkan', 'definition' => 'Memberikan rasa senang.', 'example' => 'Have a nice day.', 'example_trans' => 'Semoga harimu menyenangkan.'],
    ['word' => 'Morning', 'pronunciation' => '/ˈmɔːr.nɪŋ/', 'pos' => 'noun', 'translation' => 'Pagi', 'definition' => 'Waktu awal hari.', 'example' => 'Good morning, teacher.', 'example_trans' => 'Selamat pagi, guru.'],
];

$vocabStmt = $db->prepare(
    'INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
     VALUES (2, :word, :pronunciation, :part_of_speech, :difficulty)'
);
$transStmt = $db->prepare(
    'INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation)
     VALUES (:vocab_id, 1, :translation, :definition, :example_sentence, :example_translation)'
);
$pivotStmt = $db->prepare(
    'INSERT INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index)
     VALUES (1, :vocab_id, :order_index)'
);

foreach ($vocabularies as $i => $v) {
    $vocabStmt->execute([
        'word' => $v['word'],
        'pronunciation' => $v['pronunciation'],
        'part_of_speech' => $v['pos'],
        'difficulty' => 'easy',
    ]);
    $vocabId = (int) $db->lastInsertId();
    $transStmt->execute([
        'vocab_id' => $vocabId,
        'translation' => $v['translation'],
        'definition' => $v['definition'],
        'example_sentence' => $v['example'],
        'example_translation' => $v['example_trans'],
    ]);
    $pivotStmt->execute(['vocab_id' => $vocabId, 'order_index' => $i + 1]);
}
echo "✓ Vocabulary seeded\n";

echo "\n=== Module 01 content seeding complete ===\n";