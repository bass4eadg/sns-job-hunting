-- ステップ3: postsテーブルの更新
-- step2_insert_threads.sql の実行が成功してから実行してください

USE tb270457db;

-- まず現在のpostsテーブルの構造を確認
DESCRIBE posts;

-- thread_idカラムが存在しない場合のみ実行してください
-- ALTER TABLE posts ADD COLUMN thread_id INT DEFAULT 1 AFTER id;

-- 実行結果を確認
SELECT 'Check if thread_id column exists in posts table' as message;