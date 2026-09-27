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
