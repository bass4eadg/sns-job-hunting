-- メンタル記録のサンプルデータ追加

USE tb270457db;

-- 既存のサンプルデータをクリア
DELETE FROM mental_records WHERE user_id IN (1, 2);

-- 過去30日分のサンプルデータを追加
-- ユーザー1のデータ（test_user）
INSERT INTO mental_records (user_id, date, status) VALUES 
(1, CURDATE(), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '落ち込み'),
(1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 8 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 9 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 10 DAY), '落ち込み'),
(1, DATE_SUB(CURDATE(), INTERVAL 11 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 16 DAY), '落ち込み'),
(1, DATE_SUB(CURDATE(), INTERVAL 17 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 18 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 19 DAY), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 21 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 22 DAY), '落ち込み'),
(1, DATE_SUB(CURDATE(), INTERVAL 23 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 25 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 26 DAY), '普通'),
(1, DATE_SUB(CURDATE(), INTERVAL 27 DAY), '良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 28 DAY), 'とても良い'),
(1, DATE_SUB(CURDATE(), INTERVAL 29 DAY), '落ち込み');

-- ユーザー2のデータ（sample_user）
INSERT INTO mental_records (user_id, date, status) VALUES 
(2, CURDATE(), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'とても良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'とても良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 8 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 9 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 10 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 11 DAY), 'とても良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 12 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 13 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 15 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 16 DAY), 'とても良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 17 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 18 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 19 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'とても良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 21 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 22 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 23 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'とても良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 25 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 26 DAY), '良い'),
(2, DATE_SUB(CURDATE(), INTERVAL 27 DAY), '落ち込み'),
(2, DATE_SUB(CURDATE(), INTERVAL 28 DAY), '普通'),
(2, DATE_SUB(CURDATE(), INTERVAL 29 DAY), 'とても良い');

-- 確認
SELECT 'Mental records sample data inserted successfully!' as result;

SELECT 'Sample data count:' as info;
SELECT 
    u.name,
    COUNT(mr.id) as record_count,
    DATE(MIN(mr.date)) as oldest_record,
    DATE(MAX(mr.date)) as newest_record
FROM users u
LEFT JOIN mental_records mr ON u.id = mr.user_id
WHERE u.id IN (1, 2)
GROUP BY u.id, u.name;

SELECT 'Status distribution:' as info;
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mental_records WHERE user_id IN (1, 2)), 1) as percentage
FROM mental_records 
WHERE user_id IN (1, 2)
GROUP BY status
ORDER BY count DESC;