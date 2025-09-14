-- =============================================
-- 掲示板機能 基本テーブルのみ作成SQL
-- 既存のテーブルには一切手を加えません
-- =============================================

-- threadsテーブルの作成（掲示板の基本）
CREATE TABLE IF NOT EXISTS threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- postsテーブルの作成（投稿機能）
CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_thread (thread_id),
    INDEX idx_user (user_id)
);

-- repliesテーブルの作成（返信機能）
CREATE TABLE IF NOT EXISTS replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
);

-- 業界別スレッドの初期データ
INSERT IGNORE INTO threads (id, name, industry, description) VALUES
(1, 'IT・Web業界', 'IT', 'プログラマー、エンジニア、デザイナーなどIT関連の情報交換'),
(2, '金融業界', '金融', '銀行、証券、保険業界の就活情報'),
(3, '製造業', '製造', '自動車、電機、化学などの製造業界'),
(4, '商社・貿易', '商社', '総合商社、専門商社の就活情報'),
(5, 'コンサルティング', 'コンサル', '戦略、IT、人事コンサルなど'),
(6, '医療・製薬', '医療', '病院、製薬会社、医療機器メーカー');

-- =============================================
-- 実行後の確認方法
-- =============================================
-- 1. テーブル一覧確認: SHOW TABLES;
-- 2. スレッド確認: SELECT * FROM threads;
-- 3. 既存usersテーブル確認: DESCRIBE users;

-- =============================================
-- テストユーザー作成例（手動実行）
-- =============================================
-- 既存のusersテーブル構造に応じて調整してください
-- INSERT INTO users (name, password) VALUES ('テストユーザー', 'test123');

-- =============================================
-- 注意事項
-- =============================================
-- このSQLは以下のテーブルのみ作成します：
-- - threads（スレッド）
-- - posts（投稿）  
-- - replies（返信）
-- 
-- likes、users等の既存テーブルには一切触れません