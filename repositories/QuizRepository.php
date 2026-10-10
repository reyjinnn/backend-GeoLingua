<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class QuizRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    /**
     * Get quiz by module ID.
     */
    public function quizForModule(int $moduleId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, module_id, title, passing_score, time_limit_minutes
             FROM quizzes
             WHERE module_id = :module_id
             LIMIT 1'
        );
        $query->execute(['module_id' => $moduleId]);
        $quiz = $query->fetch();
        return $quiz === false ? null : $quiz;
    }

    /**
     * Get quiz by ID.
     */
    public function findById(int $quizId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, module_id, title, passing_score, time_limit_minutes
             FROM quizzes
             WHERE id = :id
             LIMIT 1'
        );
        $query->execute(['id' => $quizId]);
        $quiz = $query->fetch();
        return $quiz === false ? null : $quiz;
    }

    /**
     * Get questions for a quiz with options.
     */
    public function questionsForQuiz(int $quizId): array
    {
        $query = $this->db->prepare(
            'SELECT id, quiz_id, question_text, question_type, explanation, score_weight, order_index
             FROM quiz_questions
             WHERE quiz_id = :quiz_id
             ORDER BY order_index, id'
        );
        $query->execute(['quiz_id' => $quizId]);
        $questions = $query->fetchAll();

        if ($questions === []) return [];

        $questionIds = array_map(fn($q) => (int) $q['id'], $questions);
        $optionsByQuestion = $this->optionsForQuestions($questionIds);

        return array_map(function ($q) use ($optionsByQuestion) {
            $q['id'] = (int) $q['id'];
            $q['score_weight'] = (int) $q['score_weight'];
            $q['order_index'] = (int) $q['order_index'];
            $q['options'] = $optionsByQuestion[$q['id']] ?? [];
            return $q;
        }, $questions);
    }

    private function optionsForQuestions(array $questionIds): array
    {
        if ($questionIds === []) return [];

        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $query = $this->db->prepare(
            "SELECT id, question_id, option_text, is_correct
             FROM quiz_options
             WHERE question_id IN ($placeholders)
             ORDER BY id"
        );
        $query->execute($questionIds);

        $result = [];
        foreach ($query->fetchAll() as $row) {
            $result[(int) $row['question_id']][] = [
                'id' => (int) $row['id'],
                'text' => (string) $row['option_text'],
                'is_correct' => (bool) $row['is_correct'],
            ];
        }
        return $result;
    }

    /**
     * Get correct option ID for a question.
     */
    public function correctOptionId(int $questionId): ?int
    {
        $query = $this->db->prepare(
            'SELECT id FROM quiz_options
             WHERE question_id = :question_id AND is_correct = 1
             LIMIT 1'
        );
        $query->execute(['question_id' => $questionId]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /**
     * Save user quiz attempt.
     */
    public function saveAttempt(
        int $userId,
        int $quizId,
        int $score,
        bool $isPassed,
        int $durationSeconds
    ): void {
        $query = $this->db->prepare(
            'INSERT INTO user_quiz_attempts
                (user_id, quiz_id, score, is_passed, attempt_duration_seconds)
             VALUES (:user_id, :quiz_id, :score, :is_passed, :duration)'
        );
        $query->execute([
            'user_id' => $userId,
            'quiz_id' => $quizId,
            'score' => $score,
            'is_passed' => $isPassed ? 1 : 0,
            'duration' => $durationSeconds,
        ]);
    }

    /**
     * Check if quiz belongs to user's course.
     */
    public function quizBelongsToUserCourse(int $quizId, int $userId): bool
    {
        $query = $this->db->prepare(
            'SELECT COUNT(*) FROM quizzes q
             JOIN modules m ON m.id = q.module_id
             JOIN user_learning_languages ull ON ull.course_id = m.course_id
             WHERE q.id = :quiz_id
               AND ull.user_id = :user_id
               AND ull.is_primary = 1'
        );
        $query->execute(['quiz_id' => $quizId, 'user_id' => $userId]);
        return (int) $query->fetchColumn() > 0;
    }

    /**
     * Get module ID for a quiz.
     */
    public function moduleIdForQuiz(int $quizId): ?int
    {
        $query = $this->db->prepare('SELECT module_id FROM quizzes WHERE id = :id LIMIT 1');
        $query->execute(['id' => $quizId]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}