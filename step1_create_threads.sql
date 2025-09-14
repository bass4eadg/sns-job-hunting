-- ステップ1: threadsテーブルの作成
-- このファイルを最初に実行してください

USE tb270457db;

-- スレッド（業界別）テーブルの作成
CREATE TABLE IF NOT EXISTS threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    industry VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 実行結果を確認
SELECT 'threads table created successfully' as result;