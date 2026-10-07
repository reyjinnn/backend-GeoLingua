<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$db = databaseConnection();
$moduleId = 1;

// Cek quiz existing
$check = $db->prepare('SELECT id FROM quizzes WHERE module_id = :module_id LIMIT 1');
$check->execute(['module_id' => $moduleId]);
$existingQuizId = $check->fetchColumn();

if ($existingQuizId !== false) {
    echo "✓ Quiz untuk module {$moduleId} udah ada\n";
    exit(0);
}

// Insert quiz
$quizStmt = $db->prepare(
    'INSERT INTO quizzes (module_id, title, passing_score, time_limit_minutes)
     VALUES (:module_id, :title, :passing, :time_limit)'
);
$quizStmt->execute([
    'module_id' => $moduleId,
    'title' => 'Kuis Evaluasi Modul 01',
    'passing' => 70,
    'time_limit' => 15,
]);
$quizId = (int) $db->lastInsertId();

// 10 soal
$questions = [
    ['Apa arti kata "Hello"?', ['Halo', 'Selamat tinggal', 'Terima kasih', 'Sama-sama'], 0],
    ['Lengkapi: "My ___ is Alex."', ['name', 'live', 'meet', 'nice'], 0],
    ['Apa arti kalimat "I live in Surabaya"?', ['Saya bekerja di Surabaya', 'Saya berasal dari Surabaya', 'Saya tinggal di Surabaya', 'Saya pergi ke Surabaya'], 2],
    ['Balasan untuk "Nice to meet you" adalah...', ['Goodbye', 'Nice to meet you too', 'I am from Bali', 'Yes, I work'], 1],
    ['Lengkapi: "She is ___ Canada."', ['to', 'from', 'under', 'with'], 1],
    ['Kelas kata "teacher" adalah...', ['Verb', 'Adjective', 'Noun', 'Adverb'], 2],
    ['Ungkapan saat berpisah adalah...', ['Hello', 'Goodbye', 'Good morning', 'Pleased'], 1],
    ['Susunan kalimat yang benar...', ['I student am a', 'I am a student', 'Student a am I', 'Am I student a'], 1],
    ['Lawan kata "morning" adalah...', ['afternoon', 'evening / night', 'noon', 'clock'], 1],
    ['Apa arti kata "friend"?', ['Teman / Sahabat', 'Guru', 'Tetangga', 'Orang asing'], 0],
];

$questionStmt = $db->prepare(
    'INSERT INTO quiz_questions (quiz_id, question_text, question_type, explanation, score_weight, order_index)
     VALUES (:quiz_id, :text, :type, :explanation, :weight, :order_index)'
);
$optionStmt = $db->prepare(
    'INSERT INTO quiz_options (question_id, option_text, is_correct)
     VALUES (:question_id, :text, :is_correct)'
);

foreach ($questions as $i => $data) {
    [$questionText, $options, $correctIndex] = $data;
    
    $questionStmt->execute([
        'quiz_id' => $quizId,
        'text' => $questionText,
        'type' => 'multiple_choice',
        'explanation' => '',
        'weight' => 10,
        'order_index' => $i + 1,
    ]);
    $questionId = (int) $db->lastInsertId();

    foreach ($options as $optIndex => $optionText) {
        $optionStmt->execute([
            'question_id' => $questionId,
            'text' => $optionText,
            'is_correct' => $optIndex === $correctIndex ? 1 : 0,
        ]);
    }
}

echo "✓ Quiz {$quizId} seeded untuk module {$moduleId}\n";
echo "✓ Total: " . count($questions) . " soal\n";