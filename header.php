<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? h($pageTitle) : '就活状況共有アプリ'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/likes.js" defer></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="threads.php">
                <i class="bi bi-people-fill me-2"></i>就活状況共有アプリ
            </a>

            <?php if (isLoggedIn()): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="threads.php">
                                <i class="bi bi-chat-dots me-1"></i>掲示板
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="mental.php">
                                <i class="bi bi-emoji-smile me-1"></i>メンタル記録
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="companies.php">
                                <i class="bi bi-building me-1"></i>企業管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="todos.php">
                                <i class="bi bi-check2-square me-1"></i>TODO
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="ranking.php">
                                <i class="bi bi-trophy me-1"></i>ランキング
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo h($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-1"></i>ログアウト
                                    </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mt-4">
        <?php
        // フラッシュメッセージの表示
        $flash = getFlashMessage();
        if ($flash):
            $alertClass = 'alert-info';
            if ($flash['type'] === 'error')
                $alertClass = 'alert-danger';
            if ($flash['type'] === 'success')
                $alertClass = 'alert-success';
            if ($flash['type'] === 'warning')
                $alertClass = 'alert-warning';
            ?>
            <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                <?php echo h($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>