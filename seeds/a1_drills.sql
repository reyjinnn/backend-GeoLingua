-- Seed untuk Drill A1 (Latihan MCQ dan Typing)
-- Safe to rerun (Idempotent)

SET @lesson1_id = (SELECT id FROM lessons WHERE lesson_name = 'Menyapa dalam Bahasa Inggris' LIMIT 1);
SET @v_hello = (SELECT id FROM vocabularies WHERE word = 'hello' LIMIT 1);
SET @v_gm = (SELECT id FROM vocabularies WHERE word = 'good morning' LIMIT 1);

-- MCQ 1: Apa arti 'hello'?
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson1_id, @v_hello, 'mcq_meaning', 'Apa arti dari kata "hello"?', 'halo', 'Hello adalah kata sapaan umum yang berarti halo.', 1
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson1_id AND prompt = 'Apa arti dari kata "hello"?');

SET @ex1_id = (SELECT id FROM exercises WHERE lesson_id = @lesson1_id AND prompt = 'Apa arti dari kata "hello"?');

INSERT INTO exercise_options (exercise_id, option_text, is_correct)
SELECT @ex1_id, 'halo', 1 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex1_id AND option_text = 'halo');
INSERT INTO exercise_options (exercise_id, option_text, is_correct)
SELECT @ex1_id, 'selamat tinggal', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex1_id AND option_text = 'selamat tinggal');
INSERT INTO exercise_options (exercise_id, option_text, is_correct)
SELECT @ex1_id, 'terima kasih', 0 WHERE NOT EXISTS (SELECT 1 FROM exercise_options WHERE exercise_id = @ex1_id AND option_text = 'terima kasih');

-- Typing 1: Tulis 'selamat pagi' dalam bahasa Inggris
INSERT INTO exercises (lesson_id, vocabulary_id, question_type, prompt, correct_answer, explanation, order_index)
SELECT @lesson1_id, @v_gm, 'typing', 'Tulis terjemahan dari "selamat pagi" dalam bahasa Inggris.', 'good morning', 'Good morning digunakan untuk menyapa di pagi hari.', 2
WHERE NOT EXISTS (SELECT 1 FROM exercises WHERE lesson_id = @lesson1_id AND prompt = 'Tulis terjemahan dari "selamat pagi" dalam bahasa Inggris.');
