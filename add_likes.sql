-- いいね機能のためのデータベース拡張

USE tb270457db;

-- いいねテーブルの作成
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NULL,
    reply_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_id) REFERENCES replies(id) ON DELETE CASCADE,
    -- 同じユーザーが同じ投稿/返信に複数回いいねできないようにする
    UNIQUE KEY unique_user_post (user_id, post_id),
    UNIQUE KEY unique_user_reply (user_id, reply_id),
    -- post_id か reply_id のどちらかは必須（両方NULL不可）
    CHECK ((post_id IS NOT NULL AND reply_id IS NULL) OR (post_id IS NULL AND reply_id IS NOT NULL))
);

-- サンプルいいねデータの追加
INSERT IGNORE INTO likes (user_id, post_id) VALUES 
(1, 2),  -- test_user が金融業界の投稿にいいね
(2, 1),  -- sample_user がIT業界の投稿にいいね
(1, 3),  -- test_user がIT業界の別投稿にいいね
(2, 4);  -- sample_user がメーカー業界の投稿にいいね

INSERT IGNORE INTO likes (user_id, reply_id) VALUES 
(1, 2),  -- test_user が返信にいいね
(2, 1);  -- sample_user が別の返信にいいね

-- 結果確認
SELECT 'Likes table created successfully!' as result;
SELECT 'Post likes:' as info;
SELECT l.id, u.name as user, LEFT(p.content, 30) as post_content 
FROM likes l 
JOIN users u ON l.user_id = u.id 
JOIN posts p ON l.post_id = p.id 
WHERE l.post_id IS NOT NULL;

SELECT 'Reply likes:' as info;
SELECT l.id, u.name as user, LEFT(r.content, 30) as reply_content 
FROM likes l 
JOIN users u ON l.user_id = u.id 
JOIN replies r ON l.reply_id = r.id 
WHERE l.reply_id IS NOT NULL;