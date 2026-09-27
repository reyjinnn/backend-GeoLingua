<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class OnboardingRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    public function languages(): array
    {
        return $this->db->query(
            'SELECT id, code, name, native_name FROM languages WHERE is_active = 1 ORDER BY id'
        )->fetchAll();
    }

    public function levels(): array
    {
        return $this->db->query(
            'SELECT id, code, name, order_index FROM levels ORDER BY order_index'
        )->fetchAll();
    }

    public function courses(): array
    {
        return $this->db->query(
            'SELECT c.id, c.title, base.code AS base_language_code, target.code AS target_language_code
             FROM courses c
             JOIN languages base ON base.id = c.base_language_id
             JOIN languages target ON target.id = c.target_language_id
             WHERE c.is_active = 1 AND base.is_active = 1 AND target.is_active = 1 ORDER BY c.id'
        )->fetchAll();
    }

    public function courseId(string $baseCode, string $targetCode): ?int
    {
        $query = $this->db->prepare(
            'SELECT c.id FROM courses c
             JOIN languages base ON base.id = c.base_language_id
             JOIN languages target ON target.id = c.target_language_id
             WHERE base.code = :base AND target.code = :target
               AND c.is_active = 1 AND base.is_active = 1 AND target.is_active = 1 LIMIT 1'
        );
        $query->execute(['base' => $baseCode, 'target' => $targetCode]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function levelId(string $code): ?int
    {
        $query = $this->db->prepare('SELECT id FROM levels WHERE code = :code LIMIT 1');
        $query->execute(['code' => $code]);
        $id = $query->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function savePreferences(int $userId, int $courseId, int $levelId): void
    {
        $this->db->beginTransaction();
        try {
            $clear = $this->db->prepare('UPDATE user_learning_languages SET is_primary = 0 WHERE user_id = :user_id');
            $clear->execute(['user_id' => $userId]);

            $find = $this->db->prepare('SELECT id FROM user_learning_languages WHERE user_id = :user_id AND course_id = :course_id LIMIT 1');
            $find->execute(['user_id' => $userId, 'course_id' => $courseId]);
            $existingId = $find->fetchColumn();
            if ($existingId === false) {
                $insert = $this->db->prepare(
                    'INSERT INTO user_learning_languages (user_id, course_id, current_level_id, is_primary)
                     VALUES (:user_id, :course_id, :level_id, 1)'
                );
                $insert->execute(['user_id' => $userId, 'course_id' => $courseId, 'level_id' => $levelId]);
            } else {
                $update = $this->db->prepare(
                    'UPDATE user_learning_languages SET current_level_id = :level_id, is_primary = 1 WHERE id = :id'
                );
                $update->execute(['level_id' => $levelId, 'id' => $existingId]);
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
