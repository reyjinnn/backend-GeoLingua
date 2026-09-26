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

