<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$db = databaseConnection();

$courseId = 1;  // ID -> EN
$levelId = 1;   // A1

// Insert 3 modul
$modules = [
    [
        'module_code' => 'EN-A1-M01',
        'title' => 'Perkenalan Sehari-hari (Daily Introduction)',
        'topic' => 'Personal Information',
        'description' => 'Pelajari cara menyapa, menyebutkan nama, asal negara, dan profesi.',
        'learning_objectives' => 'Mampu memperkenalkan diri dan menanyakan informasi identitas.',
        'order_index' => 1,
        'prerequisite_module_id' => null,
    ],
    [
        'module_code' => 'EN-A1-M02',
        'title' => 'Aktivitas Harian (Daily Activities)',
        'topic' => 'Daily Routines',
        'description' => 'Pelajari kosakata aktivitas sehari-hari.',
        'learning_objectives' => 'Mampu mendeskripsikan rutinitas harian.',
        'order_index' => 2,
        'prerequisite_module_id' => 1,
    ],
    [
        'module_code' => 'EN-A1-M03',
        'title' => 'Keluarga & Hubungan (Family & Relations)',
        'topic' => 'Family Members',
        'description' => 'Pelajari kosakata keluarga.',
        'learning_objectives' => 'Mampu menyebutkan anggota keluarga.',
        'order_index' => 3,
        'prerequisite_module_id' => 2,
    ],
];

$stmt = $db->prepare(
    'INSERT INTO modules (course_id, level_id, module_code, title, topic, description, learning_objectives, order_index, prerequisite_module_id, status)
     VALUES (:course_id, :level_id, :module_code, :title, :topic, :description, :learning_objectives, :order_index, :prerequisite_module_id, :status)
     ON DUPLICATE KEY UPDATE title = VALUES(title)'
);

foreach ($modules as $module) {
    $stmt->execute(array_merge($module, [
        'course_id' => $courseId,
        'level_id' => $levelId,
        'status' => 'published',
    ]));
    echo "✓ Module {$module['module_code']} seeded\n";
}

echo "\n=== Module seeding complete ===\n";