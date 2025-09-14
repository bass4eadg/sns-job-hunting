-- =============================================
-- 就活アプリ 最小限のデータベース初期化SQL
-- 既存のテーブル構造を壊さずに必要なデータのみ追加
-- =============================================

-- threadsテーブルの作成（既存の場合はスキップ）
CREATE TABLE IF NOT EXISTS threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- likesテーブルの作成（既存の場合はスキップ）
-- 既存のlikesテーブルがある場合は、その構造を尊重します
-- CREATE TABLE IF NOT EXISTS likes (
--     id INT PRIMARY KEY AUTO_INCREMENT,
--     user_id INT NOT NULL,
--     target_id INT NOT NULL,
--     target_type ENUM('post', 'reply') NOT NULL DEFAULT 'post',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     UNIQUE KEY unique_like (user_id, target_id, target_type),
--     INDEX idx_target (target_id, target_type)
-- );

-- 注意: 既存のlikesテーブルがある場合は、コメントアウトを解除する前に
-- 現在の構造を確認してください: DESCRIBE likes;

-- postsテーブルの作成（既存の場合はスキップ）
CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_thread (thread_id),
    INDEX idx_user (user_id)
);

-- repliesテーブルの作成（既存の場合はスキップ）
CREATE TABLE IF NOT EXISTS replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
);

-- 初期スレッドデータの投入（重複回避）
INSERT IGNORE INTO threads (id, name, industry, description) VALUES
(1, 'IT・Web業界', 'IT', 'プログラマー、エンジニア、デザイナーなどIT関連の情報交換'),
(2, '金融業界', '金融', '銀行、証券、保険業界の就活情報'),
(3, '製造業', '製造', '自動車、電機、化学などの製造業界'),
(4, '商社・貿易', '商社', '総合商社、専門商社の就活情報'),
(5, 'コンサルティング', 'コンサル', '戦略、IT、人事コンサルなど'),
(6, '医療・製薬', '医療', '病院、製薬会社、医療機器メーカー');

-- 注意: usersテーブルについて
-- 既存のusersテーブルの構造が不明なため、手動でテスト用ユーザーを作成してください
-- 例: INSERT INTO users (name, password) VALUES ('テストユーザー', 'password_hash');

-- 初期化完了メッセージ
-- このSQLファイルは既存のテーブル構造を壊すことなく、掲示板機能に必要な基本テーブルを作成します