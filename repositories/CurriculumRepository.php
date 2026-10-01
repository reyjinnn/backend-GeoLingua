<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class CurriculumRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    //Get active course & level for a user
    public function activeCourseAndLevel(int $userId): ?array
    {
        $query = $this->db->prepare(
            'SELECT course_id, current_level_id
             FROM user_learning_languages
             WHERE user_id = :user_id AND is_primary = 1
             ORDER BY id DESC LIMIT 1'
        );
        $query->execute(['user_id' => $userId]);
        $result = $query->fetch();
        return $result === false ? null : $result;
    }

    //List all published modules for a course & level
    public function modulesForCourseLevel(int $courseId, int $levelId): array
    {
        $query = $this->db->prepare(
            "SELECT id, module_code, title, topic, description,
                    learning_objectives, estimated_duration_minutes,
                    order_index, prerequisite_module_id
             FROM modules
             WHERE course_id = :course_id
               AND level_id = :level_id
               AND status = 'published'
             ORDER BY order_index"
        );
        $query->execute(['course_id' => $courseId, 'level_id' => $levelId]);
        return $query->fetchAll();
    }

    //Get user's progress for all modules in a course
    public function progressForUser(int $userId): array
    {
        $query = $this->db->prepare(
            'SELECT module_id, status, highest_quiz_score, completed_at
             FROM user_module_progress
             WHERE user_id = :user_id'
        );
        $query->execute(['user_id' => $userId]);
        $rows = $query->fetchAll();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['module_id']] = $row;
        }
        return $indexed;
    }

    
    //Get module detail with lessons
    public function moduleDetail(int $moduleId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, course_id, level_id, module_code, title, topic,
                    description, learning_objectives, estimated_duration_minutes,
                    order_index, prerequisite_module_id
             FROM modules
             WHERE id = :id AND status = \'published\'
             LIMIT 1'
        );
        $query->execute(['id' => $moduleId]);
        $module = $query->fetch();
        return $module === false ? null : $module;
    }

    //Get lessons for a module with vocabulary coun
    public function lessonsForModule(int $moduleId): array
    {
        $query = $this->db->prepare(
            'SELECT l.id, l.lesson_name, l.order_index,
                    COUNT(lv.vocabulary_id) AS vocabulary_count
             FROM lessons l
             LEFT JOIN lesson_vocabularies lv ON lv.lesson_id = l.id
             WHERE l.module_id = :module_id
             GROUP BY l.id
             ORDER BY l.order_index'
        );
        $query->execute(['module_id' => $moduleId]);
        return $query->fetchAll();
    }

    //Get quiz for a module
    public function quizForModule(int $moduleId): ?array
    {
        $query = $this->db->prepare(
            'SELECT id, title, passing_score
             FROM quizzes
             WHERE module_id = :module_id
             LIMIT 1'
        );
        $query->execute(['module_id' => $moduleId]);
        $quiz = $query->fetch();
        return $quiz === false ? null : $quiz;
    }

    //Check if a user has access to a module (not locked)
    public function isModuleAccessible(int $userId, int $moduleId): bool
    {
        // Module 1 (order_index = 1) always accessible
        // Otherwise, check if prerequisite module is completed

        $query = $this->db->prepare(
            'SELECT order_index, prerequisite_module_id
             FROM modules WHERE id = :id LIMIT 1'
        );
        $query->execute(['id' => $moduleId]);
        $module = $query->fetch();
        if ($module === false) {
            return false;
        }

        if ((int) $module['order_index'] === 1 || $module['prerequisite_module_id'] === null) {
            return true;
        }

        $progressQuery = $this->db->prepare(
            'SELECT status FROM user_module_progress
             WHERE user_id = :user_id AND module_id = :module_id LIMIT 1'
        );
        $progressQuery->execute([
            'user_id' => $userId,
            'module_id' => $module['prerequisite_module_id'],
        ]);
        $status = $progressQuery->fetchColumn();
        return $status === 'completed';
    }
}