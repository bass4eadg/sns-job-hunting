-- =============================================
-- 就活アプリ データベース初期化SQL
-- =============================================

-- threadsテーブルの作成
CREATE TABLE IF NOT EXISTS threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- likesテーブルの作成
-- 注意: 既存のlikesテーブルがある場合は、この部分をコメントアウトしてください
-- 既存テーブル構造確認: DESCRIBE likes;
CREATE TABLE IF NOT EXISTS likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    target_id INT NOT NULL,
    target_type ENUM('post', 'reply') NOT NULL DEFAULT 'post',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, target_id, target_type),
    INDEX idx_target (target_id, target_type)
);

-- postsテーブルの作成
CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_thread (thread_id),
    INDEX idx_user (user_id)
);

-- repliesテーブルの作成
CREATE TABLE IF NOT EXISTS replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post (post_id),
    INDEX idx_user (user_id)
);

-- usersテーブルの作成（他の場所で作成されていない場合）
-- 既存のテーブル構造に合わせて、emailカラムがない場合に対応
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- emailカラムが存在しない場合の対応（必要に応じて追加）
-- ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE AFTER name;

-- mental_recordsテーブルの作成（メンタル記録機能用）
CREATE TABLE IF NOT EXISTS mental_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    status ENUM('とても良い', '良い', '普通', '落ち込み') NOT NULL,
    comment TEXT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date),
    INDEX idx_user (user_id)
);

-- company_statusテーブルの作成（企業進捗管理用）
CREATE TABLE IF NOT EXISTS company_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    position VARCHAR(255),
    status ENUM('興味あり', '応募検討', 'ES提出', '書類選考', '一次面接', '二次面接', '三次面接', '最終面接', '内定', '内定承諾', '選考辞退', '不合格') NOT NULL DEFAULT '興味あり',
    notes TEXT,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- todosテーブルの作成（TODO機能用）
CREATE TABLE IF NOT EXISTS todos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('低', '中', '高') NOT NULL DEFAULT '中',
    status ENUM('未完了', '完了') NOT NULL DEFAULT '未完了',
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
);

-- 初期スレッドデータの投入
INSERT IGNORE INTO threads (id, name, industry, description) VALUES
(1, 'IT・Web業界', 'IT', 'プログラマー、エンジニア、デザイナーなどIT関連の情報交換'),
(2, '金融業界', '金融', '銀行、証券、保険業界の就活情報'),
(3, '製造業', '製造', '自動車、電機、化学などの製造業界'),
(4, '商社・貿易', '商社', '総合商社、専門商社の就活情報'),
(5, 'コンサルティング', 'コンサル', '戦略、IT、人事コンサルなど'),
(6, '医療・製薬', '医療', '病院、製薬会社、医療機器メーカー');

-- サンプルユーザーデータ（テスト用）
-- 既存のusersテーブル構造に合わせてemailカラムを除外
INSERT IGNORE INTO users (id, name, password) VALUES
(1, 'テストユーザー1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'テストユーザー2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- サンプル投稿データ
INSERT IGNORE INTO posts (id, thread_id, user_id, content, created_at) VALUES
(1, 1, 1, 'IT業界の就活について質問があります。プログラミング経験は必須でしょうか？', '2024-01-15 10:30:00'),
(2, 1, 2, 'プログラミング経験があると有利ですが、必須ではない企業も多いですよ。', '2024-01-15 11:15:00'),
(3, 2, 1, '金融業界のインターンシップ情報を教えてください。', '2024-01-16 14:20:00');

-- サンプル返信データ
INSERT IGNORE INTO replies (id, post_id, user_id, content, created_at) VALUES
(1, 1, 2, '文系でもITコンサルなどの職種があります！', '2024-01-15 12:00:00'),
(2, 2, 1, 'ありがとうございます！参考になりました。', '2024-01-15 12:30:00');

-- サンプルいいねデータ
-- 注意: 既存のlikesテーブル構造に合わせて調整が必要です
-- まず既存の構造を確認してください: DESCRIBE likes;
-- 
-- 既存のlikesテーブルが異なる構造の場合は、以下をコメントアウトしてください
-- INSERT IGNORE INTO likes (id, user_id, target_id, target_type, created_at) VALUES
-- (1, 1, 2, 'post', '2024-01-15 13:00:00'),
-- (2, 2, 1, 'post', '2024-01-15 14:00:00'),
-- (3, 1, 1, 'reply', '2024-01-15 15:00:00');

-- 初期化完了メッセージ用のコメント
-- 上記のSQLを実行すると、就活アプリに必要な全てのテーブルとサンプルデータが作成されます。