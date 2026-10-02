<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class VocabularyRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    public function vocabularyForLesson(int $lessonId, int $baseLanguageId): array
    {
        $query = $this->db->prepare(
            'SELECT v.id, v.word, v.pronunciation, v.part_of_speech, v.difficulty,
                    vt.translation, vt.definition, vt.example_sentence, vt.example_translation
             FROM vocabularies v
             JOIN vocabulary_translations vt ON vt.vocabulary_id = v.id
             JOIN lesson_vocabularies lv ON lv.vocabulary_id = v.id
             WHERE lv.lesson_id = :lesson_id AND vt.base_language_id = :base_language_id
             ORDER BY lv.order_index, v.id'
        );
        $query->execute(['lesson_id' => $lessonId, 'base_language_id' => $baseLanguageId]);
        $rows = $query->fetchAll();
        return array_map(fn($r) => [
            'id' => (int) $r['id'],
            'word' => $r['word'],
            'pronunciation' => $r['pronunciation'],
            'part_of_speech' => $r['part_of_speech'],
            'difficulty' => $r['difficulty'],
            'translation' => $r['translation'],
            'definition' => $r['definition'],
            'example_sentence' => $r['example_sentence'],
            'example_translation' => $r['example_translation'],
        ], $rows);
    }

    public function lessonBelongsToUserCourse(int $lessonId, int $userId): bool
    {
        $query = $this->db->prepare(
            'SELECT COUNT(*) FROM lessons l
             JOIN modules m ON m.id = l.module_id
             JOIN user_learning_languages ull ON ull.course_id = m.course_id
             WHERE l.id = :lesson_id AND ull.user_id = :user_id AND ull.is_primary = 1'
        );
        $query->execute(['lesson_id' => $lessonId, 'user_id' => $userId]);
        return (int) $query->fetchColumn() > 0;
    }
}