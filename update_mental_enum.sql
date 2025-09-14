-- mental_recordsテーブルのENUM値を更新

USE tb270457db;

-- 現在のテーブル構造を確認
DESCRIBE mental_records;

-- ENUMを新しい値に更新
ALTER TABLE mental_records 
MODIFY COLUMN status ENUM('とても良い', '良い', '普通', '落ち込み') NOT NULL;

-- 確認
DESCRIBE mental_records;

SELECT 'mental_records table updated successfully!' as result;