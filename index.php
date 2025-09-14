<?php
require_once 'config.php';

startSession();

// ログインしている場合は業界別掲示板にリダイレクト
if (isLoggedIn()) {
    header('Location: threads.php');
    exit();
}

$pageTitle = '就活状況共有アプリ';
?>

<?php include 'header.php'; ?>

<!-- ヒーローセクション -->
<div class="bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="bi bi-people-fill me-3"></i>就活状況共有アプリ
                </h1>
                <p class="lead mb-4">
                    就職活動中の学生同士で情報を共有し、お互いを支え合うためのプラットフォーム。
                    一人で悩まず、みんなで就活を乗り越えましょう。
                </p>
                <div class="d-flex gap-3">
                    <a href="register.php" class="btn btn-light btn-lg">
                        <i class="bi bi-person-plus me-2"></i>新規登録
                    </a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>ログイン
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="bi bi-people text-white-50" style="font-size: 12rem;"></i>
            </div>
        </div>
    </div>
</div>

<!-- 機能紹介 -->
<div class="container mb-5">
    <h2 class="text-center mb-5">
        <i class="bi bi-star me-2"></i>主な機能
    </h2>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-3" style="font-size: 3rem;">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <h5 class="card-title">業界別掲示板</h5>
                    <p class="card-text text-muted">
                        IT、金融、商社など8つの業界別に情報交換。
                        同じ業界を志望する仲間と繋がろう。
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-3" style="font-size: 3rem;">
                        <i class="bi bi-building"></i>
                    </div>
                    <h5 class="card-title">企業進捗管理</h5>
                    <p class="card-text text-muted">
                        応募から内定まで12段階で管理。
                        どの企業がどの段階にあるか一目で把握。
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-3" style="font-size: 3rem;">
                        <i class="bi bi-check2-square"></i>
                    </div>
                    <h5 class="card-title">TODOリスト</h5>
                    <p class="card-text text-muted">
                        ES作成、面接対策など就活タスクを管理。
                        優先度や期限も設定できます。
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-info mb-3" style="font-size: 3rem;">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <h5 class="card-title">メンタル記録</h5>
                    <p class="card-text text-muted">
                        毎日の気分を記録してモチベーション管理。
                        グラフで全体の傾向も確認できます。
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger mb-3" style="font-size: 3rem;">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <h5 class="card-title">ランキング</h5>
                    <p class="card-text text-muted">
                        応募数や通過数のランキングを表示。
                        他の人の頑張りからモチベーションをもらおう。
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-secondary mb-3" style="font-size: 3rem;">
                        <i class="bi bi-heart"></i>
                    </div>
                    <h5 class="card-title">いいね機能</h5>
                    <p class="card-text text-muted">
                        投稿や返信にいいねで共感を表現。
                        お互いを励まし合う仕組みです。
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 統計情報 -->
<div class="bg-light py-5 mb-5">
    <div class="container">
        <h2 class="text-center mb-5">
            <i class="bi bi-graph-up me-2"></i>コミュニティ統計
        </h2>

        <?php
        try {
            $db = Database::getConnection();

            // 統計データを取得
            $stats = [];

            // ユーザー数
            $stmt = $db->query("SELECT COUNT(*) FROM users");
            $stats['users'] = $stmt->fetchColumn();

            // 投稿数
            $stmt = $db->query("SELECT COUNT(*) FROM posts");
            $stats['posts'] = $stmt->fetchColumn();

            // 企業数
            $stmt = $db->query("SELECT COUNT(DISTINCT company_name) FROM company_status");
            $stats['companies'] = $stmt->fetchColumn();

            // TODO数
            $stmt = $db->query("SELECT COUNT(*) FROM todos");
            $stats['todos'] = $stmt->fetchColumn();

        } catch (PDOException $e) {
            $stats = ['users' => 0, 'posts' => 0, 'companies' => 0, 'todos' => 0];
        }
        ?>

        <div class="row text-center">
            <div class="col-md-3">
                <div class="card border-0">
                    <div class="card-body">
                        <h3 class="text-primary"><?php echo number_format($stats['users']); ?></h3>
                        <p class="text-muted mb-0">登録ユーザー</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0">
                    <div class="card-body">
                        <h3 class="text-success"><?php echo number_format($stats['posts']); ?></h3>
                        <p class="text-muted mb-0">投稿数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0">
                    <div class="card-body">
                        <h3 class="text-warning"><?php echo number_format($stats['companies']); ?></h3>
                        <p class="text-muted mb-0">管理中企業</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0">
                    <div class="card-body">
                        <h3 class="text-info"><?php echo number_format($stats['todos']); ?></h3>
                        <p class="text-muted mb-0">TODO数</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="container text-center mb-5">
    <div class="card border-0 shadow-lg">
        <div class="card-body py-5">
            <h2 class="mb-4">
                <i class="bi bi-rocket me-2"></i>今すぐ就活をスタート！
            </h2>
            <p class="lead text-muted mb-4">
                一人で頑張る必要はありません。みんなで情報を共有し、お互いを支え合いながら内定を目指しましょう。
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="register.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-person-plus me-2"></i>無料で始める
                </a>
                <a href="login.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>ログイン
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>