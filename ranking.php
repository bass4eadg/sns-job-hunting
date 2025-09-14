<?php
require_once 'config.php';
requireLogin();

$db = Database::getConnection();

try {
    // 応募社数ランキング（応募済み以上の進捗状況）
    $stmt = $db->prepare("
        SELECT 
            u.name,
            COUNT(CASE WHEN cs.progress_status IN ('応募済み', '書類選考中', '書類通過', '面接準備中', '1次面接', '2次面接', '最終面接', '内定', '不採用') THEN 1 END) as applied_count,
            COUNT(CASE WHEN cs.progress_status IN ('書類通過', '面接準備中', '1次面接', '2次面接', '最終面接', '内定') THEN 1 END) as passed_count,
            COUNT(CASE WHEN cs.progress_status = '内定' THEN 1 END) as offer_count
        FROM users u
        LEFT JOIN company_status cs ON u.id = cs.user_id
        GROUP BY u.id, u.name
        ORDER BY applied_count DESC, passed_count DESC, offer_count DESC
    ");
    $stmt->execute();
    $ranking_data = $stmt->fetchAll();

    // 全体統計
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_users,
            COUNT(cs.id) as total_companies,
            COUNT(CASE WHEN cs.progress_status IN ('応募済み', '書類選考中', '書類通過', '面接準備中', '1次面接', '2次面接', '最終面接', '内定', '不採用') THEN 1 END) as total_applied,
            COUNT(CASE WHEN cs.progress_status IN ('書類通過', '面接準備中', '1次面接', '2次面接', '最終面接', '内定') THEN 1 END) as total_passed,
            COUNT(CASE WHEN cs.progress_status = '内定' THEN 1 END) as total_offers
        FROM users u
        LEFT JOIN company_status cs ON u.id = cs.user_id
    ");
    $stmt->execute();
    $overall_stats = $stmt->fetch();

    // TODOタスク完了ランキング
    $stmt = $db->prepare("
        SELECT 
            u.name,
            COUNT(t.id) as total_todos,
            COUNT(CASE WHEN t.status = '完了' THEN 1 END) as completed_todos,
            ROUND(COUNT(CASE WHEN t.status = '完了' THEN 1 END) * 100.0 / NULLIF(COUNT(t.id), 0), 1) as completion_rate
        FROM users u
        LEFT JOIN todos t ON u.id = t.user_id
        GROUP BY u.id, u.name
        HAVING total_todos > 0
        ORDER BY completion_rate DESC, completed_todos DESC
    ");
    $stmt->execute();
    $todo_ranking = $stmt->fetchAll();

} catch (PDOException $e) {
    $ranking_data = [];
    $todo_ranking = [];
    $overall_stats = ['total_users' => 0, 'total_companies' => 0, 'total_applied' => 0, 'total_passed' => 0, 'total_offers' => 0];
    $error = 'ランキングデータの取得に失敗しました。';
}

$pageTitle = 'ランキング';
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="bi bi-trophy me-2"></i>ランキング
        </h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 全体統計 -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up me-1"></i>全体統計
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2">
                        <div class="text-primary">
                            <i class="bi bi-people fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $overall_stats['total_users']; ?></div>
                            <small class="text-muted">登録ユーザー</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-info">
                            <i class="bi bi-building fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $overall_stats['total_companies']; ?></div>
                            <small class="text-muted">管理企業数</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-warning">
                            <i class="bi bi-send fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $overall_stats['total_applied']; ?></div>
                            <small class="text-muted">総応募数</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-success">
                            <i class="bi bi-check-circle fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $overall_stats['total_passed']; ?></div>
                            <small class="text-muted">書類通過数</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-danger">
                            <i class="bi bi-award fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $overall_stats['total_offers']; ?></div>
                            <small class="text-muted">内定数</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-secondary">
                            <i class="bi bi-percent fs-2"></i>
                            <div class="fw-bold fs-4">
                                <?php echo $overall_stats['total_applied'] > 0 ? round(($overall_stats['total_offers'] / $overall_stats['total_applied']) * 100, 1) : 0; ?>%
                            </div>
                            <small class="text-muted">内定率</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 就活進捗ランキング -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-1"></i>就活進捗ランキング
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($ranking_data)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-1"></i>
                        ランキングデータがありません。
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>順位</th>
                                    <th>ユーザー</th>
                                    <th>応募</th>
                                    <th>通過</th>
                                    <th>内定</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ranking_data as $index => $user): ?>
                                    <tr class="<?php echo $_SESSION['user_id'] == $user['name'] ? 'table-warning' : ''; ?>">
                                        <td>
                                            <?php
                                            $rank = $index + 1;
                                            if ($rank == 1)
                                                echo '<i class="bi bi-trophy-fill text-warning"></i>';
                                            elseif ($rank == 2)
                                                echo '<i class="bi bi-trophy text-secondary"></i>';
                                            elseif ($rank == 3)
                                                echo '<i class="bi bi-trophy text-warning"></i>';
                                            else
                                                echo $rank;
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?php echo h($user['name']); ?></strong>
                                            <?php if ($user['name'] == $_SESSION['user_name']): ?>
                                                <small class="badge bg-primary ms-1">YOU</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-warning text-dark"><?php echo $user['applied_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $user['passed_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo $user['offer_count']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TODOタスク完了ランキング -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-check2-square me-1"></i>タスク完了ランキング
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($todo_ranking)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-1"></i>
                        TODOデータがありません。
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>順位</th>
                                    <th>ユーザー</th>
                                    <th>完了率</th>
                                    <th>完了/総数</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todo_ranking as $index => $user): ?>
                                    <tr class="<?php echo $user['name'] == $_SESSION['user_name'] ? 'table-warning' : ''; ?>">
                                        <td>
                                            <?php
                                            $rank = $index + 1;
                                            if ($rank == 1)
                                                echo '<i class="bi bi-trophy-fill text-warning"></i>';
                                            elseif ($rank == 2)
                                                echo '<i class="bi bi-trophy text-secondary"></i>';
                                            elseif ($rank == 3)
                                                echo '<i class="bi bi-trophy text-warning"></i>';
                                            else
                                                echo $rank;
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?php echo h($user['name']); ?></strong>
                                            <?php if ($user['name'] == $_SESSION['user_name']): ?>
                                                <small class="badge bg-primary ms-1">YOU</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success"
                                                    style="width: <?php echo $user['completion_rate']; ?>%">
                                                    <?php echo $user['completion_rate']; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo $user['completed_todos']; ?> / <?php echo $user['total_todos']; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info" role="alert">
            <i class="bi bi-lightbulb me-1"></i>
            <strong>ヒント:</strong>
            チェックシート機能で企業管理を行い、TODOリスト機能でタスクを管理すると、ランキングに反映されます！
            頑張って就活を進めてランキング上位を目指しましょう。
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>