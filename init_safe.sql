-- =============================================
-- 段階的データベース構築SQL
-- 既存テーブルとの競合を避けて安全に実行
-- =============================================

-- ステップ1: 基本テーブル作成（競合が少ないもの）
-- threadsテーブル
CREATE TABLE IF NOT EXISTS threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- postsテーブル
CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_thread (thread_id),
    INDEX idx_user (user_id)
);

-- repliesテーブル
CREATE TABLE IF NOT EXISTS replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
);

-- ステップ2: 初期データ投入（安全）
INSERT IGNORE INTO threads (id, name, industry, description) VALUES
(1, 'IT・Web業界', 'IT', 'プログラマー、エンジニア、デザイナーなどIT関連の情報交換'),
(2, '金融業界', '金融', '銀行、証券、保険業界の就活情報'),
(3, '製造業', '製造', '自動車、電機、化学などの製造業界'),
(4, '商社・貿易', '商社', '総合商社、専門商社の就活情報'),
(5, 'コンサルティング', 'コンサル', '戦略、IT、人事コンサルなど'),
(6, '医療・製薬', '医療', '病院、製薬会社、医療機器メーカー');

-- =============================================
-- 既存テーブル構造確認用クエリ
-- 以下を実行して既存テーブルの構造を確認してください
-- =============================================

-- SHOW TABLES;
-- DESCRIBE users;
-- DESCRIBE likes;

-- =============================================
-- テーブル別の条件付き作成・データ投入
-- 必要に応じて実行してください
-- =============================================

-- usersテーブル（既存の場合はスキップ）
-- CREATE TABLE IF NOT EXISTS users (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     name VARCHAR(255) NOT NULL,
--     password VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- テストユーザー（既存usersテーブル構造に応じて調整）
-- INSERT IGNORE INTO users (name, password) VALUES 
-- ('テストユーザー1', 'test123'),
-- ('テストユーザー2', 'test456');

-- likesテーブル（新規作成の場合のみ）
-- CREATE TABLE IF NOT EXISTS likes (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     user_id INT NOT NULL,
--     target_id INT NOT NULL,
--     target_type ENUM('post', 'reply') NOT NULL DEFAULT 'post',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     UNIQUE KEY unique_like (user_id, target_id, target_type),
--     INDEX idx_target (target_id, target_type)
-- );

-- サンプル投稿（usersテーブルにテストユーザーがある場合のみ）
-- INSERT IGNORE INTO posts (thread_id, user_id, content, created_at) VALUES
-- (1, 1, 'IT業界の就活について質問があります。プログラミング経験は必須でしょうか？', '2024-01-15 10:30:00'),
-- (1, 2, 'プログラミング経験があると有利ですが、必須ではない企業も多いですよ。', '2024-01-15 11:15:00'),
-- (2, 1, '金融業界のインターンシップ情報を教えてください。', '2024-01-16 14:20:00');

-- =============================================
-- 実行後の確認方法
-- =============================================
-- SELECT * FROM threads;
-- SELECT COUNT(*) FROM posts;
-- SELECT COUNT(*) FROM users;