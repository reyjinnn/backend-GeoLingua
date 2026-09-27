<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class DrillRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    public function getExercisesForLesson(int $lessonId): array
    {
        $query = $this->db->prepare(
            'SELECT e.id, e.question_type, e.prompt, e.order_index, 
                    v.word, v.pronunciation
             FROM exercises e
             JOIN vocabularies v ON v.id = e.vocabulary_id
             WHERE e.lesson_id = :lesson_id
             ORDER BY e.order_index ASC'
        );
        $query->execute(['lesson_id' => $lessonId]);
        $exercises = $query->fetchAll();

        foreach ($exercises as &$ex) {
            $ex['id'] = (int) $ex['id'];
            $ex['order_index'] = (int) $ex['order_index'];
            
            // If MCQ, fetch options
            if (in_array($ex['question_type'], ['mcq_meaning', 'mcq_word'], true)) {
                $optQuery = $this->db->prepare('SELECT id, option_text FROM exercise_options WHERE exercise_id = :exercise_id');
                $optQuery->execute(['exercise_id' => $ex['id']]);
                $options = $optQuery->fetchAll();
                shuffle($options);
                foreach ($options as &$opt) {
                    $opt['id'] = (int) $opt['id'];
                    $opt['text'] = $opt['option_text'];
                }
                $ex['options'] = $options;
            }
        }
        
        return $exercises;
    }

    public function getExerciseWithAnswer(int $exerciseId): ?array
    {
        $query = $this->db->prepare(
            'SELECT e.id, e.lesson_id, e.question_type, e.prompt, e.correct_answer, e.explanation
             FROM exercises e
             WHERE e.id = :exercise_id'
        );
        $query->execute(['exercise_id' => $exerciseId]);
        $ex = $query->fetch();
        if (!$ex) return null;
        
        $ex['id'] = (int) $ex['id'];
        $ex['lesson_id'] = (int) $ex['lesson_id'];

        $optQuery = $this->db->prepare('SELECT id, option_text FROM exercise_options WHERE exercise_id = :exercise_id');
        $optQuery->execute(['exercise_id' => $exerciseId]);
        $options = $optQuery->fetchAll();
        foreach ($options as &$opt) {
            $opt['id'] = (int) $opt['id'];
            $opt['text'] = $opt['option_text'];
        }
        $ex['options'] = $options;

        return $ex;
    }

    public function evaluateAnswer(array $exercise, string $userAnswer): bool
    {
        $cleanUser = trim($userAnswer);
        $correct = mb_strtolower(trim((string) $exercise['correct_answer']));

        // Case-insensitive direct text comparison
        if (mb_strtolower($cleanUser) === $correct) {
            return true;
        }

        // If client submitted numeric option ID, match option text against correct_answer
        if (is_numeric($cleanUser) && !empty($exercise['options'])) {
            $optId = (int) $cleanUser;
            foreach ($exercise['options'] as $opt) {
                if ((int) $opt['id'] === $optId) {
                    return mb_strtolower(trim((string) $opt['option_text'])) === $correct;
                }
            }
        }

        return false;
    }

    public function saveAttempt(int $userId, int $exerciseId, bool $isCorrect, string $userAnswer): void
    {
        $query = $this->db->prepare(
            'INSERT INTO user_exercise_attempts (user_id, exercise_id, is_correct, user_answer) 
             VALUES (:user_id, :exercise_id, :is_correct, :user_answer)'
        );
        $query->execute([
            'user_id' => $userId,
            'exercise_id' => $exerciseId,
            'is_correct' => $isCorrect ? 1 : 0,
            'user_answer' => $userAnswer
        ]);
    }
}
