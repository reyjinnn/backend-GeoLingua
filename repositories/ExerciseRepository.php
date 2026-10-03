<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class ExerciseRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    /**
     * Get all drill exercises for a lesson.
     */
    public function drillsForLesson(int $lessonId): array
    {
        $query = $this->db->prepare(
            'SELECT id, lesson_id, vocabulary_id, question_type, prompt,
                    correct_answer, explanation, order_index
             FROM exercises
             WHERE lesson_id = :lesson_id
             ORDER BY order_index, id'
        );
        $query->execute(['lesson_id' => $lessonId]);
        return $query->fetchAll();
    }

    /**
     * Get options for MCQ exercises.
     */
    public function optionsForExercises(array $exerciseIds): array
    {
        if ($exerciseIds === []) return [];

        $placeholders = implode(',', array_fill(0, count($exerciseIds), '?'));
        $query = $this->db->prepare(
            "SELECT id, exercise_id, option_text, is_correct
             FROM exercise_options
             WHERE exercise_id IN ($placeholders)
             ORDER BY id"
        );
        $query->execute($exerciseIds);

        $result = [];
        foreach ($query->fetchAll() as $row) {
            $result[(int) $row['exercise_id']][] = [
                'id' => (int) $row['id'],
                'text' => $row['option_text'],
            ];
        }
        return $result;
    }

    /**
     * Get single exercise by ID (buat evaluasi jawaban).
     */
    public function findById(int $exerciseId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, lesson_id, vocabulary_id, question_type, prompt,
                    correct_answer, explanation
             FROM exercises
             WHERE id = :id
             LIMIT 1'
        );
        $query->execute(['id' => $exerciseId]);
        $exercise = $query->fetch();
        return $exercise === false ? null : $exercise;
    }

    /**
     * Get correct option ID for MCQ exercise.
     */
    public function correctOptionId(int $exerciseId): ?int
    {
        $query = $this->db->prepare(
            'SELECT id FROM exercise_options
             WHERE exercise_id = :id AND is_correct = 1
             LIMIT 1'
        );
        $query->execute(['id' => $exerciseId]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /**
     * Check if exercise belongs to a lesson in user's course.
     */
    public function exerciseBelongsToUserCourse(int $exerciseId, int $userId): bool
    {
        $query = $this->db->prepare(
            'SELECT COUNT(*) FROM exercises e
             JOIN lessons l ON l.id = e.lesson_id
             JOIN modules m ON m.id = l.module_id
             JOIN user_learning_languages ull ON ull.course_id = m.course_id
             WHERE e.id = :exercise_id
               AND ull.user_id = :user_id
               AND ull.is_primary = 1'
        );
        $query->execute(['exercise_id' => $exerciseId, 'user_id' => $userId]);
        return (int) $query->fetchColumn() > 0;
    }
}