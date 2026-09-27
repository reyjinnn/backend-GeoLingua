-- Seed untuk Writing A1
-- Safe to rerun (Idempotent)

SET @lesson1_id = (SELECT id FROM lessons WHERE lesson_name = 'Menyapa dalam Bahasa Inggris' LIMIT 1);

INSERT INTO writing_exercises (lesson_id, instruction, writing_prompt, required_vocabulary, minimum_words, benchmark_answer, evaluation_guide)
SELECT @lesson1_id, 'Tulis perkenalan singkat.', 'Ceritakan siapa Anda, dan sapa kami dengan mengucapkan halo atau good morning.', 'hello, good morning', 15, 'Hello, good morning! My name is Raihan. I am from Indonesia and I want to learn English. Thank you.', 'Pastikan kata sapaan digunakan di awal. Periksa struktur kalimat subjek+verb.'
WHERE NOT EXISTS (SELECT 1 FROM writing_exercises WHERE lesson_id = @lesson1_id);
