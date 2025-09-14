<?php
require_once 'config.php';
requireLogin();

$db = Database::getConnection();

try {
    // テーブルの存在確認
    $stmt = $db->prepare("SHOW TABLES LIKE 'threads'");
    $stmt->execute();
    $table_exists = $stmt->fetch();

    if (!$table_exists) {
        throw new Exception("threadsテーブルが存在しません");
    }

    // まずシンプルにスレッド一覧を取得
    $stmt = $db->prepare("SELECT id, name, industry, description FROM threads ORDER BY id ASC");
    $stmt->execute();
    $basic_threads = $stmt->fetchAll();

    $threads = [];
    foreach ($basic_threads as $thread) {
        // 各スレッドの投稿数を取得
        $stmt = $db->prepare("SELECT COUNT(*) as post_count FROM posts WHERE thread_id = ?");
        $stmt->execute([$thread['id']]);
        $post_count = $stmt->fetchColumn();

        // 最新投稿情報を取得
        $stmt = $db->prepare("
            SELECT p.created_at, u.name as user_name 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.thread_id = ? 
            ORDER BY p.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$thread['id']]);
        $latest_post = $stmt->fetch();

        // いいね総数を取得（likesテーブルが存在しない場合は0）
        $total_likes = 0;
        try {
            // まずlikesテーブルの構造を確認
            $stmt = $db->prepare("SHOW COLUMNS FROM likes LIKE 'target_type'");
            $stmt->execute();
            $has_target_type = $stmt->fetch();

            if ($has_target_type) {
                // target_typeカラムがある場合
                $stmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM likes l 
                    JOIN posts p ON l.target_id = p.id 
                    WHERE l.target_type = 'post' AND p.thread_id = ?
                ");
                $stmt->execute([$thread['id']]);
                $total_likes = $stmt->fetchColumn();
            } else {
                // target_typeカラムがない場合、すべてのlikesを投稿として扱う
                $stmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM likes l 
                    JOIN posts p ON l.target_id = p.id 
                    WHERE p.thread_id = ?
                ");
                $stmt->execute([$thread['id']]);
                $total_likes = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            // likesテーブルが存在しない場合は0
            $total_likes = 0;
        }

        $threads[] = [
            'id' => $thread['id'],
            'name' => $thread['name'],
            'industry' => $thread['industry'],
            'description' => $thread['description'],
            'post_count' => $post_count,
            'latest_post_at' => $latest_post ? $latest_post['created_at'] : null,
            'latest_user' => $latest_post ? $latest_post['user_name'] : null,
            'total_likes' => $total_likes
        ];
    }

} catch (Exception $e) {
    $threads = [];
    $error = 'スレッド一覧の取得に失敗しました。エラー: ' . $e->getMessage();
}

$pageTitle = '業界別掲示板';
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="bi bi-chat-dots me-2"></i>業界別掲示板
        </h1>

        <p class="text-muted mb-4">
            業界ごとに分かれた掲示板で、より専門的な情報交換ができます。
        </p>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($threads as $thread): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-folder me-1"></i><?php echo h($thread['name']); ?>
                            </h5>
                            <div>
                                <span class="badge bg-primary rounded-pill me-1"><?php echo $thread['post_count']; ?></span>
                                <span class="badge bg-danger rounded-pill">
                                    <i class="bi bi-heart-fill me-1"></i><?php echo $thread['total_likes']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted small mb-3">
                                <?php echo h($thread['description']); ?>
                            </p>

                            <?php if ($thread['latest_post_at']): ?>
                                <div class="small text-muted mb-3">
                                    <i class="bi bi-clock me-1"></i>
                                    最新投稿: <?php echo date('m/d H:i', strtotime($thread['latest_post_at'])); ?>
                                    <?php if ($thread['latest_user']): ?>
                                        by <?php echo h($thread['latest_user']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="small text-muted mb-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    まだ投稿がありません
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="thread.php?id=<?php echo $thread['id']; ?>" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-arrow-right-circle me-1"></i>スレッドを見る
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($threads)): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-1"></i>
                スレッドが見つかりませんでした。
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>