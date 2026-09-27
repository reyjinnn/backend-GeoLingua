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
