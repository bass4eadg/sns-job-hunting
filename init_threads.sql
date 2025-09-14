-- スレッドテーブルが存在しない場合の初期化用SQL

-- threadsテーブルの作成
CREATE TABLE IF NOT EXISTS threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 初期データの投入（存在しない場合のみ）
INSERT IGNORE INTO threads (id, name, industry, description) VALUES
(1, 'IT・Web業界', 'IT', 'プログラマー、エンジニア、デザイナーなどIT関連の情報交換'),
(2, '金融業界', '金融', '銀行、証券、保険業界の就活情報'),
(3, '製造業', '製造', '自動車、電機、化学などの製造業界'),
(4, '商社・貿易', '商社', '総合商社、専門商社の就活情報'),
(5, 'コンサルティング', 'コンサル', '戦略、IT、人事コンサルなど'),
(6, '医療・製薬', '医療', '病院、製薬会社、医療機器メーカー');