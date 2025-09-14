-- 業界別スレッド機能のためのデータベース拡張

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

-- サンプル業界スレッドの追加（外部キー制約の前に実行）
INSERT INTO threads (name, industry, description) VALUES 
('IT・エンジニア', 'IT', 'IT業界、エンジニア職に関する情報交換'),
('金融・銀行', '金融', '銀行、証券、保険などの金融業界'),
('メーカー・製造業', 'メーカー', '自動車、電機、化学などの製造業'),
('商社・流通', '商社', '総合商社、専門商社、小売業'),
('コンサル・シンクタンク', 'コンサル', 'コンサルティング、シンクタンク'),
('マスコミ・広告', 'マスコミ', 'テレビ、新聞、広告代理店'),
('公務員・非営利', '公務員', '国家公務員、地方公務員、NPO'),
('その他・業界未定', 'その他', '上記以外の業界や業界未定の方');

-- 既存のpostsテーブルにthread_idカラムを追加
ALTER TABLE posts ADD COLUMN thread_id INT DEFAULT 1 AFTER id;

-- 外部キー制約を追加（threadsテーブルにデータが入った後）
ALTER TABLE posts ADD FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE;

-- 既存の投稿を適当なスレッドに割り当て（サンプルとして）
UPDATE posts SET thread_id = 1 WHERE id = 1;  -- IT業界
UPDATE posts SET thread_id = 2 WHERE id = 2;  -- 金融業界