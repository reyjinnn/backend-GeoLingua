<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class ModuleProgressService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    /**
     * Mark module as completed & unlock next module.
     * Returns unlocked module ID (atau null).
     */
    public function completeModuleAndUnlockNext(int $userId, int $moduleId, int $score): ?int
    {
        $this->db->beginTransaction();
        try {
            $this->upsertModuleProgress($userId, $moduleId, 'completed', $score);

            $nextModuleId = $this->findNextModule($moduleId);
            if ($nextModuleId === null) {
                $this->db->commit();
                return null;
            }

            $this->upsertModuleProgress($userId, $nextModuleId, 'unlocked', 0);

            $this->db->commit();
            return $nextModuleId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function upsertModuleProgress(int $userId, int $moduleId, string $status, int $score): void
    {
        $existing = $this->db->prepare(
            'SELECT id FROM user_module_progress WHERE user_id = :user_id AND module_id = :module_id LIMIT 1'
        );
        $existing->execute(['user_id' => $userId, 'module_id' => $moduleId]);
        $rowId = $existing->fetchColumn();

        $now = gmdate('Y-m-d H:i:s');

        if ($rowId === false) {
            $insert = $this->db->prepare(
                'INSERT INTO user_module_progress
                    (user_id, module_id, status, highest_quiz_score, completed_at)
                 VALUES (:user_id, :module_id, :status, :score, :completed_at)'
            );
            $insert->execute([
                'user_id' => $userId,
                'module_id' => $moduleId,
                'status' => $status,
                'score' => $score,
                'completed_at' => $status === 'completed' ? $now : null,
            ]);
            return;
        }

        $update = $this->db->prepare(
            "UPDATE user_module_progress
             SET status = :status,
                 highest_quiz_score = CASE WHEN :score > highest_quiz_score THEN :score ELSE highest_quiz_score END,
                 completed_at = CASE WHEN :status = 'completed' THEN :completed_at ELSE completed_at END
             WHERE id = :id"
        );
        $update->execute([
            'status' => $status,
            'score' => $score,
            'completed_at' => $now,
            'id' => $rowId,
        ]);
    }

    private function findNextModule(int $moduleId): ?int
    {
        $current = $this->db->prepare(
            'SELECT course_id, level_id, order_index FROM modules WHERE id = :id LIMIT 1'
        );
        $current->execute(['id' => $moduleId]);
        $mod = $current->fetch();
        if ($mod === false) return null;

        $next = $this->db->prepare(
            'SELECT id FROM modules
             WHERE course_id = :course_id AND level_id = :level_id AND order_index = :next_order
             LIMIT 1'
        );
        $next->execute([
            'course_id' => $mod['course_id'],
            'level_id' => $mod['level_id'],
            'next_order' => (int) $mod['order_index'] + 1,
        ]);
        $id = $next->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}