<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$db = databaseConnection();
$lessonId = 1;

// Cek apakah udah ada writing exercise
$check = $db->prepare('SELECT id FROM writing_exercises WHERE lesson_id = :lesson_id LIMIT 1');
$check->execute(['lesson_id' => $lessonId]);
if ($check->fetchColumn() !== false) {
    echo "✓ Writing exercise untuk lesson {$lessonId} udah ada\n";
    exit(0);
}

$stmt = $db->prepare(
    'INSERT INTO writing_exercises
        (lesson_id, instruction, writing_prompt, required_vocabulary,
         minimum_words, benchmark_answer, evaluation_guide)
     VALUES (:lesson_id, :instruction, :prompt, :required_vocab,
             :minimum_words, :benchmark, :guide)'
);

$stmt->execute([
    'lesson_id' => $lessonId,
    'instruction' => 'Tuliskan paragraf perkenalan diri minimal 15 kata dalam bahasa Inggris.',
    'prompt' => 'Sebutkan salam, nama lengkap, kota tempat tinggal, dan ungkapan senang bertemu.',
    'required_vocab' => 'hello,name,live,meet',
    'minimum_words' => 15,
    'benchmark' => 'Hello! My name is Rian Pratama. I live in Jakarta, Indonesia. Nice to meet you all today.',
    'guide' => 'Periksa: (1) huruf kapital di awal kalimat & nama, (2) tanda titik di akhir, (3) semua kata wajib muncul.',
]);

echo "✓ Writing exercise seeded untuk lesson {$lessonId}\n";