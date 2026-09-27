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

    public function hasAnyAdmin(): bool
    {
        $query = $this->db->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'admin'");
        $query->execute();
        return ((int) $query->fetchColumn()) > 0;
    }

    public function initAdmin(string $email, string $passwordHash, string $name): bool
    {
        $this->db->beginTransaction();
        try {
            if ($this->hasAnyAdmin()) {
                $this->db->rollBack();
                return false; // Admin already exists
            }

            // Get admin role ID
            $roleQuery = $this->db->prepare("SELECT id FROM roles WHERE name = 'admin'");
            $roleQuery->execute();
            $roleId = $roleQuery->fetchColumn();

            if (!$roleId) {
                $this->db->exec("INSERT INTO roles (name, description) VALUES ('admin', 'Administrator')");
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
        // Newly created modules always start as 'draft' to enforce publish validation
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
            'status' => 'draft'
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
        $result = [
            'has_lesson' => false,
            'lesson_count' => 0,
            'lessons_without_vocabulary' => 0,
            'lessons_without_drill' => 0,
            'lessons_without_writing' => 0,
            'has_vocabulary' => false,
            'has_drill' => false,
            'has_writing' => false,
            'has_quiz' => false,
            'quiz_question_count' => 0,
            'invalid_quiz_questions' => 0
        ];

        $lessonsQuery = $this->db->prepare('SELECT id FROM lessons WHERE module_id = :module_id ORDER BY order_index ASC');
        $lessonsQuery->execute(['module_id' => $moduleId]);
        $lessons = $lessonsQuery->fetchAll();

        $result['lesson_count'] = count($lessons);
        if (count($lessons) > 0) {
            $result['has_lesson'] = true;
            $allVocab = true;
            $allDrill = true;
            $allWriting = true;

            foreach ($lessons as $lesson) {
                $lId = (int) $lesson['id'];

                $vocQuery = $this->db->prepare('SELECT COUNT(*) FROM lesson_vocabularies WHERE lesson_id = :lesson_id');
                $vocQuery->execute(['lesson_id' => $lId]);
                if (((int) $vocQuery->fetchColumn()) === 0) {
                    $allVocab = false;
                    $result['lessons_without_vocabulary']++;
                }

                $drillQuery = $this->db->prepare('SELECT COUNT(*) FROM exercises WHERE lesson_id = :lesson_id');
                $drillQuery->execute(['lesson_id' => $lId]);
                if (((int) $drillQuery->fetchColumn()) === 0) {
                    $allDrill = false;
                    $result['lessons_without_drill']++;
                }

                $writeQuery = $this->db->prepare('SELECT COUNT(*) FROM writing_exercises WHERE lesson_id = :lesson_id');
                $writeQuery->execute(['lesson_id' => $lId]);
                if (((int) $writeQuery->fetchColumn()) === 0) {
                    $allWriting = false;
                    $result['lessons_without_writing']++;
                }
            }

            $result['has_vocabulary'] = $allVocab;
            $result['has_drill'] = $allDrill;
            $result['has_writing'] = $allWriting;
        }

        $quizQuery = $this->db->prepare('SELECT id FROM quizzes WHERE module_id = :module_id LIMIT 1');
        $quizQuery->execute(['module_id' => $moduleId]);
        $quizId = $quizQuery->fetchColumn();

        if ($quizId) {
            $result['has_quiz'] = true;
            $qqQuery = $this->db->prepare('SELECT id FROM quiz_questions WHERE quiz_id = :quiz_id');
            $qqQuery->execute(['quiz_id' => $quizId]);
            $questions = $qqQuery->fetchAll();
            $result['quiz_question_count'] = count($questions);

            // Validate that every question has at least 2 options and 1 correct answer
            $invalidQuestions = 0;
            foreach ($questions as $q) {
                $optQuery = $this->db->prepare('SELECT COUNT(*) as total_opt, SUM(is_correct) as correct_opt FROM quiz_options WHERE question_id = :question_id');
                $optQuery->execute(['question_id' => $q['id']]);
                $optStats = $optQuery->fetch();
                $totalOpt = (int) ($optStats['total_opt'] ?? 0);
                $correctOpt = (int) ($optStats['correct_opt'] ?? 0);
                if ($totalOpt < 2 || $correctOpt < 1) {
                    $invalidQuestions++;
                }
            }
            $result['invalid_quiz_questions'] = $invalidQuestions;
        }

        return $result;
    }

    public function listModules(): array
    {
        $query = $this->db->query(
            "SELECT m.id, m.course_id, m.level_id, m.module_code, m.title, m.topic, m.order_index, m.status,
                    COUNT(l.id) AS lesson_count
             FROM modules m
             LEFT JOIN lessons l ON l.module_id = m.id
             GROUP BY m.id
             ORDER BY m.order_index ASC"
        );
        return $query->fetchAll();
    }

    public function getLessonsForModule(int $moduleId): array
    {
        $query = $this->db->prepare('SELECT id, module_id, lesson_name, lesson_objective, grammar_notes, order_index FROM lessons WHERE module_id = :module_id ORDER BY order_index ASC');
        $query->execute(['module_id' => $moduleId]);
        return $query->fetchAll();
    }

    public function createVocabulary(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $vocabStmt = $this->db->prepare(
                'INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
                 VALUES (:target_lang_id, :word, :pronunciation, :pos, :difficulty)'
            );
            $vocabStmt->execute([
                'target_lang_id' => $data['target_language_id'] ?? 2,
                'word' => $data['word'],
                'pronunciation' => $data['pronunciation'] ?? '',
                'part_of_speech' => $data['part_of_speech'] ?? 'noun',
                'difficulty' => $data['difficulty'] ?? 'easy'
            ]);
            $vocabId = (int) $this->db->lastInsertId();

            $transStmt = $this->db->prepare(
                'INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
                 VALUES (:vocab_id, :base_lang_id, :trans, :def, :ex, :ex_trans, :notes)'
            );
            $transStmt->execute([
                'vocab_id' => $vocabId,
                'base_lang_id' => $data['base_language_id'] ?? 1,
                'trans' => $data['translation'],
                'def' => $data['definition'] ?? '',
                'ex' => $data['example_sentence'] ?? '',
                'ex_trans' => $data['example_translation'] ?? '',
                'notes' => $data['notes'] ?? null
            ]);

            if (!empty($data['lesson_id'])) {
                $linkStmt = $this->db->prepare(
                    'INSERT IGNORE INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index) VALUES (:lesson_id, :vocab_id, :order_idx)'
                );
                $linkStmt->execute([
                    'lesson_id' => $data['lesson_id'],
                    'vocab_id' => $vocabId,
                    'order_idx' => $data['order_index'] ?? 1
                ]);
            }

            $this->db->commit();
            return $vocabId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createExercise(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
                 VALUES (:lesson_id, :vocab_id, :type, :prompt, :correct, :exp, :order_idx)'
            );
            $stmt->execute([
                'lesson_id' => $data['lesson_id'],
                'vocab_id' => $data['vocabulary_id'],
                'type' => $data['question_type'],
                'prompt' => $data['prompt'],
                'correct' => $data['correct_answer'],
                'exp' => $data['explanation'] ?? '',
                'order_idx' => $data['order_index'] ?? 1
            ]);
            $exerciseId = (int) $this->db->lastInsertId();

            if (!empty($data['options']) && is_array($data['options'])) {
                $optStmt = $this->db->prepare(
                    'INSERT INTO exercise_options (exercise_id, option_text, is_correct) VALUES (:ex_id, :text, :is_correct)'
                );
                foreach ($data['options'] as $opt) {
                    $optStmt->execute([
                        'ex_id' => $exerciseId,
                        'text' => $opt['option_text'] ?? $opt['text'],
                        'is_correct' => !empty($opt['is_correct']) ? 1 : 0
                    ]);
                }
            }

            $this->db->commit();
            return $exerciseId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
