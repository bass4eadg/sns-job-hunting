-- ステップ5: 外部キー制約の追加
-- step4が成功してから実行してください

USE tb270457db;

-- 外部キー制約の追加
ALTER TABLE posts ADD FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE;

-- 既存の投稿にthread_idを設定
UPDATE posts SET thread_id = 1 WHERE id = 1;  -- IT業界
UPDATE posts SET thread_id = 2 WHERE id = 2;  -- 金融業界

-- 実行結果を確認
SELECT p.id, p.thread_id, t.name as thread_name, LEFT(p.content, 50) as content_preview 
FROM posts p 
LEFT JOIN threads t ON p.thread_id = t.id;