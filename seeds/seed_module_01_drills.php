<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$db = databaseConnection();
$lessonId = 1;

$drills = [
    [
        'type' => 'mcq_meaning',
        'prompt' => 'Pilih arti kata yang tepat untuk: "Hello"',
        'correct_answer' => 'Halo / Salam',
        'explanation' => '"Hello" adalah sapaan ramah dalam bahasa Inggris.',
        'options' => [
            ['Halo / Salam', true],
            ['Selamat tinggal', false],
            ['Terima kasih', false],
            ['Sama-sama', false],
        ],
    ],
    [
        'type' => 'mcq_meaning',
        'prompt' => 'Pilih arti kata yang tepat untuk: "Name"',
        'correct_answer' => 'Nama',
        'explanation' => '"Name" berarti nama.',
        'options' => [
            ['Nama', true],
            ['Alamat', false],
            ['Umur', false],
            ['Pekerjaan', false],
        ],
    ],
    [
        'type' => 'mcq_meaning',
        'prompt' => 'Pilih arti kata yang tepat untuk: "Meet"',
        'correct_answer' => 'Bertemu / Berkenalan',
        'explanation' => '"Meet" berarti bertemu.',
        'options' => [
            ['Bertemu / Berkenalan', true],
            ['Makan', false],
            ['Tidur', false],
            ['Berjalan', false],
        ],
    ],
    [
        'type' => 'mcq_meaning',
        'prompt' => 'Pilih arti kata yang tepat untuk: "Nice"',
        'correct_answer' => 'Menyenangkan',
        'explanation' => '"Nice" berarti menyenangkan.',
        'options' => [
            ['Menyenangkan', true],
            ['Menyedihkan', false],
            ['Marah', false],
            ['Lapar', false],
        ],
    ],
    [
        'type' => 'mcq_meaning',
        'prompt' => 'Pilih arti kata yang tepat untuk: "Morning"',
        'correct_answer' => 'Pagi',
        'explanation' => '"Morning" berarti pagi.',
        'options' => [
            ['Pagi', true],
            ['Malam', false],
            ['Siang', false],
            ['Sore', false],
        ],
    ],
    [
        'type' => 'typing',
        'prompt' => 'Ketik terjemahan bahasa Inggris untuk: "Nama"',
        'correct_answer' => 'name',
        'explanation' => 'Terjemahan bahasa Inggris untuk "Nama" adalah "name".',
    ],
    [
        'type' => 'typing',
        'prompt' => 'Ketik terjemahan bahasa Inggris untuk: "Bertemu"',
        'correct_answer' => 'meet',
        'explanation' => 'Terjemahan bahasa Inggris untuk "Bertemu" adalah "meet".',
    ],
    [
        'type' => 'typing',
        'prompt' => 'Ketik terjemahan bahasa Inggris untuk: "Pagi"',
        'correct_answer' => 'morning',
        'explanation' => 'Terjemahan bahasa Inggris untuk "Pagi" adalah "morning".',
    ],
    [
        'type' => 'typing',
        'prompt' => 'Ketik terjemahan bahasa Inggris untuk: "Menyenangkan"',
        'correct_answer' => 'nice',
        'explanation' => 'Terjemahan bahasa Inggris untuk "Menyenangkan" adalah "nice".',
    ],
    [
        'type' => 'typing',
        'prompt' => 'Ketik terjemahan bahasa Inggris untuk: "Halo"',
        'correct_answer' => 'hello',
        'explanation' => 'Terjemahan bahasa Inggris untuk "Halo" adalah "hello".',
    ],
];

$exerciseStmt = $db->prepare(
    'INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
     VALUES (:lesson_id, :vocab_id, :type, :prompt, :answer, :explanation, :order_index)'
);
$optionStmt = $db->prepare(
    'INSERT INTO exercise_options (exercise_id, option_text, is_correct)
     VALUES (:exercise_id, :text, :is_correct)'
);

foreach ($drills as $i => $drill) {
    $exerciseStmt->execute([
        'lesson_id' => $lessonId,
        'vocab_id' => (($i % 5) + 1),
        'type' => $drill['type'],
        'prompt' => $drill['prompt'],
        'answer' => $drill['correct_answer'],
        'explanation' => $drill['explanation'],
        'order_index' => $i + 1,
    ]);
    $exerciseId = (int) $db->lastInsertId();

    foreach ($drill['options'] ?? [] as [$text, $isCorrect]) {
        $optionStmt->execute([
            'exercise_id' => $exerciseId,
            'text' => $text,
            'is_correct' => $isCorrect ? 1 : 0,
        ]);
    }
}

echo "✓ " . count($drills) . " drills seeded\n";