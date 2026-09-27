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
