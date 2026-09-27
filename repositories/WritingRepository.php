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

    public function getExerciseForLesson(int $lessonId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide 
             FROM writing_exercises 
             WHERE lesson_id = :lesson_id'
        );
        $query->execute(['lesson_id' => $lessonId]);
        $exercise = $query->fetch();
        
        if (!$exercise) return null;
        
        $exercise['id'] = (int) $exercise['id'];
        $exercise['lesson_id'] = (int) $exercise['lesson_id'];
        $exercise['minimum_words'] = (int) $exercise['minimum_words'];
        
        return $exercise;
    }

    public function getExerciseById(int $exerciseId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide 
             FROM writing_exercises 
             WHERE id = :id'
        );
        $query->execute(['id' => $exerciseId]);
        $exercise = $query->fetch();
        
        if (!$exercise) return null;
        
        $exercise['id'] = (int) $exercise['id'];
        $exercise['lesson_id'] = (int) $exercise['lesson_id'];
        $exercise['minimum_words'] = (int) $exercise['minimum_words'];
        
        return $exercise;
    }

    public function saveSubmission(int $userId, int $exerciseId, string $submittedText, int $wordCount, bool $passedValidation): void
    {
        $query = $this->db->prepare(
            'INSERT INTO user_writing_submissions (user_id, writing_exercise_id, submitted_text, word_count, passed_validation) 
             VALUES (:user_id, :writing_exercise_id, :submitted_text, :word_count, :passed_validation)'
        );
        $query->execute([
            'user_id' => $userId,
            'writing_exercise_id' => $exerciseId,
            'submitted_text' => $submittedText,
            'word_count' => $wordCount,
            'passed_validation' => $passedValidation ? 1 : 0
        ]);
    }
}
