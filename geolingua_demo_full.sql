-- GeoLingua complete schema and demo data for MariaDB 10.4+ / MySQL 8.0+
-- Select the target database before importing. Development only.
-- Safe to rerun; existing records are preserved.

-- BEGIN db.sql
-- GeoLingua schema for MariaDB 10.4+ / MySQL 8.0+
-- Select the target database in phpMyAdmin before importing this file.
-- The table order follows foreign-key dependencies; existing data is not dropped.

-- 1. Roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(30) NOT NULL UNIQUE,
  `description` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 2. Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `interface_language` VARCHAR(5) NOT NULL DEFAULT 'id',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 3. User Tokens
CREATE TABLE IF NOT EXISTS `user_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 4. Languages
CREATE TABLE IF NOT EXISTS `languages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(10) NOT NULL UNIQUE,
  `name` VARCHAR(50) NOT NULL,
  `native_name` VARCHAR(50) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 5. Levels (CEFR)
CREATE TABLE IF NOT EXISTS `levels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(5) NOT NULL UNIQUE,
  `name` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `order_index` INT NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 6. Courses (Pairings)
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `base_language_id` INT UNSIGNED NOT NULL,
  `target_language_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_course_pair` (`base_language_id`, `target_language_id`),
  CONSTRAINT `fk_course_base` FOREIGN KEY (`base_language_id`) REFERENCES `languages` (`id`),
  CONSTRAINT `fk_course_target` FOREIGN KEY (`target_language_id`) REFERENCES `languages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 7. User Learning Languages
CREATE TABLE IF NOT EXISTS `user_learning_languages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `current_level_id` INT UNSIGNED NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_course` (`user_id`, `course_id`),
  CONSTRAINT `fk_ull_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ull_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ull_level` FOREIGN KEY (`current_level_id`) REFERENCES `levels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 8. Modules
CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` INT UNSIGNED NOT NULL,
  `level_id` INT UNSIGNED NOT NULL,
  `module_code` VARCHAR(20) NOT NULL UNIQUE,
  `title` VARCHAR(150) NOT NULL,
  `topic` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `learning_objectives` TEXT NOT NULL,
  `estimated_duration_minutes` INT NOT NULL DEFAULT 30,
  `order_index` INT NOT NULL,
  `prerequisite_module_id` INT UNSIGNED NULL,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_module_order` (`course_id`, `level_id`, `order_index`),
  CONSTRAINT `fk_modules_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  CONSTRAINT `fk_modules_level` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`),
  CONSTRAINT `fk_modules_prereq` FOREIGN KEY (`prerequisite_module_id`) REFERENCES `modules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 9. Lessons
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NOT NULL,
  `lesson_name` VARCHAR(150) NOT NULL,
  `lesson_objective` TEXT NOT NULL,
  `grammar_notes` TEXT NULL,
  `order_index` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lessons_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 10. Vocabularies
CREATE TABLE IF NOT EXISTS `vocabularies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_language_id` INT UNSIGNED NOT NULL,
  `word` VARCHAR(100) NOT NULL,
  `pronunciation` VARCHAR(100) NOT NULL,
  `part_of_speech` VARCHAR(30) NOT NULL,
  `difficulty` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'easy',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_vocab_word` (`word`),
  CONSTRAINT `fk_vocab_lang` FOREIGN KEY (`target_language_id`) REFERENCES `languages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 11. Vocabulary Translations
CREATE TABLE IF NOT EXISTS `vocabulary_translations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vocabulary_id` INT UNSIGNED NOT NULL,
  `base_language_id` INT UNSIGNED NOT NULL,
  `translation` VARCHAR(200) NOT NULL,
  `definition` TEXT NOT NULL,
  `example_sentence` TEXT NOT NULL,
  `example_translation` TEXT NOT NULL,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vocab_trans` (`vocabulary_id`, `base_language_id`),
  CONSTRAINT `fk_vt_vocab` FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabularies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vt_base_lang` FOREIGN KEY (`base_language_id`) REFERENCES `languages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 12. Lesson Vocabularies (Pivot)
CREATE TABLE IF NOT EXISTS `lesson_vocabularies` (
  `lesson_id` INT UNSIGNED NOT NULL,
  `vocabulary_id` INT UNSIGNED NOT NULL,
  `order_index` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`lesson_id`, `vocabulary_id`),
  CONSTRAINT `fk_lv_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lv_vocab` FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabularies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 13. Exercises (Drill Questions)
CREATE TABLE IF NOT EXISTS `exercises` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lesson_id` INT UNSIGNED NOT NULL,
  `vocabulary_id` INT UNSIGNED NOT NULL,
  `question_type` ENUM('mcq_meaning', 'mcq_word', 'fill_blank', 'typing') NOT NULL,
  `prompt` TEXT NOT NULL,
  `correct_answer` VARCHAR(255) NOT NULL,
  `explanation` TEXT NULL,
  `order_index` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_exercises_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exercises_vocab` FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabularies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 14. Exercise Options
CREATE TABLE IF NOT EXISTS `exercise_options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exercise_id` INT UNSIGNED NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_eo_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 15. Writing Exercises
CREATE TABLE IF NOT EXISTS `writing_exercises` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lesson_id` INT UNSIGNED NOT NULL,
  `instruction` TEXT NOT NULL,
  `writing_prompt` TEXT NOT NULL,
  `required_vocabulary` VARCHAR(255) NOT NULL,
  `minimum_words` INT NOT NULL DEFAULT 15,
  `benchmark_answer` TEXT NOT NULL,
  `evaluation_guide` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_we_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 16. User Writing Submissions
CREATE TABLE IF NOT EXISTS `user_writing_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `writing_exercise_id` INT UNSIGNED NOT NULL,
  `submitted_text` TEXT NOT NULL,
  `word_count` INT NOT NULL,
  `passed_validation` TINYINT(1) NOT NULL DEFAULT 1,
  `self_reviewed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_uws_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uws_exercise` FOREIGN KEY (`writing_exercise_id`) REFERENCES `writing_exercises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 17. Quizzes
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `passing_score` INT NOT NULL DEFAULT 70,
  `time_limit_minutes` INT NOT NULL DEFAULT 15,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_quizzes_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 18. Quiz Questions
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quiz_id` INT UNSIGNED NOT NULL,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('multiple_choice', 'fill_blank') NOT NULL DEFAULT 'multiple_choice',
  `explanation` TEXT NULL,
  `score_weight` INT NOT NULL DEFAULT 10,
  `order_index` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_qq_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 19. Quiz Options
CREATE TABLE IF NOT EXISTS `quiz_options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_qo_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 20. User Module Progress
CREATE TABLE IF NOT EXISTS `user_module_progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `module_id` INT UNSIGNED NOT NULL,
  `status` ENUM('locked', 'unlocked', 'in_progress', 'completed') NOT NULL DEFAULT 'unlocked',
  `highest_quiz_score` INT NOT NULL DEFAULT 0,
  `completed_at` DATETIME NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_module` (`user_id`, `module_id`),
  CONSTRAINT `fk_ump_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ump_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 21. User Quiz Attempts
CREATE TABLE IF NOT EXISTS `user_quiz_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `quiz_id` INT UNSIGNED NOT NULL,
  `score` INT NOT NULL,
  `is_passed` TINYINT(1) NOT NULL,
  `attempt_duration_seconds` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_uqa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uqa_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Minimum role data required by the authentication endpoints.
INSERT INTO `roles` (`name`, `description`)
SELECT 'learner', 'Student account'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'learner');

INSERT INTO `roles` (`name`, `description`)
SELECT 'admin', 'Administrator account'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'admin');


-- END db.sql

-- BEGIN migrations/001_add_exercise_attempts.sql
CREATE TABLE IF NOT EXISTS `user_exercise_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `exercise_id` INT UNSIGNED NOT NULL,
  `is_correct` TINYINT(1) NOT NULL,
  `user_answer` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_uea_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uea_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- END migrations/001_add_exercise_attempts.sql

-- BEGIN seeds/onboarding.sql
-- Import after db.sql. Safe to rerun; no administrator account is created.
INSERT INTO `languages` (`code`, `name`, `native_name`)
SELECT 'id', 'Indonesian', 'Bahasa Indonesia'
WHERE NOT EXISTS (SELECT 1 FROM `languages` WHERE `code` = 'id');

INSERT INTO `languages` (`code`, `name`, `native_name`)
SELECT 'en', 'English', 'English'
WHERE NOT EXISTS (SELECT 1 FROM `languages` WHERE `code` = 'en');

INSERT INTO `languages` (`code`, `name`, `native_name`)
SELECT 'ja', 'Japanese', '日本語'
WHERE NOT EXISTS (SELECT 1 FROM `languages` WHERE `code` = 'ja');

INSERT INTO `levels` (`code`, `name`, `order_index`)
SELECT 'A1', 'Beginner', 1 WHERE NOT EXISTS (SELECT 1 FROM `levels` WHERE `code` = 'A1');
INSERT INTO `levels` (`code`, `name`, `order_index`)
SELECT 'A2', 'Elementary', 2 WHERE NOT EXISTS (SELECT 1 FROM `levels` WHERE `code` = 'A2');
INSERT INTO `levels` (`code`, `name`, `order_index`)
SELECT 'B1', 'Intermediate', 3 WHERE NOT EXISTS (SELECT 1 FROM `levels` WHERE `code` = 'B1');
INSERT INTO `levels` (`code`, `name`, `order_index`)
SELECT 'B2', 'Upper Intermediate', 4 WHERE NOT EXISTS (SELECT 1 FROM `levels` WHERE `code` = 'B2');
INSERT INTO `levels` (`code`, `name`, `order_index`)
SELECT 'C1', 'Advanced', 5 WHERE NOT EXISTS (SELECT 1 FROM `levels` WHERE `code` = 'C1');
INSERT INTO `levels` (`code`, `name`, `order_index`)
SELECT 'C2', 'Proficient', 6 WHERE NOT EXISTS (SELECT 1 FROM `levels` WHERE `code` = 'C2');

-- The first MVP course. Additional language pairs can be added without changing the API.
INSERT INTO `courses` (`base_language_id`, `target_language_id`, `title`, `description`)
SELECT base.id, target.id, 'English for Indonesian Speakers', 'Belajar bahasa Inggris dasar untuk penutur bahasa Indonesia.'
FROM `languages` base CROSS JOIN `languages` target
WHERE base.code = 'id' AND target.code = 'en'
  AND NOT EXISTS (
      SELECT 1 FROM `courses` existing
      WHERE existing.base_language_id = base.id AND existing.target_language_id = target.id
  );

-- END seeds/onboarding.sql

-- BEGIN seeds/a1_curriculum.sql
-- Seed Modul A1 dan Kosakata (Safe to rerun / Idempotent)

SET @course_id = (SELECT c.id FROM courses c JOIN languages base ON base.id = c.base_language_id JOIN languages target ON target.id = c.target_language_id WHERE base.code = 'id' AND target.code = 'en' LIMIT 1);
SET @level_id = (SELECT id FROM levels WHERE code = 'A1' LIMIT 1);
SET @target_language_id = (SELECT id FROM languages WHERE code = 'en' LIMIT 1);
SET @base_language_id = (SELECT id FROM languages WHERE code = 'id' LIMIT 1);

-- Modul 1: Perkenalan Dasar (Published)
INSERT INTO modules (course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, status)
SELECT @course_id, @level_id, 'EN-A1-M1', 'Perkenalan Dasar', 'Greetings & Introductions', 'Modul dasar untuk mempelajari cara menyapa dan berkenalan dalam bahasa Inggris.', 'Mampu menyapa orang lain, menanyakan kabar, dan memperkenalkan diri.', 45, 1, 'published'
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE module_code = 'EN-A1-M1');

SET @module1_id = (SELECT id FROM modules WHERE module_code = 'EN-A1-M1');

-- Modul 2: Aktivitas Sehari-hari (Draft - belum ada materi lengkap)
INSERT INTO modules (course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, prerequisite_module_id, status)
SELECT @course_id, @level_id, 'EN-A1-M2', 'Aktivitas Sehari-hari', 'Daily Routine', 'Modul menceritakan aktivitas sehari-hari.', 'Mampu menceritakan aktivitas rutin.', 45, 2, @module1_id, 'draft'
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE module_code = 'EN-A1-M2');

-- Update Modul 2 to draft if already exists as published without lessons
UPDATE modules SET status = 'draft', prerequisite_module_id = @module1_id WHERE module_code = 'EN-A1-M2' AND status = 'published';

-- ========================================================
-- LESSONS FOR MODUL 1
-- ========================================================

-- Lesson 1: Menyapa dalam Bahasa Inggris
INSERT INTO lessons (module_id, lesson_name, lesson_objective, grammar_notes, order_index)
SELECT @module1_id, 'Menyapa dalam Bahasa Inggris', 'Mengucapkan salam dan menanyakan kabar.', 'Gunakan "Hello" untuk situasi umum, "Hi" untuk informal. Gunakan "Good morning" sebelum pukul 12.00, "Good afternoon" antara 12.00-18.00, dan "Good evening" setelah 18.00.', 1
WHERE NOT EXISTS (SELECT 1 FROM lessons WHERE module_id = @module1_id AND order_index = 1);

SET @lesson1_id = (SELECT id FROM lessons WHERE module_id = @module1_id AND order_index = 1);

-- Lesson 2: Memperkenalkan Diri
INSERT INTO lessons (module_id, lesson_name, lesson_objective, grammar_notes, order_index)
SELECT @module1_id, 'Memperkenalkan Diri', 'Menyebutkan nama dan bertukar sapaan perkenalan.', 'Gunakan pola "My name is [Nama]" atau "I am [Nama]". Saat pertama kali berkenalan, katakan "Nice to meet you" yang dijawab dengan "Nice to meet you too".', 2
WHERE NOT EXISTS (SELECT 1 FROM lessons WHERE module_id = @module1_id AND order_index = 2);

SET @lesson2_id = (SELECT id FROM lessons WHERE module_id = @module1_id AND order_index = 2);

-- ========================================================
-- 15 VOCABULARIES (A1 Greetings & Introductions)
-- ========================================================

-- Helper stored procedure / macro pattern using INSERT WHERE NOT EXISTS
-- 1. hello
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'hello', 'həˈloʊ', 'interjection', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'hello' AND target_language_id = @target_language_id);
SET @v_hello = (SELECT id FROM vocabularies WHERE word = 'hello' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_hello, @base_language_id, 'halo', 'Kata sapaan umum.', 'Hello, my name is John.', 'Halo, nama saya John.', 'Sapaan formal dan umum.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_hello AND base_language_id = @base_language_id);

-- 2. hi
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'hi', 'haɪ', 'interjection', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'hi' AND target_language_id = @target_language_id);
SET @v_hi = (SELECT id FROM vocabularies WHERE word = 'hi' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_hi, @base_language_id, 'hai', 'Kata sapaan santai / informal.', 'Hi Sarah, how are you?', 'Hai Sarah, apa kabar?', 'Digunakan untuk teman atau situasi santai.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_hi AND base_language_id = @base_language_id);

-- 3. good morning
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'good morning', 'ɡʊd ˈmɔrnɪŋ', 'phrase', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'good morning' AND target_language_id = @target_language_id);
SET @v_gm = (SELECT id FROM vocabularies WHERE word = 'good morning' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_gm, @base_language_id, 'selamat pagi', 'Sapaan di pagi hari.', 'Good morning, class!', 'Selamat pagi, kelas!', 'Gunakan sebelum jam 12.00 siang.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_gm AND base_language_id = @base_language_id);

-- 4. good afternoon
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'good afternoon', 'ɡʊd ˌæftərˈnun', 'phrase', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'good afternoon' AND target_language_id = @target_language_id);
SET @v_ga = (SELECT id FROM vocabularies WHERE word = 'good afternoon' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_ga, @base_language_id, 'selamat siang', 'Sapaan dari siang hingga sore hari.', 'Good afternoon, Mr. Smith.', 'Selamat siang, Pak Smith.', 'Gunakan antara jam 12.00 hingga 18.00.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_ga AND base_language_id = @base_language_id);

-- 5. good evening
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'good evening', 'ɡʊd ˈivnɪŋ', 'phrase', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'good evening' AND target_language_id = @target_language_id);
SET @v_ge = (SELECT id FROM vocabularies WHERE word = 'good evening' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_ge, @base_language_id, 'selamat malam', 'Sapaan saat bertemu di malam hari.', 'Good evening, ladies and gentlemen.', 'Selamat malam, hadirin sekalian.', 'Sapaan kedatangan (berbeda dengan good night untuk perpisahan).'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_ge AND base_language_id = @base_language_id);

-- 6. goodbye
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'goodbye', 'ɡʊdˈbaɪ', 'interjection', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'goodbye' AND target_language_id = @target_language_id);
SET @v_gb = (SELECT id FROM vocabularies WHERE word = 'goodbye' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_gb, @base_language_id, 'selamat tinggal', 'Ucapan perpisahan.', 'Goodbye, see you tomorrow!', 'Selamat tinggal, sampai jumpa besok!', 'Sapaan perpisahan umum.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_gb AND base_language_id = @base_language_id);

-- 7. how
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'how', 'haʊ', 'adverb', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'how' AND target_language_id = @target_language_id);
SET @v_how = (SELECT id FROM vocabularies WHERE word = 'how' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_how, @base_language_id, 'bagaimana', 'Kata tanya untuk cara atau kondisi.', 'How are you today?', 'Bagaimana kabar Anda hari ini?', 'Kata tanya dasar.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_how AND base_language_id = @base_language_id);

-- 8. fine
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'fine', 'faɪn', 'adjective', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'fine' AND target_language_id = @target_language_id);
SET @v_fine = (SELECT id FROM vocabularies WHERE word = 'fine' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_fine, @base_language_id, 'baik / sehat', 'Keadaan sehat atau memuaskan.', 'I am fine, thank you.', 'Saya baik-baik saja, terima kasih.', 'Jawaban umum atas pertanyaan kabar.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_fine AND base_language_id = @base_language_id);

-- 9. thank you
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'thank you', 'θæŋk ju', 'phrase', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'thank you' AND target_language_id = @target_language_id);
SET @v_ty = (SELECT id FROM vocabularies WHERE word = 'thank you' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_ty, @base_language_id, 'terima kasih', 'Ungkapan rasa terima kasih dan syukur.', 'Thank you for your help.', 'Terima kasih atas bantuan Anda.', 'Ungkapan kesopanan dasar.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_ty AND base_language_id = @base_language_id);

-- 10. name
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'name', 'neɪm', 'noun', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'name' AND target_language_id = @target_language_id);
SET @v_name = (SELECT id FROM vocabularies WHERE word = 'name' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_name, @base_language_id, 'nama', 'Sebutan untuk orang, tempat, atau benda.', 'What is your name?', 'Siapa nama Anda?', 'Kosakata dasar perkenalan.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_name AND base_language_id = @base_language_id);

-- 11. my
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'my', 'maɪ', 'pronoun', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'my' AND target_language_id = @target_language_id);
SET @v_my = (SELECT id FROM vocabularies WHERE word = 'my' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_my, @base_language_id, 'milik saya / -ku', 'Kata ganti kepemilikan orang pertama tunggal.', 'My name is Alex.', 'Nama saya Alex.', 'Possessive adjective.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_my AND base_language_id = @base_language_id);

-- 12. I
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'I', 'aɪ', 'pronoun', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'I' AND target_language_id = @target_language_id);
SET @v_i = (SELECT id FROM vocabularies WHERE word = 'I' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_i, @base_language_id, 'saya / aku', 'Kata ganti subjek orang pertama tunggal.', 'I am a student.', 'Saya seorang siswa.', 'Selalu ditulis huruf kapital dalam bahasa Inggris.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_i AND base_language_id = @base_language_id);

-- 13. you
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'you', 'ju', 'pronoun', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'you' AND target_language_id = @target_language_id);
SET @v_you = (SELECT id FROM vocabularies WHERE word = 'you' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_you, @base_language_id, 'kamu / Anda', 'Kata ganti orang kedua tunggal maupun jamak.', 'It is good to see you.', 'Senang bertemu denganmu.', 'Dapat berupa tunggal atau jamak.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_you AND base_language_id = @base_language_id);

-- 14. nice
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'nice', 'naɪs', 'adjective', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'nice' AND target_language_id = @target_language_id);
SET @v_nice = (SELECT id FROM vocabularies WHERE word = 'nice' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_nice, @base_language_id, 'senang / bagus', 'Menyenangkan, ramah, atau baik.', 'Nice to meet you.', 'Senang berkenalan dengan Anda.', 'Digunakan dalam ungkapan perkenalan.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_nice AND base_language_id = @base_language_id);

-- 15. meet
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'meet', 'mit', 'verb', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'meet' AND target_language_id = @target_language_id);
SET @v_meet = (SELECT id FROM vocabularies WHERE word = 'meet' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_meet, @base_language_id, 'bertemu / berkenalan', 'Berjumpa atau berkenalan dengan seseorang.', 'We meet every Monday.', 'Kami bertemu setiap hari Senin.', 'Bentuk lampau adalah met.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_meet AND base_language_id = @base_language_id);

-- ========================================================
-- LINK VOCABULARIES TO LESSONS
-- Lesson 1 (Greetings): hello, hi, good morning, good afternoon, good evening, goodbye, how, fine
-- Lesson 2 (Introductions): name, my, I, you, nice, meet, thank you
-- ========================================================

INSERT IGNORE INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index) VALUES
(@lesson1_id, @v_hello, 1),
(@lesson1_id, @v_hi, 2),
(@lesson1_id, @v_gm, 3),
(@lesson1_id, @v_ga, 4),
(@lesson1_id, @v_ge, 5),
(@lesson1_id, @v_gb, 6),
(@lesson1_id, @v_how, 7),
(@lesson1_id, @v_fine, 8),
(@lesson2_id, @v_name, 1),
(@lesson2_id, @v_my, 2),
(@lesson2_id, @v_i, 3),
(@lesson2_id, @v_you, 4),
(@lesson2_id, @v_nice, 5),
(@lesson2_id, @v_meet, 6),
(@lesson2_id, @v_ty, 7);

-- END seeds/a1_curriculum.sql

-- BEGIN seeds/a1_drills.sql
-- Seed untuk Drill A1 (Minimal 10 Latihan: MCQ dan Typing)
-- Safe to rerun (Idempotent)

SET @lesson1_id = (SELECT id FROM lessons WHERE lesson_name = 'Menyapa dalam Bahasa Inggris' LIMIT 1);
SET @lesson2_id = (SELECT id FROM lessons WHERE lesson_name = 'Memperkenalkan Diri' LIMIT 1);

SET @v_hello = (SELECT id FROM vocabularies WHERE word = 'hello' LIMIT 1);
SET @v_gm    = (SELECT id FROM vocabularies WHERE word = 'good morning' LIMIT 1);
SET @v_ga    = (SELECT id FROM vocabularies WHERE word = 'good afternoon' LIMIT 1);
SET @v_how   = (SELECT id FROM vocabularies WHERE word = 'how' LIMIT 1);
SET @v_gb    = (SELECT id FROM vocabularies WHERE word = 'goodbye' LIMIT 1);

SET @v_name  = (SELECT id FROM vocabularies WHERE word = 'name' LIMIT 1);
SET @v_ty    = (SELECT id FROM vocabularies WHERE word = 'thank you' LIMIT 1);
SET @v_meet  = (SELECT id FROM vocabularies WHERE word = 'meet' LIMIT 1);
SET @v_i     = (SELECT id FROM vocabularies WHERE word = 'I' LIMIT 1);
SET @v_nice  = (SELECT id FROM vocabularies WHERE word = 'nice' LIMIT 1);

-- ========================================================
-- DRILLS FOR LESSON 1 (5 Soal)
-- ========================================================

-- Drill 1: MCQ - hello
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson1_id, @v_hello, 'mcq_meaning', 'Apa arti dari kata "hello"?', 'halo', 'Hello adalah kata sapaan umum yang berarti halo.', 1
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 1);
SET @ex1_id = (SELECT id FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 1);

INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex1_id, 'halo', 1 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex1_id AND option_text = 'halo');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex1_id, 'selamat tinggal', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex1_id AND option_text = 'selamat tinggal');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex1_id, 'terima kasih', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex1_id AND option_text = 'terima kasih');

-- Drill 2: MCQ - good morning
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson1_id, @v_gm, 'mcq_meaning', 'Apa arti dari ungkapan "good morning"?', 'selamat pagi', 'Good morning digunakan untuk menyapa seseorang di pagi hari sebelum jam 12 siang.', 2
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 2);
SET @ex2_id = (SELECT id FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 2);

INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex2_id, 'selamat pagi', 1 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex2_id AND option_text = 'selamat pagi');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex2_id, 'selamat malam', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex2_id AND option_text = 'selamat malam');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex2_id, 'sampai jumpa', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex2_id AND option_text = 'sampai jumpa');

-- Drill 3: Typing - good afternoon
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson1_id, @v_ga, 'typing', 'Tulis terjemahan dari "selamat siang" dalam bahasa Inggris.', 'good afternoon', 'Good afternoon digunakan untuk menyapa antara jam 12.00 hingga 18.00.', 3
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 3);

-- Drill 4: MCQ - how
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson1_id, @v_how, 'mcq_meaning', 'Pertanyaan "How are you?" digunakan untuk...', 'menanyakan kabar', 'How are you berarti "Apa kabar?" dan digunakan untuk menanyakan kabar orang lain.', 4
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 4);
SET @ex4_id = (SELECT id FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 4);

INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex4_id, 'menanyakan kabar', 1 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex4_id AND option_text = 'menanyakan kabar');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex4_id, 'menanyakan nama', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex4_id AND option_text = 'menanyakan nama');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex4_id, 'mengucapkan selamat tinggal', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex4_id AND option_text = 'mengucapkan selamat tinggal');

-- Drill 5: Typing - goodbye
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson1_id, @v_gb, 'typing', 'Tulis terjemahan dari "selamat tinggal" dalam bahasa Inggris.', 'goodbye', 'Goodbye adalah ucapan perpisahan umum dalam bahasa Inggris.', 5
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson1_id AND order_index = 5);

-- ========================================================
-- DRILLS FOR LESSON 2 (5 Soal)
-- ========================================================

-- Drill 6: MCQ - name
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson2_id, @v_name, 'mcq_meaning', 'Kata "name" dalam bahasa Indonesia berarti...', 'nama', 'Name berarti sebutan atau nama seseorang.', 1
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 1);
SET @ex6_id = (SELECT id FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 1);

INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex6_id, 'nama', 1 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex6_id AND option_text = 'nama');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex6_id, 'alamat', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex6_id AND option_text = 'alamat');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex6_id, 'umur', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex6_id AND option_text = 'umur');

-- Drill 7: Typing - thank you
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson2_id, @v_ty, 'typing', 'Tulis ungkapan "terima kasih" dalam bahasa Inggris.', 'thank you', 'Thank you adalah ungkapan sopan untuk menyampaikan rasa terima kasih.', 2
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 2);

-- Drill 8: MCQ - meet
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson2_id, @v_meet, 'mcq_meaning', 'Respon yang sopan untuk "Nice to meet you" adalah...', 'Nice to meet you too', 'Menambahkan "too" di akhir berarti "senang bertemu denganmu juga".', 3
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 3);
SET @ex8_id = (SELECT id FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 3);

INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex8_id, 'Nice to meet you too', 1 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex8_id AND option_text = 'Nice to meet you too');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex8_id, 'Good night', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex8_id AND option_text = 'Good night');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex8_id, 'I am fine', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex8_id AND option_text = 'I am fine');

-- Drill 9: MCQ - I
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson2_id, @v_i, 'mcq_meaning', 'Kata ganti "I" dalam bahasa Indonesia adalah...', 'saya', '"I" adalah kata ganti orang pertama tunggal dan selalu ditulis kapital.', 4
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 4);
SET @ex9_id = (SELECT id FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 4);

INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex9_id, 'saya', 1 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex9_id AND option_text = 'saya');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex9_id, 'kamu', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex9_id AND option_text = 'kamu');
INSERT INTO exercise_options (exercise_id, option_text, is_correct) SELECT @ex9_id, 'dia', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex9_id AND option_text = 'dia');

-- Drill 10: Typing - nice
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson2_id, @v_nice, 'typing', 'Tulis kata sifat bahasa Inggris untuk "menyenangkan / bagus" (diawali huruf n):', 'nice', 'Nice digunakan dalam ungkapan perkenalan "Nice to meet you".', 5
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson2_id AND order_index = 5);

-- END seeds/a1_drills.sql

-- BEGIN seeds/a1_writing.sql
-- Seed untuk Writing A1 (Safe to rerun / Idempotent)

SET @lesson1_id = (SELECT id FROM lessons WHERE lesson_name = 'Menyapa dalam Bahasa Inggris' LIMIT 1);
SET @lesson2_id = (SELECT id FROM lessons WHERE lesson_name = 'Memperkenalkan Diri' LIMIT 1);

-- Writing untuk Lesson 1: Menyapa Teman
INSERT INTO writing_exercises (lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide)
SELECT @lesson1_id, 
  'Tulis sapaan singkat kepada teman sekelas.', 
  'Tulis pesan singkat menyapa teman sekelas Anda di pagi hari, tanyakan kabarnya, dan sampaikan bahwa Anda siap belajar bersama.', 
  'hello, good morning', 
  15, 
  'Hello! Good morning, my friend. How are you today? I am very excited and ready to learn English together with you.', 
  'Gunakan sapaan di awal kalimat dengan huruf kapital yang benar. Pastikan kata "hello" dan "good morning" digunakan dalam konteks yang tepat.'
WHERE NOT EXISTS (SELECT 1 FROM writing_exercises WHERE lesson_id = @lesson1_id);

-- Writing untuk Lesson 2: Memperkenalkan Diri
INSERT INTO writing_exercises (lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide)
SELECT @lesson2_id, 
  'Tulis perkenalan diri lengkap.', 
  'Perkenalkan nama Anda, asal Anda, dan ungkapkan bahwa Anda senang bertemu dengannya serta ucapkan terima kasih.', 
  'name, meet, thank you', 
  15, 
  'Hello everyone! My name is Raihan. I am from Indonesia. It is nice to meet you all here. Thank you very much.', 
  'Periksa kesesuaian subjek dan kata kerja (My name is...), penggunaan kata sifat dan kata kerja (meet), serta ungkapan kesopanan (thank you).'
WHERE NOT EXISTS (SELECT 1 FROM writing_exercises WHERE lesson_id = @lesson2_id);

-- END seeds/a1_writing.sql

-- BEGIN seeds/a1_quiz.sql
-- Seed untuk Quiz Modul A1 (10 Soal)
-- Safe to rerun (Idempotent)

SET @module1_id = (SELECT id FROM modules WHERE module_code = 'EN-A1-M1' LIMIT 1);
-- If module1_id is null (e.g. tests using 'M1' instead), fallback:
SET @module1_id = IFNULL(@module1_id, (SELECT id FROM modules WHERE module_code = 'M1' LIMIT 1));

INSERT INTO quizzes (module_id, title, passing_score, time_limit_minutes)
SELECT @module1_id, 'Kuis Modul 1', 70, 15
WHERE NOT EXISTS (SELECT 1 FROM quizzes WHERE module_id = @module1_id);

SET @quiz1_id = (SELECT id FROM quizzes WHERE module_id = @module1_id LIMIT 1);

-- Q1
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Apa arti dari "Good morning"?', 10, 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 1);
SET @q1 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 1);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q1, 'Selamat pagi', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q1 AND option_text = 'Selamat pagi');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q1, 'Selamat malam', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q1 AND option_text = 'Selamat malam');

-- Q2
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Terjemahan "Hello" adalah...', 10, 2 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 2);
SET @q2 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 2);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q2, 'Halo', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q2 AND option_text = 'Halo');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q2, 'Terima kasih', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q2 AND option_text = 'Terima kasih');

-- Q3
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Pilih kalimat yang benar: "My name ___ Raihan"', 10, 3 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 3);
SET @q3 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 3);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q3, 'is', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q3 AND option_text = 'is');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q3, 'are', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q3 AND option_text = 'are');

-- Q4
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, '"How are you?" artinya...', 10, 4 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 4);
SET @q4 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 4);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q4, 'Apa kabar?', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q4 AND option_text = 'Apa kabar?');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q4, 'Siapa nama Anda?', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q4 AND option_text = 'Siapa nama Anda?');

-- Q5
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Respon untuk "Nice to meet you" adalah...', 10, 5 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 5);
SET @q5 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 5);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q5, 'Nice to meet you too', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q5 AND option_text = 'Nice to meet you too');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q5, 'Good morning', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q5 AND option_text = 'Good morning');

-- Q6
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Kata ganti untuk "saya" dalam bahasa Inggris?', 10, 6 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 6);
SET @q6 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 6);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q6, 'I', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q6 AND option_text = 'I');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q6, 'You', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q6 AND option_text = 'You');

-- Q7
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Kata ganti untuk "kamu"?', 10, 7 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 7);
SET @q7 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 7);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q7, 'You', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q7 AND option_text = 'You');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q7, 'He', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q7 AND option_text = 'He');

-- Q8
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Terjemahan "Terima kasih" adalah...', 10, 8 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 8);
SET @q8 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 8);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q8, 'Thank you', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q8 AND option_text = 'Thank you');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q8, 'Please', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q8 AND option_text = 'Please');

-- Q9
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, 'Sapaan siang hari:', 10, 9 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 9);
SET @q9 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 9);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q9, 'Good afternoon', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q9 AND option_text = 'Good afternoon');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q9, 'Good evening', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q9 AND option_text = 'Good evening');

-- Q10
INSERT INTO quiz_questions (quiz_id, question_text, score_weight, order_index)
SELECT @quiz1_id, '"I am from Indonesia" berarti...', 10, 10 WHERE NOT EXISTS (SELECT 1 FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 10);
SET @q10 = (SELECT id FROM quiz_questions WHERE quiz_id = @quiz1_id AND order_index = 10);
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q10, 'Saya dari Indonesia', 1 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q10 AND option_text = 'Saya dari Indonesia');
INSERT INTO quiz_options (question_id, option_text, is_correct) SELECT @q10, 'Saya orang Indonesia', 0 WHERE NOT EXISTS (SELECT 1 FROM quiz_options WHERE question_id = @q10 AND option_text = 'Saya orang Indonesia');

-- END seeds/a1_quiz.sql

-- BEGIN seeds/demo_accounts_activity.sql
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

-- END seeds/demo_accounts_activity.sql
