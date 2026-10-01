-- GeoLingua demo accounts and learner activity. Run after the content seeds.
-- Password for all demo accounts: 123 (bcrypt). Development only.
-- Bare @geolingua addresses are included as requested but PHP's email
-- validation rejects them at login. Use the .test accounts for API login.

INSERT INTO users (role_id, full_name, email, password_hash, interface_language)
SELECT r.id, d.full_name, d.email,
       '$2y$10$piNQyJduymdkro/3.C.jLubeMqUvEjQI4mSJqKD0R1Q8snyN3o2Mq', 'id'
FROM (
  SELECT 'admin' AS role_name, 'Admin GeoLingua' AS full_name, 'admin@geolingua' AS email
  UNION ALL SELECT 'admin', 'Admin Demo', 'admin@geolingua.test'
  UNION ALL SELECT 'learner', 'User GeoLingua', 'user@geolingua'
  UNION ALL SELECT 'learner', 'User Demo', 'user@geolingua.test'
  UNION ALL SELECT 'learner', 'Ayu Pratama', 'ayu@geolingua.test'
  UNION ALL SELECT 'learner', 'Budi Santoso', 'budi@geolingua.test'
  UNION ALL SELECT 'learner', 'Citra Lestari', 'citra@geolingua.test'
  UNION ALL SELECT 'learner', 'Dimas Saputra', 'dimas@geolingua.test'
  UNION ALL SELECT 'learner', 'Eka Maharani', 'eka@geolingua.test'
) AS d
JOIN roles AS r ON r.name = d.role_name
WHERE NOT EXISTS (SELECT 1 FROM users AS u WHERE u.email = d.email);

-- Every learner starts the Indonesian -> English A1 course.
INSERT INTO user_learning_languages (user_id, course_id, current_level_id, is_primary)
SELECT u.id, c.id, l.id, 1
FROM users AS u
JOIN courses AS c ON c.base_language_id = (SELECT id FROM languages WHERE code = 'id')
                 AND c.target_language_id = (SELECT id FROM languages WHERE code = 'en')
JOIN levels AS l ON l.code = 'A1'
WHERE u.email IN ('user@geolingua', 'user@geolingua.test',
                  'ayu@geolingua.test', 'budi@geolingua.test',
                  'citra@geolingua.test', 'dimas@geolingua.test',
                  'eka@geolingua.test')
  AND NOT EXISTS (SELECT 1 FROM user_learning_languages AS x
                  WHERE x.user_id = u.id AND x.course_id = c.id);

-- Different learner states for testing dashboard and admin reports.
INSERT INTO user_module_progress (user_id, module_id, status, highest_quiz_score, completed_at)
SELECT u.id, m.id, d.progress_status, d.score,
       IF(d.progress_status = 'completed', '2026-09-26 10:00:00', NULL)
FROM (
  SELECT 'user@geolingua.test' AS email, 'in_progress' AS progress_status, 0 AS score
  UNION ALL SELECT 'ayu@geolingua.test', 'completed', 90
  UNION ALL SELECT 'budi@geolingua.test', 'in_progress', 50
  UNION ALL SELECT 'citra@geolingua.test', 'completed', 80
  UNION ALL SELECT 'dimas@geolingua.test', 'unlocked', 0
  UNION ALL SELECT 'eka@geolingua.test', 'in_progress', 0
) AS d
JOIN users AS u ON u.email = d.email
JOIN modules AS m ON m.module_code = 'EN-A1-M1'
WHERE NOT EXISTS (SELECT 1 FROM user_module_progress AS x
                  WHERE x.user_id = u.id AND x.module_id = m.id);

INSERT INTO user_quiz_attempts (user_id, quiz_id, score, is_passed, attempt_duration_seconds, created_at)
SELECT u.id, q.id, d.score, IF(d.score >= q.passing_score, 1, 0), d.duration_seconds, d.attempted_at
FROM (
  SELECT 'ayu@geolingua.test' AS email, 90 AS score, 1 AS attempt_no, 420 AS duration_seconds, '2026-09-26 09:50:00' AS attempted_at
  UNION ALL SELECT 'budi@geolingua.test', 50, 1, 610, '2026-09-25 11:30:00'
  UNION ALL SELECT 'citra@geolingua.test', 60, 1, 590, '2026-09-24 13:00:00'
  UNION ALL SELECT 'citra@geolingua.test', 80, 2, 480, '2026-09-26 08:45:00'
) AS d
JOIN users AS u ON u.email = d.email
JOIN modules AS m ON m.module_code = 'EN-A1-M1'
JOIN quizzes AS q ON q.module_id = m.id
WHERE NOT EXISTS (SELECT 1 FROM user_quiz_attempts AS x
                  WHERE x.user_id = u.id AND x.quiz_id = q.id AND x.created_at = d.attempted_at);

INSERT INTO user_writing_submissions
  (user_id, writing_exercise_id, submitted_text, word_count, passed_validation, self_reviewed, created_at)
SELECT u.id, w.id, d.answer_text, d.words, 1, d.reviewed, d.submitted_at
FROM (
  SELECT 'ayu@geolingua.test' AS email, 1 AS lesson_order,
         'Hello! Good morning, my friend. How are you today? I am happy to learn English with you this morning.' AS answer_text,
         21 AS words, 1 AS reviewed, '2026-09-25 09:00:00' AS submitted_at
  UNION ALL SELECT 'ayu@geolingua.test', 2,
         'Hello! My name is Ayu. I am from Indonesia. It is nice to meet you. Thank you for learning together.',
         21, 1, '2026-09-25 09:30:00'
  UNION ALL SELECT 'citra@geolingua.test', 1,
         'Hello and good morning! How are you? I am Citra and I am ready to study English with you today.',
         22, 1, '2026-09-24 10:00:00'
  UNION ALL SELECT 'budi@geolingua.test', 1,
         'Hello, good morning my friend. How are you today? I am ready to learn new English words together.',
         20, 0, '2026-09-25 10:00:00'
) AS d
JOIN users AS u ON u.email = d.email
JOIN modules AS m ON m.module_code = 'EN-A1-M1'
JOIN lessons AS l ON l.module_id = m.id AND l.order_index = d.lesson_order
JOIN writing_exercises AS w ON w.lesson_id = l.id
WHERE NOT EXISTS (SELECT 1 FROM user_writing_submissions AS x
                  WHERE x.user_id = u.id AND x.writing_exercise_id = w.id AND x.created_at = d.submitted_at);

-- Exercise attempts require migrations/001_add_exercise_attempts.sql.
INSERT INTO user_exercise_attempts (user_id, exercise_id, is_correct, user_answer, created_at)
SELECT u.id, e.id, d.is_correct, d.answer, d.attempted_at
FROM (
  SELECT 'ayu@geolingua.test' AS email, 1 AS lesson_order, 1 AS exercise_order,
         1 AS is_correct, 'halo' AS answer, '2026-09-25 08:30:00' AS attempted_at
  UNION ALL SELECT 'ayu@geolingua.test', 1, 2, 1, 'selamat pagi', '2026-09-25 08:31:00'
  UNION ALL SELECT 'citra@geolingua.test', 1, 1, 1, 'halo', '2026-09-24 09:30:00'
  UNION ALL SELECT 'budi@geolingua.test', 1, 1, 0, 'selamat tinggal', '2026-09-25 09:15:00'
  UNION ALL SELECT 'budi@geolingua.test', 1, 1, 1, 'halo', '2026-09-25 09:17:00'
  UNION ALL SELECT 'user@geolingua.test', 1, 1, 1, 'halo', '2026-09-27 14:00:00'
  UNION ALL SELECT 'eka@geolingua.test', 1, 1, 0, 'terima kasih', '2026-09-27 15:00:00'
) AS d
JOIN users AS u ON u.email = d.email
JOIN modules AS m ON m.module_code = 'EN-A1-M1'
JOIN lessons AS l ON l.module_id = m.id AND l.order_index = d.lesson_order
JOIN exercises AS e ON e.lesson_id = l.id AND e.order_index = d.exercise_order
WHERE NOT EXISTS (SELECT 1 FROM user_exercise_attempts AS x
                  WHERE x.user_id = u.id AND x.exercise_id = e.id AND x.created_at = d.attempted_at);

-- user_tokens intentionally has no fixed demo rows: login issues fresh random tokens.
