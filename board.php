<?php
require_once 'config.php';
requireLogin();

$db = Database::getConnection();
$error = '';
$success = '';

// 新規投稿の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'new_post') {
        $content = trim($_POST['content'] ?? '');

        if (empty($content)) {
            $error = '投稿内容を入力してください。';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $content]);
                $success = '投稿しました。';
            } catch (PDOException $e) {
                $error = '投稿に失敗しました。';
            }
        }
    } elseif ($action === 'reply') {
        $post_id = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($post_id <= 0) {
            $error = '不正な投稿IDです。';
        } elseif (empty($content)) {
            $error = '返信内容を入力してください。';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO replies (post_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
                $success = '返信しました。';
            } catch (PDOException $e) {
                $error = '返信に失敗しました。';
            }
        }
    }
}

// 投稿一覧の取得
try {
    $stmt = $db->prepare("
        SELECT p.id, p.content, p.created_at, u.name as user_name
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll();

    // 各投稿の返信も取得
    $postsWithReplies = [];
    foreach ($posts as $post) {
        $stmt = $db->prepare("
            SELECT r.id, r.content, r.created_at, u.name as user_name
            FROM replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.post_id = ?
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([$post['id']]);
        $replies = $stmt->fetchAll();

        $post['replies'] = $replies;
        $postsWithReplies[] = $post;
    }
    $posts = $postsWithReplies;

} catch (PDOException $e) {
    $error = '投稿の取得に失敗しました。';
    $posts = [];
}

$pageTitle = '掲示板';
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="bi bi-chat-dots me-2"></i>掲示板
        </h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-1"></i>
                <?php echo h($success); ?>
            </div>
        <?php endif; ?>

        <!-- 新規投稿フォーム -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil-square me-1"></i>新しい投稿
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="new_post">
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="4" placeholder="就活に関する質問や情報を共有しましょう..."
                            required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>投稿する
                    </button>
                </form>
            </div>
        </div>

        <!-- 投稿一覧 -->
        <?php if (empty($posts)): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-1"></i>
                まだ投稿がありません。最初の投稿をしてみましょう！
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><i class="bi bi-person-circle me-1"></i><?php echo h($post['user_name']); ?></strong>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i><?php echo date('Y/m/d H:i', strtotime($post['created_at'])); ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(h($post['content'])); ?></p>

                        <!-- 返信一覧 -->
                        <?php if (!empty($post['replies'])): ?>
                            <div class="mt-3">
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-chat-left-text me-1"></i>返信 (<?php echo count($post['replies']); ?>)
                                </h6>
                                <?php foreach ($post['replies'] as $reply): ?>
                                    <div class="border-start border-3 border-light ps-3 mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <small class="fw-bold text-primary">
                                                    <i class="bi bi-person-circle me-1"></i><?php echo h($reply['user_name']); ?>
                                                </small>
                                                <p class="mb-1 mt-1"><?php echo nl2br(h($reply['content'])); ?></p>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('m/d H:i', strtotime($reply['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 返信フォーム -->
                        <div class="mt-3">
                            <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse"
                                data-bs-target="#reply-form-<?php echo $post['id']; ?>">
                                <i class="bi bi-reply me-1"></i>返信する
                            </button>

                            <div class="collapse mt-3" id="reply-form-<?php echo $post['id']; ?>">
                                <form method="POST">
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <div class="mb-3">
                                        <textarea class="form-control" name="content" rows="3" placeholder="返信を入力してください..."
                                            required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send me-1"></i>返信する
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse"
                                        data-bs-target="#reply-form-<?php echo $post['id']; ?>">
                                        キャンセル
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>