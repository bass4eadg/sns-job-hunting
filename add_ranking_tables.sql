-- ランキング機能のためのデータベーステーブル追加

USE tb270457db;

-- 企業管理テーブル（チェックシート機能用）
CREATE TABLE IF NOT EXISTS company_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    progress_status ENUM('興味あり', '応募準備中', '応募済み', '書類選考中', '書類通過', '面接準備中', '1次面接', '2次面接', '最終面接', '内定', '不採用', '辞退') NOT NULL DEFAULT '興味あり',
    notes TEXT,
    applied_date DATE NULL,
    interview_date DATE NULL,
    result_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_company (user_id, company_name)
);

-- TODOリストテーブル
CREATE TABLE IF NOT EXISTS todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    status ENUM('未完了', '完了') NOT NULL DEFAULT '未完了',
    priority ENUM('低', '中', '高') NOT NULL DEFAULT '中',
    due_date DATE NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- サンプルデータ：企業管理
INSERT IGNORE INTO company_status (user_id, company_name, progress_status, applied_date, notes) VALUES 
(1, 'ABC株式会社', '応募済み', '2025-09-10', 'IT企業、エンジニア職'),
(1, 'XYZ銀行', '書類選考中', '2025-09-08', '金融業界志望'),
(1, 'DEF製作所', '面接準備中', '2025-09-05', 'メーカー、技術職'),
(1, 'GHI商事', '興味あり', NULL, '商社、総合職検討中'),
(2, 'JKL IT Solutions', '応募済み', '2025-09-12', 'スタートアップ企業'),
(2, 'MNO証券', '書類通過', '2025-09-06', '1次面接予定'),
(2, 'PQR自動車', '応募準備中', NULL, '自動車業界'),
(2, 'STU広告', '内定', '2025-08-25', '内定獲得！');

-- サンプルデータ：TODOリスト
INSERT IGNORE INTO todos (user_id, content, status, priority, due_date) VALUES 
(1, 'ES（エントリーシート）をABC株式会社に提出', '完了', '高', '2025-09-10'),
(1, '面接対策の準備（自己PR練習）', '未完了', '高', '2025-09-15'),
(1, '業界研究：金融業界について調べる', '未完了', '中', '2025-09-20'),
(1, 'SPI対策問題集を進める', '未完了', '中', '2025-09-25'),
(2, '履歴書の写真を撮影する', '完了', '中', '2025-09-05'),
(2, 'MNO証券の1次面接準備', '未完了', '高', '2025-09-16'),
(2, '企業研究：PQR自動車について調べる', '未完了', '低', '2025-09-30'),
(2, 'ありがとうメール送信（STU広告）', '完了', '低', '2025-08-26');

-- 結果確認
SELECT 'Tables created successfully!' as result;

SELECT 'Company Status Sample:' as info;
SELECT u.name, cs.company_name, cs.progress_status, cs.applied_date 
FROM company_status cs 
JOIN users u ON cs.user_id = u.id 
ORDER BY u.name, cs.created_at;

SELECT 'TODO Sample:' as info;
SELECT u.name, LEFT(t.content, 40) as todo, t.status, t.priority
FROM todos t 
JOIN users u ON t.user_id = u.id 
ORDER BY u.name, t.created_at;