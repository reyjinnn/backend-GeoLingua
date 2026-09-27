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

    public function getModulesForCourseAndLevel(int $courseId, int $levelId, int $userId): array
    {
        $query = $this->db->prepare(
            'SELECT m.id, m.module_code, m.title, m.topic, m.description, m.learning_objectives,
                    m.estimated_duration_minutes, m.order_index, m.prerequisite_module_id,
                    ump.status as progress_status
             FROM modules m
             LEFT JOIN user_module_progress ump ON ump.module_id = m.id AND ump.user_id = :user_id
             WHERE m.course_id = :course_id AND m.level_id = :level_id AND m.status = \'published\'
             ORDER BY m.order_index ASC'
        );
        $query->execute([
            'course_id' => $courseId,
            'level_id' => $levelId,
            'user_id' => $userId,
        ]);
        
        $modules = $query->fetchAll();
        
        // Compute progress_status for modules that don't have a record yet
        $completedModules = array_filter($modules, fn($m) => $m['progress_status'] === 'completed');
        $completedModuleIds = array_map(fn($m) => (int)$m['id'], $completedModules);
        
        foreach ($modules as &$m) {
            $m['id'] = (int) $m['id'];
            $m['estimated_duration_minutes'] = (int) $m['estimated_duration_minutes'];
            $m['order_index'] = (int) $m['order_index'];
            $m['prerequisite_module_id'] = $m['prerequisite_module_id'] ? (int) $m['prerequisite_module_id'] : null;
            
            if ($m['progress_status'] === null) {
                if ($m['prerequisite_module_id'] === null) {
                    $m['progress_status'] = 'unlocked';
                } else {
                    $m['progress_status'] = in_array($m['prerequisite_module_id'], $completedModuleIds, true) 
                        ? 'unlocked' 
                        : 'locked';
                }
            }

            // Expose fields required by frontend Dashboard & ModuleSummary
            $m['status'] = $m['progress_status'];
            $m['is_completed'] = ($m['status'] === 'completed');
            $m['progress_percentage'] = $m['is_completed'] ? 100 : ($m['status'] === 'in_progress' ? 50 : 0);
        }
        
        return $modules;
    }

    public function getModuleById(int $moduleId, int $userId, int $courseId, int $levelId): ?array
    {
        $query = $this->db->prepare(
            'SELECT m.id, m.module_code, m.title, m.topic, m.description, m.learning_objectives,
                    m.estimated_duration_minutes, m.order_index, m.prerequisite_module_id,
                    m.status as module_status, m.status,
                    ump.status as progress_status
             FROM modules m
             LEFT JOIN user_module_progress ump ON ump.module_id = m.id AND ump.user_id = :user_id
             WHERE m.id = :module_id AND m.course_id = :course_id AND m.level_id = :level_id AND m.status = \'published\''
        );
        $query->execute([
            'module_id' => $moduleId,
            'user_id' => $userId,
            'course_id' => $courseId,
            'level_id' => $levelId,
        ]);
        
        $module = $query->fetch();
        if (!$module) return null;
        
        $module['id'] = (int) $module['id'];
        $module['estimated_duration_minutes'] = (int) $module['estimated_duration_minutes'];
        $module['order_index'] = (int) $module['order_index'];
        $module['prerequisite_module_id'] = $module['prerequisite_module_id'] ? (int) $module['prerequisite_module_id'] : null;
        
        if ($module['progress_status'] === null) {
            if ($module['prerequisite_module_id'] === null) {
                $module['progress_status'] = 'unlocked';
            } else {
                // Check if prereq is completed
                $prereqQuery = $this->db->prepare('SELECT status FROM user_module_progress WHERE user_id = :user_id AND module_id = :prereq_id');
                $prereqQuery->execute(['user_id' => $userId, 'prereq_id' => $module['prerequisite_module_id']]);
                $prereqStatus = $prereqQuery->fetchColumn();
                $module['progress_status'] = ($prereqStatus === 'completed') ? 'unlocked' : 'locked';
            }
        }

        $module['status'] = $module['progress_status'];
        
        // Get lessons for this module with vocabulary count
        $lessonsQuery = $this->db->prepare(
            'SELECT l.id, l.lesson_name, l.lesson_objective, l.order_index,
                    COUNT(lv.vocabulary_id) as vocabulary_count
             FROM lessons l
             LEFT JOIN lesson_vocabularies lv ON lv.lesson_id = l.id
             WHERE l.module_id = :module_id
             GROUP BY l.id, l.lesson_name, l.lesson_objective, l.order_index
             ORDER BY l.order_index ASC'
        );
        $lessonsQuery->execute(['module_id' => $moduleId]);
        $lessons = $lessonsQuery->fetchAll();
        foreach ($lessons as &$l) {
            $l['id'] = (int) $l['id'];
            $l['order_index'] = (int) $l['order_index'];
            $l['vocabulary_count'] = (int) ($l['vocabulary_count'] ?? 0);
        }
        $module['lessons'] = $lessons;
        
        // Fetch quiz for this module
        $quizQuery = $this->db->prepare('SELECT id, title, passing_score FROM quizzes WHERE module_id = :module_id LIMIT 1');
        $quizQuery->execute(['module_id' => $moduleId]);
        $quizRow = $quizQuery->fetch();
        if ($quizRow) {
            $module['quiz'] = [
                'id' => (int) $quizRow['id'],
                'title' => (string) $quizRow['title'],
                'passing_score' => (int) $quizRow['passing_score']
            ];
        } else {
            $module['quiz'] = [
                'id' => 0,
                'title' => 'Kuis Modul',
                'passing_score' => 70
            ];
        }

        return $module;
    }

    public function getLessonById(int $lessonId, int $userId): ?array
    {
        $query = $this->db->prepare(
            'SELECT l.id, l.module_id, l.lesson_name, l.lesson_objective, l.grammar_notes, l.order_index,
                    m.course_id, m.level_id, m.status as module_status, m.prerequisite_module_id,
                    ump.status as progress_status
             FROM lessons l
             JOIN modules m ON m.id = l.module_id
             LEFT JOIN user_module_progress ump ON ump.module_id = m.id AND ump.user_id = :user_id
             WHERE l.id = :lesson_id'
        );
        $query->execute(['lesson_id' => $lessonId, 'user_id' => $userId]);
        $lesson = $query->fetch();
        if (!$lesson) return null;
        
        $lesson['id'] = (int) $lesson['id'];
        $lesson['module_id'] = (int) $lesson['module_id'];
        $lesson['order_index'] = (int) $lesson['order_index'];
        $lesson['course_id'] = (int) $lesson['course_id'];
        $lesson['level_id'] = (int) $lesson['level_id'];
        $lesson['prerequisite_module_id'] = $lesson['prerequisite_module_id'] ? (int) $lesson['prerequisite_module_id'] : null;
        
        // Calculate progress status
        if ($lesson['progress_status'] === null) {
            if ($lesson['prerequisite_module_id'] === null) {
                $lesson['progress_status'] = 'unlocked';
            } else {
                $prereqQuery = $this->db->prepare('SELECT status FROM user_module_progress WHERE user_id = :user_id AND module_id = :prereq_id');
                $prereqQuery->execute(['user_id' => $userId, 'prereq_id' => $lesson['prerequisite_module_id']]);
                $prereqStatus = $prereqQuery->fetchColumn();
                $lesson['progress_status'] = ($prereqStatus === 'completed') ? 'unlocked' : 'locked';
            }
        }
        
        return $lesson;
    }

    public function getVocabularyForLesson(int $lessonId, int $baseLanguageId): array
    {
        $query = $this->db->prepare(
            'SELECT v.id, v.word, v.pronunciation, v.part_of_speech, v.difficulty,
                    vt.translation, vt.definition, vt.example_sentence, vt.example_translation, vt.notes
             FROM vocabularies v
             JOIN lesson_vocabularies lv ON lv.vocabulary_id = v.id
             JOIN vocabulary_translations vt ON vt.vocabulary_id = v.id
             WHERE lv.lesson_id = :lesson_id AND vt.base_language_id = :base_language_id
             ORDER BY lv.order_index ASC'
        );
        $query->execute([
            'lesson_id' => $lessonId,
            'base_language_id' => $baseLanguageId
        ]);
        
        $vocabularies = $query->fetchAll();
        foreach ($vocabularies as &$v) {
            $v['id'] = (int) $v['id'];
        }
        return $vocabularies;
    }
}
