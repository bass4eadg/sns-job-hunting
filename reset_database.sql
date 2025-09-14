-- 就活状況共有アプリのデータベース完全再構築
-- 既存のデータを削除して、業界別スレッド機能込みで再構築します

USE tb270457db;

-- 既存のテーブルを削除（外部キー制約があるため逆順で削除）
DROP TABLE IF EXISTS mental_records;
DROP TABLE IF EXISTS replies;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS threads;
DROP TABLE IF EXISTS users;

-- ユーザーテーブル
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- スレッド（業界別）テーブル
CREATE TABLE threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    industry VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 投稿テーブル（thread_id付き）
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 返信テーブル
CREATE TABLE replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- メンタル記録テーブル
CREATE TABLE mental_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('元気', '普通', '落ち込み') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date)
);

-- サンプルユーザーの追加
INSERT INTO users (name, password) VALUES 
('test_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password
('sample_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password

-- 業界スレッドの追加
INSERT INTO threads (name, industry, description) VALUES 
('IT・エンジニア', 'IT', 'IT業界、エンジニア職に関する情報交換'),
('金融・銀行', '金融', '銀行、証券、保険などの金融業界'),
('メーカー・製造業', 'メーカー', '自動車、電機、化学などの製造業'),
('商社・流通', '商社', '総合商社、専門商社、小売業'),
('コンサル・シンクタンク', 'コンサル', 'コンサルティング、シンクタンク'),
('マスコミ・広告', 'マスコミ', 'テレビ、新聞、広告代理店'),
('公務員・非営利', '公務員', '国家公務員、地方公務員、NPO'),
('その他・業界未定', 'その他', '上記以外の業界や業界未定の方');

-- サンプル投稿の追加（業界別スレッドに配置）
INSERT INTO posts (thread_id, user_id, content) VALUES 
(1, 1, '就活始めました！どこから手をつけていいかわからず困っています...'),
(2, 2, '面接で緊張してしまいます。皆さんはどう対策していますか？'),
(1, 2, 'プログラミングスキルはどの程度必要でしょうか？'),
(3, 1, 'メーカーの技術職志望です。おすすめの企業があれば教えてください！');

-- サンプル返信の追加
INSERT INTO replies (post_id, user_id, content) VALUES 
(1, 2, '私も最初は同じでした！まずは自己分析から始めるのがおすすめです。'),
(2, 1, '模擬面接を友達と練習すると少しずつ慣れますよ！'),
(3, 1, '基本的なアルゴリズムと、一つの言語を深く理解していれば十分だと思います。'),
(4, 2, '○○製作所の説明会に参加しましたが、とても良い雰囲気でした！');

-- サンプルメンタル記録の追加
INSERT INTO mental_records (user_id, date, status) VALUES 
(1, CURDATE(), '普通'),
(2, CURDATE(), '元気'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '元気'),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '普通');

-- 結果確認
SELECT 'Database recreation completed successfully!' as result;
SELECT 'Users:' as info; SELECT * FROM users;
SELECT 'Threads:' as info; SELECT * FROM threads;
SELECT 'Posts:' as info; SELECT p.id, t.name as thread, u.name as user, LEFT(p.content, 30) as content FROM posts p JOIN threads t ON p.thread_id = t.id JOIN users u ON p.user_id = u.id;
SELECT 'Replies:' as info; SELECT COUNT(*) as reply_count FROM replies;
SELECT 'Mental records:' as info; SELECT COUNT(*) as mental_count FROM mental_records;