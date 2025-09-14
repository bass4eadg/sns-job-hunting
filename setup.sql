-- 就活状況共有アプリのデータベース設計

-- 既存のデータベースを使用
USE tb270457db;

-- ユーザーテーブル
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 投稿テーブル
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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

-- サンプル投稿の追加
INSERT INTO posts (user_id, content) VALUES 
(1, '就活始めました！どこから手をつけていいかわからず困っています...'),
(2, '面接で緊張してしまいます。皆さんはどう対策していますか？');

-- サンプル返信の追加
INSERT INTO replies (post_id, user_id, content) VALUES 
(1, 2, '私も最初は同じでした！まずは自己分析から始めるのがおすすめです。'),
(2, 1, '模擬面接を友達と練習すると少しずつ慣れますよ！');

-- サンプルメンタル記録の追加
INSERT INTO mental_records (user_id, date, status) VALUES 
(1, CURDATE(), '普通'),
(2, CURDATE(), '元気'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '普通');