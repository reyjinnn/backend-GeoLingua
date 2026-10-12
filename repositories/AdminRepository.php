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

        
    // LESSONS
    

    public function lessonsForModule(int $moduleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, module_id, lesson_name, lesson_objective, grammar_notes, order_index
             FROM lessons
             WHERE module_id = :module_id
             ORDER BY order_index, id'
        );
        $stmt->execute(['module_id' => $moduleId]);
        return array_map(function ($row) {
            $row['id'] = (int) $row['id'];
            $row['module_id'] = (int) $row['module_id'];
            $row['order_index'] = (int) $row['order_index'];
            return $row;
        }, $stmt->fetchAll());
    }

    public function createLesson(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO lessons (module_id, lesson_name, lesson_objective, grammar_notes, order_index)
             VALUES (:module_id, :lesson_name, :lesson_objective, :grammar_notes, :order_index)'
        );
        $stmt->execute([
            'module_id' => $data['module_id'],
            'lesson_name' => $data['lesson_name'],
            'lesson_objective' => $data['lesson_objective'],
            'grammar_notes' => $data['grammar_notes'] ?? null,
            'order_index' => $data['order_index'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateLesson(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];
        foreach (['lesson_name', 'lesson_objective', 'grammar_notes', 'order_index'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        if ($fields === []) return;
        $stmt = $this->db->prepare('UPDATE lessons SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public function deleteLesson(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM lessons WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function lessonExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM lessons WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    
    // VOCABULARIES
    

    public function createVocabulary(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
                 VALUES (:target_language_id, :word, :pronunciation, :part_of_speech, :difficulty)'
            );
            $stmt->execute([
                'target_language_id' => $data['target_language_id'],
                'word' => $data['word'],
                'pronunciation' => $data['pronunciation'],
                'part_of_speech' => $data['part_of_speech'],
                'difficulty' => $data['difficulty'] ?? 'easy',
            ]);
            $vocabId = (int) $this->db->lastInsertId();

            $transStmt = $this->db->prepare(
                'INSERT INTO vocabulary_translations
                    (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
                 VALUES (:vocab_id, :base_lang, :translation, :definition, :example_sentence, :example_translation, :notes)'
            );
            $transStmt->execute([
                'vocab_id' => $vocabId,
                'base_lang' => $data['base_language_id'],
                'translation' => $data['translation'],
                'definition' => $data['definition'],
                'example_sentence' => $data['example_sentence'],
                'example_translation' => $data['example_translation'],
                'notes' => $data['notes'] ?? null,
            ]);

            $pivotStmt = $this->db->prepare(
                'INSERT INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index)
                 VALUES (:lesson_id, :vocab_id, :order_index)'
            );
            $pivotStmt->execute([
                'lesson_id' => $data['lesson_id'],
                'vocab_id' => $vocabId,
                'order_index' => $data['order_index'] ?? 1,
            ]);

            $this->db->commit();
            return $vocabId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function vocabularyExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM vocabularies WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function deleteVocabulary(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM vocabularies WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    
    // EXERCISES (DRILL)
    

    public function createExercise(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
                 VALUES (:lesson_id, :vocabulary_id, :question_type, :prompt, :correct_answer, :explanation, :order_index)'
            );
            $stmt->execute([
                'lesson_id' => $data['lesson_id'],
                'vocabulary_id' => $data['vocabulary_id'],
                'question_type' => $data['question_type'],
                'prompt' => $data['prompt'],
                'correct_answer' => $data['correct_answer'],
                'explanation' => $data['explanation'] ?? null,
                'order_index' => $data['order_index'] ?? 1,
            ]);
            $exerciseId = (int) $this->db->lastInsertId();

            if (!empty($data['options']) && is_array($data['options'])) {
                $optStmt = $this->db->prepare(
                    'INSERT INTO exercise_options (exercise_id, option_text, is_correct)
                     VALUES (:exercise_id, :option_text, :is_correct)'
                );
                foreach ($data['options'] as $opt) {
                    $optStmt->execute([
                        'exercise_id' => $exerciseId,
                        'option_text' => $opt['text'],
                        'is_correct' => !empty($opt['is_correct']) ? 1 : 0,
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

    public function deleteExercise(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM exercises WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function exerciseExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM exercises WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    
    // WRITING EXERCISES
    

    public function createWritingExercise(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO writing_exercises
                (lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide)
             VALUES (:lesson_id, :instruction, :writing_prompt, :required_vocabulary, :minimum_words, :benchmark_answer, :evaluation_guide)'
        );
        $stmt->execute([
            'lesson_id' => $data['lesson_id'],
            'instruction' => $data['instruction'],
            'writing_prompt' => $data['writing_prompt'],
            'required_vocabulary' => $data['required_vocabulary'],
            'minimum_words' => $data['minimum_words'] ?? 15,
            'benchmark_answer' => $data['benchmark_answer'],
            'evaluation_guide' => $data['evaluation_guide'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteWritingExercise(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM writing_exercises WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function writingExerciseExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM writing_exercises WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    
    // QUIZZES
    

    public function createQuiz(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO quizzes (module_id, title, passing_score, time_limit_minutes)
                 VALUES (:module_id, :title, :passing_score, :time_limit_minutes)'
            );
            $stmt->execute([
                'module_id' => $data['module_id'],
                'title' => $data['title'],
                'passing_score' => $data['passing_score'] ?? 70,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? 15,
            ]);
            $quizId = (int) $this->db->lastInsertId();

            if (!empty($data['questions']) && is_array($data['questions'])) {
                $qStmt = $this->db->prepare(
                    'INSERT INTO quiz_questions (quiz_id, question_text, question_type, explanation, score_weight, order_index)
                     VALUES (:quiz_id, :question_text, :question_type, :explanation, :score_weight, :order_index)'
                );
                $oStmt = $this->db->prepare(
                    'INSERT INTO quiz_options (question_id, option_text, is_correct)
                     VALUES (:question_id, :option_text, :is_correct)'
                );
                foreach ($data['questions'] as $i => $q) {
                    $qStmt->execute([
                        'quiz_id' => $quizId,
                        'question_text' => $q['question_text'],
                        'question_type' => $q['question_type'] ?? 'multiple_choice',
                        'explanation' => $q['explanation'] ?? null,
                        'score_weight' => $q['score_weight'] ?? 10,
                        'order_index' => $q['order_index'] ?? ($i + 1),
                    ]);
                    $qId = (int) $this->db->lastInsertId();

                    foreach ($q['options'] as $opt) {
                        $oStmt->execute([
                            'question_id' => $qId,
                            'option_text' => $opt['text'],
                            'is_correct' => !empty($opt['is_correct']) ? 1 : 0,
                        ]);
                    }
                }
            }

            $this->db->commit();
            return $quizId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteQuiz(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM quizzes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function quizExists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM quizzes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}