<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不正なリクエスト']);
    exit();
}

$type = $_POST['type'] ?? '';
$id = intval($_POST['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!in_array($type, ['post', 'reply']) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => '無効なパラメータ']);
    exit();
}

try {
    $db = Database::getConnection();
    $db->beginTransaction();

    // 現在のいいね状態をチェック
    if ($type === 'post') {
        $stmt = $db->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $id]);
    } else {
        $stmt = $db->prepare("SELECT id FROM likes WHERE user_id = ? AND reply_id = ?");
        $stmt->execute([$user_id, $id]);
    }

    $existing_like = $stmt->fetch();
    $is_liked = false;

    if ($existing_like) {
        // いいねを削除
        $stmt = $db->prepare("DELETE FROM likes WHERE id = ?");
        $stmt->execute([$existing_like['id']]);
        $is_liked = false;
    } else {
        // いいねを追加
        if ($type === 'post') {
            $stmt = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO likes (user_id, reply_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $id]);
        }
        $is_liked = true;
    }

    // いいね数を取得
    if ($type === 'post') {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE reply_id = ?");
        $stmt->execute([$id]);
    }

    $result = $stmt->fetch();
    $like_count = $result['count'];

    $db->commit();

    echo json_encode([
        'success' => true,
        'is_liked' => $is_liked,
        'like_count' => $like_count
    ]);

} catch (PDOException $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'データベースエラー']);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'サーバーエラー']);
}
?>