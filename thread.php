<?php
require_once 'config.php';
requireLogin();

$db = Database::getConnection();
$error = '';
$success = '';

// スレッドIDの取得
$thread_id = intval($_GET['id'] ?? 1);

// スレッド情報の取得
try {
    $stmt = $db->prepare("SELECT * FROM threads WHERE id = ?");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch();

    if (!$thread) {
        header('Location: threads.php');
        exit();
    }
} catch (PDOException $e) {
    header('Location: threads.php');
    exit();
}

// 新規投稿の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'new_post') {
        $content = trim($_POST['content'] ?? '');

        if (empty($content)) {
            $error = '投稿内容を入力してください。';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO posts (thread_id, user_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$thread_id, $_SESSION['user_id'], $content]);
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

// 並べ替えオプションの取得
$sort = $_GET['sort'] ?? 'date_desc';
$order_clause = '';

switch ($sort) {
    case 'likes_desc':
        $order_clause = 'ORDER BY like_count DESC, p.created_at DESC';
        break;
    case 'likes_asc':
        $order_clause = 'ORDER BY like_count ASC, p.created_at DESC';
        break;
    case 'date_asc':
        $order_clause = 'ORDER BY p.created_at ASC';
        break;
    case 'date_desc':
    default:
        $order_clause = 'ORDER BY p.created_at DESC';
        break;
}

// 投稿一覧の取得（このスレッドのみ）
try {
    // likesテーブルの構造を確認して、いいね機能の有効性を判定
    $likes_available = false;
    $has_target_id = false;
    $has_target_type = false;

    try {
        $stmt = $db->prepare("SHOW TABLES LIKE 'likes'");
        $stmt->execute();
        if ($stmt->fetch()) {
            // likesテーブルが存在する場合、カラム構造を確認
            $stmt = $db->prepare("SHOW COLUMNS FROM likes LIKE 'target_id'");
            $stmt->execute();
            $has_target_id = (bool) $stmt->fetch();

            $stmt = $db->prepare("SHOW COLUMNS FROM likes LIKE 'target_type'");
            $stmt->execute();
            $has_target_type = (bool) $stmt->fetch();

            $likes_available = $has_target_id; // target_idが最低限必要
        }
    } catch (PDOException $e) {
        // likesテーブルが存在しない場合
        $likes_available = false;
    }

    if ($likes_available && $has_target_type) {
        // 完全なlikes構造がある場合
        $stmt = $db->prepare("
            SELECT p.id, p.content, p.created_at, u.name as user_name,
                   COALESCE(l.like_count, 0) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN (
                SELECT target_id, COUNT(*) as like_count
                FROM likes
                WHERE target_type = 'post'
                GROUP BY target_id
            ) l ON p.id = l.target_id
            WHERE p.thread_id = ?
            $order_clause
        ");
    } else if ($likes_available) {
        // target_idはあるがtarget_typeがない場合
        $stmt = $db->prepare("
            SELECT p.id, p.content, p.created_at, u.name as user_name,
                   COALESCE(l.like_count, 0) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN (
                SELECT target_id, COUNT(*) as like_count
                FROM likes
                GROUP BY target_id
            ) l ON p.id = l.target_id
            WHERE p.thread_id = ?
            $order_clause
        ");
    } else {
        // likesテーブルが使用できない場合、いいね機能なしで表示
        $stmt = $db->prepare("
            SELECT p.id, p.content, p.created_at, u.name as user_name,
                   0 as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.thread_id = ?
            $order_clause
        ");
    }

    $stmt->execute([$thread_id]);
    $posts = $stmt->fetchAll();

    // 各投稿の返信といいね情報も取得
    $postsWithReplies = [];
    foreach ($posts as $post) {
        // 返信を取得
        $stmt = $db->prepare("
            SELECT r.id, r.content, r.created_at, u.name as user_name
            FROM replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.post_id = ?
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([$post['id']]);
        $replies = $stmt->fetchAll();

        // 各返信のいいね情報を取得
        foreach ($replies as &$reply) {
            $reply['like_info'] = getLikeInfo($db, 'reply', $reply['id'], $_SESSION['user_id']);
        }

        $post['replies'] = $replies;
        // 投稿のいいね情報を取得
        $post['like_info'] = getLikeInfo($db, 'post', $post['id'], $_SESSION['user_id']);
        $postsWithReplies[] = $post;
    }
    $posts = $postsWithReplies;

} catch (PDOException $e) {
    $error = '投稿の取得に失敗しました。エラー: ' . $e->getMessage();
    $posts = [];
}

$pageTitle = $thread['name'] . ' - 掲示板';
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-12">
        <!-- パンくずナビ -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="threads.php">
                        <i class="bi bi-house me-1"></i>業界別掲示板
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo h($thread['name']); ?>
                </li>
            </ol>
        </nav>

        <!-- スレッドヘッダー -->
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="card-title">
                    <i class="bi bi-folder-open me-2"></i><?php echo h($thread['name']); ?>
                </h1>
                <p class="card-text text-muted mb-0">
                    <?php echo h($thread['description']); ?>
                </p>
            </div>
        </div>

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
                        <textarea class="form-control" name="content" rows="4"
                            placeholder="<?php echo h($thread['industry']); ?>業界に関する質問や情報を共有しましょう..."
                            required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>投稿する
                    </button>
                </form>
            </div>
        </div>

        <!-- 並べ替えオプション -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="bi bi-chat-left-text me-2"></i>投稿一覧
                <span class="badge bg-secondary ms-2"><?php echo count($posts); ?></span>
            </h4>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="sortDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-sort-down me-1"></i>並べ替え
                </button>
                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                    <li><a class="dropdown-item <?php echo $sort === 'date_desc' ? 'active' : ''; ?>"
                            href="?id=<?php echo $thread_id; ?>&sort=date_desc">
                            <i class="bi bi-clock me-1"></i>新しい順</a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'date_asc' ? 'active' : ''; ?>"
                            href="?id=<?php echo $thread_id; ?>&sort=date_asc">
                            <i class="bi bi-clock-history me-1"></i>古い順</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item <?php echo $sort === 'likes_desc' ? 'active' : ''; ?>"
                            href="?id=<?php echo $thread_id; ?>&sort=likes_desc">
                            <i class="bi bi-heart-fill me-1"></i>いいね数が多い順</a></li>
                    <li><a class="dropdown-item <?php echo $sort === 'likes_asc' ? 'active' : ''; ?>"
                            href="?id=<?php echo $thread_id; ?>&sort=likes_asc">
                            <i class="bi bi-heart me-1"></i>いいね数が少ない順</a></li>
                </ul>
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
                            <span class="badge bg-primary ms-2">
                                <i class="bi bi-heart-fill me-1"></i><?php echo $post['like_count']; ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i><?php echo date('Y/m/d H:i', strtotime($post['created_at'])); ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(h($post['content'])); ?></p>

                        <!-- 投稿のいいねボタン -->
                        <div class="mb-3">
                            <?php echo renderLikeButton('post', $post['id'], $post['like_info']); ?>
                        </div>

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
                                                <!-- 返信のいいねボタン -->
                                                <div class="mt-2">
                                                    <?php echo renderLikeButton('reply', $reply['id'], $reply['like_info']); ?>
                                                </div>
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