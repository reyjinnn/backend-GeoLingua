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

    public function getQuizForModule(int $moduleId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, title, passing_score, time_limit_minutes
             FROM quizzes
             WHERE module_id = :module_id'
        );
        $query->execute(['module_id' => $moduleId]);
        $quiz = $query->fetch();
        
        if (!$quiz) return null;
        
        $quiz['id'] = (int) $quiz['id'];
        $quiz['quiz_id'] = $quiz['id'];
        $quiz['passing_score'] = (int) $quiz['passing_score'];
        $quiz['time_limit_minutes'] = (int) $quiz['time_limit_minutes'];
        
        // Fetch questions
        $qQuery = $this->db->prepare(
            'SELECT id, question_text, question_type, score_weight, order_index
             FROM quiz_questions
             WHERE quiz_id = :quiz_id
             ORDER BY order_index ASC'
        );
        $qQuery->execute(['quiz_id' => $quiz['id']]);
        $questions = $qQuery->fetchAll();
        
        foreach ($questions as &$q) {
            $q['id'] = (int) $q['id'];
            $q['score_weight'] = (int) $q['score_weight'];
            $q['order_index'] = (int) $q['order_index'];
            
            $oQuery = $this->db->prepare('SELECT id, option_text FROM quiz_options WHERE question_id = :question_id');
            $oQuery->execute(['question_id' => $q['id']]);
            $options = $oQuery->fetchAll();
            shuffle($options); // PHP shuffle instead of ORDER BY RAND for SQLite compatibility
            foreach ($options as &$opt) {
                $opt['id'] = (int) $opt['id'];
                $opt['text'] = $opt['option_text'];
            }
            $q['options'] = $options;
        }
        
        $quiz['questions'] = $questions;
        return $quiz;
    }

    public function getQuizWithAnswers(int $quizId): ?array
    {
        $query = $this->db->prepare('SELECT id, module_id, passing_score FROM quizzes WHERE id = :id');
        $query->execute(['id' => $quizId]);
        $quiz = $query->fetch();
        if (!$quiz) return null;

        $quiz['id'] = (int) $quiz['id'];
        $quiz['quiz_id'] = $quiz['id'];
        $quiz['module_id'] = (int) $quiz['module_id'];
        $quiz['passing_score'] = (int) $quiz['passing_score'];

        $qQuery = $this->db->prepare('SELECT id, score_weight FROM quiz_questions WHERE quiz_id = :quiz_id');
        $qQuery->execute(['quiz_id' => $quizId]);
        $questions = $qQuery->fetchAll();
        
        $questionsById = [];
        foreach ($questions as $q) {
            $qId = (int) $q['id'];
            $questionsById[$qId] = [
                'id' => $qId,
                'score_weight' => (int) $q['score_weight'],
                'valid_option_ids' => [],
                'correct_option_ids' => []
            ];
            
            $oQuery = $this->db->prepare('SELECT id, is_correct FROM quiz_options WHERE question_id = :question_id');
            $oQuery->execute(['question_id' => $qId]);
            $allOptions = $oQuery->fetchAll();
            foreach ($allOptions as $co) {
                $optId = (int) $co['id'];
                $questionsById[$qId]['valid_option_ids'][] = $optId;
                if ((int) $co['is_correct'] === 1) {
                    $questionsById[$qId]['correct_option_ids'][] = $optId;
                }
            }
        }
        
        $quiz['questions'] = $questionsById;
        return $quiz;
    }

    public function getNextModule(int $moduleId): ?array
    {
        $curQuery = $this->db->prepare('SELECT course_id, level_id FROM modules WHERE id = :id');
        $curQuery->execute(['id' => $moduleId]);
        $cur = $curQuery->fetch();
        if (!$cur) return null;

        $nextQuery = $this->db->prepare(
            'SELECT id, module_code, title FROM modules 
             WHERE prerequisite_module_id = :module_id AND course_id = :course_id AND level_id = :level_id AND status = \'published\'
             LIMIT 1'
        );
        $nextQuery->execute([
            'module_id' => $moduleId,
            'course_id' => $cur['course_id'],
            'level_id' => $cur['level_id'],
        ]);
        $next = $nextQuery->fetch();
        if ($next) {
            $next['id'] = (int) $next['id'];
            return $next;
        }
        return null;
    }

    public function saveQuizResultTransaction(int $userId, int $quizId, int $moduleId, int $score, bool $isPassed, int $durationSeconds): array
    {
        $this->db->beginTransaction();
        try {
            // Save attempt
            $attemptQuery = $this->db->prepare(
                'INSERT INTO user_quiz_attempts (user_id, quiz_id, score, is_passed, attempt_duration_seconds)
                 VALUES (:user_id, :quiz_id, :score, :is_passed, :duration)'
            );
            $attemptQuery->execute([
                'user_id' => $userId,
                'quiz_id' => $quizId,
                'score' => $score,
                'is_passed' => $isPassed ? 1 : 0,
                'duration' => $durationSeconds
            ]);

            // Update module progress if passed or if we need to track highest score
            $progQuery = $this->db->prepare('SELECT id, status, highest_quiz_score FROM user_module_progress WHERE user_id = :user_id AND module_id = :module_id');
            $progQuery->execute(['user_id' => $userId, 'module_id' => $moduleId]);
            $progress = $progQuery->fetch();

            if ($progress) {
                $newHighest = max((int) $progress['highest_quiz_score'], $score);
                $newStatus = $progress['status'];
                if ($isPassed && $newStatus !== 'completed') {
                    $newStatus = 'completed';
                }
                
                $upQuery = $this->db->prepare(
                    'UPDATE user_module_progress SET status = :status, highest_quiz_score = :score, completed_at = CASE WHEN :is_completed = 1 AND completed_at IS NULL THEN CURRENT_TIMESTAMP ELSE completed_at END WHERE id = :id'
                );
                $upQuery->execute([
                    'status' => $newStatus,
                    'score' => $newHighest,
                    'is_completed' => ($newStatus === 'completed' ? 1 : 0),
                    'id' => $progress['id']
                ]);
            } else {
                $status = $isPassed ? 'completed' : 'in_progress';
                $completedAt = $isPassed ? date('Y-m-d H:i:s') : null;
                $inQuery = $this->db->prepare(
                    'INSERT INTO user_module_progress (user_id, module_id, status, highest_quiz_score, completed_at) VALUES (:user_id, :module_id, :status, :score, :completed_at)'
                );
                $inQuery->execute([
                    'user_id' => $userId,
                    'module_id' => $moduleId,
                    'status' => $status,
                    'score' => $score,
                    'completed_at' => $completedAt
                ]);
            }

            // Unlock next module if passed
            $nextModuleUnlocked = false;
            $unlockedModuleId = null;

            if ($isPassed) {
                $nextMod = $this->getNextModule($moduleId);
                if ($nextMod) {
                    $nextModuleUnlocked = true;
                    $unlockedModuleId = (int) $nextMod['id'];

                    $checkNext = $this->db->prepare('SELECT id, status FROM user_module_progress WHERE user_id = :user_id AND module_id = :module_id');
                    $checkNext->execute(['user_id' => $userId, 'module_id' => $unlockedModuleId]);
                    $nextRow = $checkNext->fetch();
                    if (!$nextRow) {
                        $insNext = $this->db->prepare("INSERT INTO user_module_progress (user_id, module_id, status) VALUES (:user_id, :module_id, 'unlocked')");
                        $insNext->execute(['user_id' => $userId, 'module_id' => $unlockedModuleId]);
                    } elseif ($nextRow['status'] === 'locked') {
                        $upNext = $this->db->prepare("UPDATE user_module_progress SET status = 'unlocked' WHERE id = :id");
                        $upNext->execute(['id' => $nextRow['id']]);
                    }
                }
            }

            $this->db->commit();
            return [
                'next_module_unlocked' => $nextModuleUnlocked,
                'unlocked_module_id' => $unlockedModuleId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
