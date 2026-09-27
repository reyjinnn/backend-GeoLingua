<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class AdminRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function fromConfig(): self
    {
        return new self(databaseConnection());
    }

    public function initAdmin(string $email, string $passwordHash, string $name): bool
    {
        $this->db->beginTransaction();
        try {
            // Check if any admin exists
            $query = $this->db->prepare('SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = "admin"');
            $query->execute();
            $adminCount = (int) $query->fetchColumn();

            if ($adminCount > 0) {
                $this->db->rollBack();
                return false; // Admin already exists
            }

            // Get admin role ID
            $roleQuery = $this->db->prepare('SELECT id FROM roles WHERE name = "admin"');
            $roleQuery->execute();
            $roleId = $roleQuery->fetchColumn();

            if (!$roleId) {
                // Should not happen if db.sql is correct
                $this->db->exec('INSERT INTO roles (name, description) VALUES ("admin", "Administrator")');
                $roleId = $this->db->lastInsertId();
            }

            // Insert user
            $insert = $this->db->prepare('INSERT INTO users (role_id, email, password_hash, full_name) VALUES (:role_id, :email, :hash, :name)');
            $insert->execute([
                'role_id' => (int) $roleId,
                'email' => $email,
                'hash' => $passwordHash,
                'name' => $name
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createModule(array $data): int
    {
        $query = $this->db->prepare(
            'INSERT INTO modules (course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, prerequisite_module_id, status)
             VALUES (:course_id, :level_id, :code, :title, :topic, :desc, :obj, :dur, :order, :prereq, :status)'
        );
        $query->execute([
            'course_id' => $data['course_id'],
            'level_id' => $data['level_id'],
            'code' => $data['module_code'],
            'title' => $data['title'],
            'topic' => $data['topic'] ?? '',
            'desc' => $data['description'] ?? '',
            'obj' => $data['learning_objectives'] ?? '',
            'dur' => $data['estimated_duration_minutes'] ?? 0,
            'order' => $data['order_index'] ?? 1,
            'prereq' => $data['prerequisite_module_id'] ?: null,
            'status' => $data['status'] ?? 'draft'
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function createLesson(array $data): int
    {
        $query = $this->db->prepare(
            'INSERT INTO lessons (module_id, lesson_name, lesson_objective, grammar_notes, order_index)
             VALUES (:module_id, :name, :obj, :grammar, :order)'
        );
        $query->execute([
            'module_id' => $data['module_id'],
            'name' => $data['lesson_name'],
            'obj' => $data['lesson_objective'] ?? '',
            'grammar' => $data['grammar_notes'] ?? '',
            'order' => $data['order_index'] ?? 1
        ]);
        return (int) $this->db->lastInsertId();
    }
    
    public function updateModuleStatus(int $moduleId, string $status): void
    {
        $query = $this->db->prepare('UPDATE modules SET status = :status WHERE id = :id');
        $query->execute(['status' => $status, 'id' => $moduleId]);
    }

    public function getModuleDetailsForPublishing(int $moduleId): array
    {
        // Check if module has:
        // 1. At least 1 lesson
        // 2. The lesson has vocabularies
        // 3. The lesson has drills
        // 4. The lesson has writing
        // 5. The module has a quiz with exactly 10 questions
        
        $result = [
            'has_lesson' => false,
            'has_vocabulary' => false,
            'has_drill' => false,
            'has_writing' => false,
            'has_quiz' => false,
            'quiz_question_count' => 0
        ];

        $lessonQuery = $this->db->prepare('SELECT id FROM lessons WHERE module_id = :module_id LIMIT 1');
        $lessonQuery->execute(['module_id' => $moduleId]);
        $lessonId = $lessonQuery->fetchColumn();

        if ($lessonId) {
            $result['has_lesson'] = true;

            $vocQuery = $this->db->prepare('SELECT COUNT(*) FROM lesson_vocabularies WHERE lesson_id = :lesson_id');
            $vocQuery->execute(['lesson_id' => $lessonId]);
            $result['has_vocabulary'] = ((int) $vocQuery->fetchColumn()) > 0;

            $drillQuery = $this->db->prepare('SELECT COUNT(*) FROM exercises WHERE lesson_id = :lesson_id');
            $drillQuery->execute(['lesson_id' => $lessonId]);
            $result['has_drill'] = ((int) $drillQuery->fetchColumn()) > 0;

            $writeQuery = $this->db->prepare('SELECT COUNT(*) FROM writing_exercises WHERE lesson_id = :lesson_id');
            $writeQuery->execute(['lesson_id' => $lessonId]);
            $result['has_writing'] = ((int) $writeQuery->fetchColumn()) > 0;
        }

        $quizQuery = $this->db->prepare('SELECT id FROM quizzes WHERE module_id = :module_id LIMIT 1');
        $quizQuery->execute(['module_id' => $moduleId]);
        $quizId = $quizQuery->fetchColumn();

        if ($quizId) {
            $result['has_quiz'] = true;
            $qqQuery = $this->db->prepare('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = :quiz_id');
            $qqQuery->execute(['quiz_id' => $quizId]);
            $result['quiz_question_count'] = (int) $qqQuery->fetchColumn();
        }

        return $result;
    }
}
