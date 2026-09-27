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
