<?php
require_once 'config.php';

startSession();

// 既にログインしている場合は業界別掲示板にリダイレクト
if (isLoggedIn()) {
    header('Location: threads.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // バリデーション
    if (empty($username)) {
        $error = 'ユーザー名を入力してください。';
    } elseif (strlen($username) < 3) {
        $error = 'ユーザー名は3文字以上で入力してください。';
    } elseif (strlen($username) > 50) {
        $error = 'ユーザー名は50文字以内で入力してください。';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $error = 'ユーザー名は英数字、アンダースコア、ハイフンのみ使用できます。';
    } elseif (empty($password)) {
        $error = 'パスワードを入力してください。';
    } elseif (strlen($password) < 6) {
        $error = 'パスワードは6文字以上で入力してください。';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません。';
    } else {
        try {
            $db = Database::getConnection();

            // ユーザー名の重複チェック
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
            $stmt->execute([$username]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = 'このユーザー名は既に使用されています。';
            } else {
                // 新規ユーザー登録
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashed_password]);

                $success = 'ユーザー登録が完了しました。ログインしてください。';

                // フォームをクリア
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = 'ユーザー登録でエラーが発生しました。しばらく時間をおいてから再度お試しください。';
        }
    }
}

$pageTitle = '新規ユーザー登録';
?>

<?php include 'header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body">
                <h1 class="card-title text-center mb-4">
                    <i class="bi bi-person-plus me-2"></i>新規ユーザー登録
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
                        <div class="mt-2">
                            <a href="login.php" class="btn btn-success btn-sm">
                                <i class="bi bi-box-arrow-in-right me-1"></i>ログインページへ
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($success)): ?>
                    <form method="POST" id="registerForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                ユーザー名 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo h($_POST['username'] ?? ''); ?>" required minlength="3" maxlength="50"
                                pattern="[a-zA-Z0-9_-]+">
                            <div class="form-text">
                                3-50文字、英数字・アンダースコア・ハイフンのみ使用可能
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                パスワード <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required
                                minlength="6">
                            <div class="form-text">
                                6文字以上で入力してください
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">
                                パスワード確認 <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                required minlength="6">
                            <div id="password-match-feedback" class="form-text"></div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    <small>利用規約に同意します</small>
                                </label>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="registerBtn">
                                <i class="bi bi-person-plus me-1"></i>登録する
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">
                            <small class="text-muted">既にアカウントをお持ちですか？</small>
                        </p>
                        <a href="login.php" class="btn btn-outline-secondary btn-sm mt-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i>ログインページへ
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 利用規約説明 -->
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-info-circle me-1"></i>就活状況共有アプリについて
                </h6>
                <p class="card-text small text-muted">
                    このアプリは就職活動中の学生同士で情報を共有し、お互いを支え合うためのプラットフォームです。
                    登録されたデータは適切に管理され、他のユーザーとの情報共有にのみ使用されます。
                </p>
                <div class="mt-3">
                    <h6 class="small"><i class="bi bi-shield-check me-1"></i>主な機能</h6>
                    <ul class="small text-muted mb-0">
                        <li>業界別掲示板での情報交換</li>
                        <li>企業の進捗状況管理</li>
                        <li>TODOリストによるタスク管理</li>
                        <li>メンタル記録とモチベーション管理</li>
                        <li>就活状況のランキング表示</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirm');
        const feedback = document.getElementById('password-match-feedback');
        const registerBtn = document.getElementById('registerBtn');

        function checkPasswordMatch() {
            if (passwordConfirm.value === '') {
                feedback.textContent = '';
                feedback.className = 'form-text';
                return;
            }

            if (password.value === passwordConfirm.value) {
                feedback.textContent = '✓ パスワードが一致しています';
                feedback.className = 'form-text text-success';
            } else {
                feedback.textContent = '✗ パスワードが一致しません';
                feedback.className = 'form-text text-danger';
            }
        }

        password.addEventListener('input', checkPasswordMatch);
        passwordConfirm.addEventListener('input', checkPasswordMatch);

        // ユーザー名のリアルタイムバリデーション
        const username = document.getElementById('username');
        username.addEventListener('input', function () {
            const value = this.value;
            const pattern = /^[a-zA-Z0-9_-]+$/;

            if (value && !pattern.test(value)) {
                this.setCustomValidity('英数字、アンダースコア、ハイフンのみ使用できます');
            } else {
                this.setCustomValidity('');
            }
        });
    });
</script>

<?php include 'footer.php'; ?>