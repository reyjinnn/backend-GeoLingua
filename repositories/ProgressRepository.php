<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class ProgressRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    public function summaryForUser(int $userId): array
    {
        $courseQuery = $this->db->prepare(
            'SELECT course_id FROM user_learning_languages
             WHERE user_id = :user_id AND is_primary = 1 ORDER BY id DESC LIMIT 1'
        );
        $courseQuery->execute(['user_id' => $userId]);
        $courseId = $courseQuery->fetchColumn();
        if ($courseId === false) {
            return $this->emptySummary();
        }

        $completedQuery = $this->db->prepare(
            "SELECT COUNT(*) FROM user_module_progress progress
             JOIN modules module ON module.id = progress.module_id
             WHERE progress.user_id = :user_id AND module.course_id = :course_id
               AND progress.status = 'completed'"
        );
        $completedQuery->execute(['user_id' => $userId, 'course_id' => $courseId]);

        // The schema does not track mastery per word. Count distinct words in completed modules.
        $vocabularyQuery = $this->db->prepare(
            "SELECT COUNT(DISTINCT lesson_vocabulary.vocabulary_id)
             FROM user_module_progress progress
             JOIN modules module ON module.id = progress.module_id
             JOIN lessons lesson ON lesson.module_id = module.id
             JOIN lesson_vocabularies lesson_vocabulary ON lesson_vocabulary.lesson_id = lesson.id
             WHERE progress.user_id = :user_id AND module.course_id = :course_id
               AND progress.status = 'completed'"
        );
        $vocabularyQuery->execute(['user_id' => $userId, 'course_id' => $courseId]);

        $attemptQuery = $this->db->prepare(
            "SELECT attempt.id, module.id AS module_id, module.module_code, module.title AS module_title,
                    attempt.score, attempt.is_passed, attempt.created_at AS attempted_at
             FROM user_quiz_attempts attempt
             JOIN quizzes quiz ON quiz.id = attempt.quiz_id
             JOIN modules module ON module.id = quiz.module_id
             WHERE attempt.user_id = :user_id AND module.course_id = :course_id
             ORDER BY attempt.created_at DESC, attempt.id DESC"
        );
        $attemptQuery->execute(['user_id' => $userId, 'course_id' => $courseId]);
        $attempts = $attemptQuery->fetchAll();
        $scoreTotal = 0;
        foreach ($attempts as &$attempt) {
            $attempt['id'] = (int) $attempt['id'];
            $attempt['module_id'] = (int) $attempt['module_id'];
            $attempt['score'] = (int) $attempt['score'];
            $attempt['is_passed'] = (bool) $attempt['is_passed'];
            $scoreTotal += $attempt['score'];
        }
        unset($attempt);

        return [
            'vocabulary_from_completed_modules' => (int) $vocabularyQuery->fetchColumn(),
            'completed_modules' => (int) $completedQuery->fetchColumn(),
            'average_quiz_score' => $attempts === [] ? null : round($scoreTotal / count($attempts), 1),
            'quiz_attempts' => $attempts,
        ];
    }

    private function emptySummary(): array
    {
        return [
            'vocabulary_from_completed_modules' => 0,
            'completed_modules' => 0,
            'average_quiz_score' => null,
            'quiz_attempts' => [],
        ];
    }
}
