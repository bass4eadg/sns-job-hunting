<?php
require_once 'config.php';

startSession();

// 既にログインしている場合は業界別掲示板にリダイレクト
if (isLoggedIn()) {
    header('Location: threads.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'ユーザー名またはパスワードが入力されていません。';
    } else {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id, name, password FROM users WHERE name = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                setFlashMessage('ログインしました。', 'success');
                header('Location: threads.php');
                exit();
            } else {
                $error = 'ユーザー名またはパスワードが正しくありません。';
            }
        } catch (PDOException $e) {
            $error = 'ログイン処理でエラーが発生しました。';
        }
    }
}

$pageTitle = 'ログイン';
?>

<?php include 'header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body">
                <h1 class="card-title text-center mb-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i>ログイン
                </h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <?php echo h($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">ユーザー名</label>
                        <input type="text" class="form-control" id="username" name="username"
                            value="<?php echo h($_POST['username'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i>ログイン
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-2">
                        <small class="text-muted">まだアカウントをお持ちでない方は</small>
                    </p>
                    <a href="register.php" class="btn btn-outline-primary btn-sm mb-3">
                        <i class="bi bi-person-plus me-1"></i>新規ユーザー登録
                    </a>

                    <div class="border-top pt-3">
                        <small class="text-muted">
                            <strong>テスト用アカウント:</strong><br>
                            ユーザー名: test_user / パスワード: password<br>
                            ユーザー名: sample_user / パスワード: password
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>