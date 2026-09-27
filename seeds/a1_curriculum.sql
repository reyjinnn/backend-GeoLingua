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
