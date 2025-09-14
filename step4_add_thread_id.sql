-- ステップ4: thread_idカラムの追加（必要な場合のみ）
-- step3で thread_id カラムが存在しないことを確認してから実行してください

USE tb270457db;

-- thread_idカラムの追加
ALTER TABLE posts ADD COLUMN thread_id INT DEFAULT 1 AFTER id;

-- 実行結果を確認
DESCRIBE posts;