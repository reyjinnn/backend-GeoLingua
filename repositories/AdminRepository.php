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

    
    // DASHBOARD METRICS
    

    public function dashboardMetrics(): array
    {
        $users = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $published = (int) $this->db->query("SELECT COUNT(*) FROM modules WHERE status = 'published'")->fetchColumn();
        $drafts = (int) $this->db->query("SELECT COUNT(*) FROM modules WHERE status = 'draft'")->fetchColumn();
        $archived = (int) $this->db->query("SELECT COUNT(*) FROM modules WHERE status = 'archived'")->fetchColumn();

        return [
            'total_users' => $users,
            'published_modules' => $published,
            'draft_modules' => $drafts,
            'archived_modules' => $archived,
        ];
    }

    
    // MODULES
    

    public function allModules(): array
    {
        $query = $this->db->query(
            "SELECT m.id, m.module_code, m.title, m.topic, m.order_index, m.status,
                    m.course_id, m.level_id, m.prerequisite_module_id,
                    c.title AS course_title, l.code AS level_code
             FROM modules m
             JOIN courses c ON c.id = m.course_id
             JOIN levels l ON l.id = m.level_id
             ORDER BY m.course_id, m.level_id, m.order_index"
        );
        return array_map(function ($row) {
            $row['id'] = (int) $row['id'];
            $row['order_index'] = (int) $row['order_index'];
            $row['course_id'] = (int) $row['course_id'];
            $row['level_id'] = (int) $row['level_id'];
            $row['prerequisite_module_id'] = $row['prerequisite_module_id'] !== null
                ? (int) $row['prerequisite_module_id']
                : null;
            return $row;
        }, $query->fetchAll());
    }

    public function findModuleById(int $id): ?array
    {
        $query = $this->db->prepare(
            "SELECT m.id, m.course_id, m.level_id, m.module_code, m.title, m.topic,
                    m.description, m.learning_objectives, m.estimated_duration_minutes,
                    m.order_index, m.prerequisite_module_id, m.status
             FROM modules m
             WHERE m.id = :id
             LIMIT 1"
        );
        $query->execute(['id' => $id]);
        $module = $query->fetch();
        if ($module === false) return null;

        $module['id'] = (int) $module['id'];
        $module['course_id'] = (int) $module['course_id'];
        $module['level_id'] = (int) $module['level_id'];
        $module['order_index'] = (int) $module['order_index'];
        $module['estimated_duration_minutes'] = (int) $module['estimated_duration_minutes'];
        $module['prerequisite_module_id'] = $module['prerequisite_module_id'] !== null
            ? (int) $module['prerequisite_module_id']
            : null;
        return $module;
    }

    public function createModule(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO modules
                (course_id, level_id, module_code, title, topic, description,
                 learning_objectives, estimated_duration_minutes, order_index,
                 prerequisite_module_id, status)
             VALUES (:course_id, :level_id, :module_code, :title, :topic, :description,
                     :learning_objectives, :estimated_duration_minutes, :order_index,
                     :prerequisite_module_id, :status)'
        );
        $stmt->execute([
            'course_id' => $data['course_id'],
            'level_id' => $data['level_id'],
            'module_code' => $data['module_code'],
            'title' => $data['title'],
            'topic' => $data['topic'],
            'description' => $data['description'],
            'learning_objectives' => $data['learning_objectives'],
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? 30,
            'order_index' => $data['order_index'],
            'prerequisite_module_id' => $data['prerequisite_module_id'] ?? null,
            'status' => 'draft',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateModule(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        $allowed = [
            'title', 'topic', 'description', 'learning_objectives',
            'estimated_duration_minutes', 'order_index', 'prerequisite_module_id',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if ($fields === []) return;

        $sql = 'UPDATE modules SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function setModuleStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE modules SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function deleteModule(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM modules WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function moduleCodeExists(string $code, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM modules WHERE module_code = :code AND id != :id');
            $stmt->execute(['code' => $code, 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM modules WHERE module_code = :code');
            $stmt->execute(['code' => $code]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function courseExists(int $courseId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM courses WHERE id = :id');
        $stmt->execute(['id' => $courseId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function levelExists(int $levelId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM levels WHERE id = :id');
        $stmt->execute(['id' => $levelId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function moduleExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM modules WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}