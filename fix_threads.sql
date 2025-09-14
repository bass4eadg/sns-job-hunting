-- 業界別スレッド機能のためのデータベース拡張（エラー対応版）

USE tb270457db;

-- スレッド（業界別）テーブルの作成（既に存在する場合はスキップ）
CREATE TABLE IF NOT EXISTS threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    industry VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- threadsテーブルが空の場合のみサンプルデータを挿入
INSERT IGNORE INTO threads (id, name, industry, description) VALUES 
(1, 'IT・エンジニア', 'IT', 'IT業界、エンジニア職に関する情報交換'),
(2, '金融・銀行', '金融', '銀行、証券、保険などの金融業界'),
(3, 'メーカー・製造業', 'メーカー', '自動車、電機、化学などの製造業'),
(4, '商社・流通', '商社', '総合商社、専門商社、小売業'),
(5, 'コンサル・シンクタンク', 'コンサル', 'コンサルティング、シンクタンク'),
(6, 'マスコミ・広告', 'マスコミ', 'テレビ、新聞、広告代理店'),
(7, '公務員・非営利', '公務員', '国家公務員、地方公務員、NPO'),
(8, 'その他・業界未定', 'その他', '上記以外の業界や業界未定の方');

-- postsテーブルにthread_idカラムが存在するかチェックし、存在しない場合のみ追加
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = 'tb270457db' 
AND table_name = 'posts' 
AND column_name = 'thread_id';

-- カラムが存在しない場合のみ追加
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE posts ADD COLUMN thread_id INT DEFAULT 1 AFTER id', 
    'SELECT "thread_id column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 外部キー制約が存在するかチェック
SET @fk_exists = 0;
SELECT COUNT(*) INTO @fk_exists
FROM information_schema.table_constraints
WHERE table_schema = 'tb270457db'
AND table_name = 'posts'
AND constraint_type = 'FOREIGN KEY'
AND constraint_name LIKE '%thread%';

-- 外部キー制約が存在しない場合のみ追加
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE posts ADD FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE',
    'SELECT "Foreign key constraint already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 既存の投稿にthread_idを設定（まだ設定されていない場合）
UPDATE posts SET thread_id = 1 WHERE id = 1 AND thread_id IS NULL;  -- IT業界
UPDATE posts SET thread_id = 2 WHERE id = 2 AND thread_id IS NULL;  -- 金融業界

-- 現在の状態を確認
SELECT 'Current threads:' as info;
SELECT * FROM threads;

SELECT 'Current posts with thread_id:' as info;
SELECT id, thread_id, LEFT(content, 50) as content_preview FROM posts;