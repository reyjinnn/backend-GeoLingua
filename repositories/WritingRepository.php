<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class WritingRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    /**
     * Get writing exercise for a lesson.
     */
    public function exerciseForLesson(int $lessonId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, lesson_id, instruction, writing_prompt,
                    required_vocabulary, minimum_words,
                    benchmark_answer, evaluation_guide
             FROM writing_exercises
             WHERE lesson_id = :lesson_id
             LIMIT 1'
        );
        $query->execute(['lesson_id' => $lessonId]);
        $exercise = $query->fetch();
        return $exercise === false ? null : $exercise;
    }

    /**
     * Get writing exercise by ID.
     */
    public function findById(int $exerciseId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, lesson_id, instruction, writing_prompt,
                    required_vocabulary, minimum_words,
                    benchmark_answer, evaluation_guide
             FROM writing_exercises
             WHERE id = :id
             LIMIT 1'
        );
        $query->execute(['id' => $exerciseId]);
        $exercise = $query->fetch();
        return $exercise === false ? null : $exercise;
    }

    /**
     * Save user writing submission.
     */
    public function saveSubmission(
        int $userId,
        int $writingExerciseId,
        string $submittedText,
        int $wordCount,
        bool $passedValidation
    ): void {
        $query = $this->db->prepare(
            'INSERT INTO user_writing_submissions
                (user_id, writing_exercise_id, submitted_text, word_count, passed_validation)
             VALUES (:user_id, :exercise_id, :text, :word_count, :passed)'
        );
        $query->execute([
            'user_id' => $userId,
            'exercise_id' => $writingExerciseId,
            'text' => $submittedText,
            'word_count' => $wordCount,
            'passed' => $passedValidation ? 1 : 0,
        ]);
    }

    /**
     * Check if writing exercise belongs to a lesson in user's course.
     */
    public function exerciseBelongsToUserCourse(int $exerciseId, int $userId): bool
    {
        $query = $this->db->prepare(
            'SELECT COUNT(*) FROM writing_exercises we
             JOIN lessons l ON l.id = we.lesson_id
             JOIN modules m ON m.id = l.module_id
             JOIN user_learning_languages ull ON ull.course_id = m.course_id
             WHERE we.id = :exercise_id
               AND ull.user_id = :user_id
               AND ull.is_primary = 1'
        );
        $query->execute(['exercise_id' => $exerciseId, 'user_id' => $userId]);
        return (int) $query->fetchColumn() > 0;
    }
}