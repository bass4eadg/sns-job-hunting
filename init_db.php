<?php
require_once 'config.php';

try {
    $db = Database::getConnection();

    echo "データベースの初期化を開始します...\n";

    // threadsテーブルの作成
    $sql = "
    CREATE TABLE IF NOT EXISTS threads (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        industry VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "threadsテーブルを作成しました。\n";

    // likesテーブルの作成
    $sql = "
    CREATE TABLE IF NOT EXISTS likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        target_id INT NOT NULL,
        target_type ENUM('post', 'reply') NOT NULL DEFAULT 'post',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (user_id, target_id, target_type),
        INDEX idx_target (target_id, target_type)
    )";
    $db->exec($sql);
    echo "likesテーブルを作成しました。\n";

    // postsテーブルの作成
    $sql = "
    CREATE TABLE IF NOT EXISTS posts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        thread_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_thread (thread_id),
        INDEX idx_user (user_id)
    )";
    $db->exec($sql);
    echo "postsテーブルを作成しました。\n";

    // repliesテーブルの作成
    $sql = "
    CREATE TABLE IF NOT EXISTS replies (
        id INT PRIMARY KEY AUTO_INCREMENT,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_post (post_id),
        INDEX idx_user (user_id)
    )";
    $db->exec($sql);
    echo "repliesテーブルを作成しました。\n";

    // 初期データの投入
    $stmt = $db->prepare("SELECT COUNT(*) FROM threads");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $threads_data = [
            [1, 'IT・Web業界', 'IT', 'プログラマー、エンジニア、デザイナーなどIT関連の情報交換'],
            [2, '金融業界', '金融', '銀行、証券、保険業界の就活情報'],
            [3, '製造業', '製造', '自動車、電機、化学などの製造業界'],
            [4, '商社・貿易', '商社', '総合商社、専門商社の就活情報'],
            [5, 'コンサルティング', 'コンサル', '戦略、IT、人事コンサルなど'],
            [6, '医療・製薬', '医療', '病院、製薬会社、医療機器メーカー']
        ];

        $stmt = $db->prepare("INSERT INTO threads (id, name, industry, description) VALUES (?, ?, ?, ?)");
        foreach ($threads_data as $thread) {
            $stmt->execute($thread);
        }
        echo "初期スレッドデータを投入しました。\n";
    } else {
        echo "スレッドデータは既に存在します。\n";
    }

    echo "データベースの初期化が完了しました。\n";

} catch (Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage() . "\n";
}
?>