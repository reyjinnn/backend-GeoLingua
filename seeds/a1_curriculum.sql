-- Seed Modul A1 dan Kosakata (Safe to rerun)

SET @course_id = (SELECT c.id FROM courses c JOIN languages base ON base.id = c.base_language_id JOIN languages target ON target.id = c.target_language_id WHERE base.code = 'id' AND target.code = 'en' LIMIT 1);
SET @level_id = (SELECT id FROM levels WHERE code = 'A1' LIMIT 1);
SET @target_language_id = (SELECT id FROM languages WHERE code = 'en' LIMIT 1);
SET @base_language_id = (SELECT id FROM languages WHERE code = 'id' LIMIT 1);

-- Modul 1
INSERT INTO modules (course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, status)
SELECT @course_id, @level_id, 'EN-A1-M1', 'Perkenalan Dasar', 'Greetings', 'Modul dasar untuk mempelajari cara berkenalan.', 'Mampu menyapa orang lain dan memperkenalkan diri.', 30, 1, 'published'
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE module_code = 'EN-A1-M1');

SET @module1_id = (SELECT id FROM modules WHERE module_code = 'EN-A1-M1');

-- Modul 2 (Prerequisite: Modul 1)
INSERT INTO modules (course_id, level_id, module_code, title, topic, description, learning_objectives, estimated_duration_minutes, order_index, prerequisite_module_id, status)
SELECT @course_id, @level_id, 'EN-A1-M2', 'Aktivitas Sehari-hari', 'Daily Routine', 'Modul menceritakan aktivitas sehari-hari.', 'Mampu menceritakan aktivitas rutin.', 45, 2, @module1_id, 'published'
WHERE NOT EXISTS (SELECT 1 FROM modules WHERE module_code = 'EN-A1-M2');

-- Lesson 1 for Modul 1
INSERT INTO lessons (module_id, lesson_name, lesson_objective, grammar_notes, order_index)
SELECT @module1_id, 'Menyapa dalam Bahasa Inggris', 'Mengucapkan salam dasar', 'Gunakan "Hello" untuk situasi umum, dan "Hi" untuk situasi informal. (Catatan: Butuh kurasi lebih lanjut dari ahli bahasa)', 1
WHERE NOT EXISTS (SELECT 1 FROM lessons WHERE module_id = @module1_id AND order_index = 1);

SET @lesson1_id = (SELECT id FROM lessons WHERE module_id = @module1_id AND order_index = 1);

-- Vocabularies
-- hello
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'hello', 'həˈloʊ', 'interjection', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'hello' AND target_language_id = @target_language_id);
SET @v_hello = (SELECT id FROM vocabularies WHERE word = 'hello' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_hello, @base_language_id, 'halo', 'Kata sapaan umum.', 'Hello, my name is John.', 'Halo, nama saya John.', 'Sapaan formal/umum.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_hello AND base_language_id = @base_language_id);

-- good morning
INSERT INTO vocabularies (target_language_id, word, pronunciation, part_of_speech, difficulty)
SELECT @target_language_id, 'good morning', 'ɡʊd ˈmɔrnɪŋ', 'phrase', 'easy'
WHERE NOT EXISTS (SELECT 1 FROM vocabularies WHERE word = 'good morning' AND target_language_id = @target_language_id);
SET @v_gm = (SELECT id FROM vocabularies WHERE word = 'good morning' AND target_language_id = @target_language_id);

INSERT INTO vocabulary_translations (vocabulary_id, base_language_id, translation, definition, example_sentence, example_translation, notes)
SELECT @v_gm, @base_language_id, 'selamat pagi', 'Sapaan di pagi hari.', 'Good morning, class!', 'Selamat pagi, kelas!', 'Gunakan sebelum jam 12 siang.'
WHERE NOT EXISTS (SELECT 1 FROM vocabulary_translations WHERE vocabulary_id = @v_gm AND base_language_id = @base_language_id);

-- Link vocab to lesson
INSERT IGNORE INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index) VALUES (@lesson1_id, @v_hello, 1);
INSERT IGNORE INTO lesson_vocabularies (lesson_id, vocabulary_id, order_index) VALUES (@lesson1_id, @v_gm, 2);
