<?php
require_once 'config.php';
requireLogin();

$db = Database::getConnection();
$error = '';
$success = '';

// 今日の気分記録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $valid_statuses = ['とても良い', '良い', '普通', '落ち込み'];

    if (!in_array($status, $valid_statuses)) {
        $error = '無効な気分が選択されました。';
    } else {
        try {
            $today = date('Y-m-d');
            $stmt = $db->prepare("
                INSERT INTO mental_records (user_id, date, status) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE status = ?, updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$_SESSION['user_id'], $today, $status, $status]);
            $success = '今日の気分を記録しました。';
        } catch (PDOException $e) {
            $error = '気分の記録に失敗しました。';
        }
    }
}

// 今日の記録済み状態を取得
$todayStatus = null;
try {
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT status FROM mental_records WHERE user_id = ? AND date = ?");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $result = $stmt->fetch();
    if ($result) {
        $todayStatus = $result['status'];
    }
} catch (PDOException $e) {
    // エラーは無視して続行
}

// 履歴の取得（過去30日分）
$history = [];
try {
    $stmt = $db->prepare("
        SELECT date, status 
        FROM mental_records 
        WHERE user_id = ? 
        ORDER BY date DESC 
        LIMIT 30
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = '履歴の取得に失敗しました。';
}

// 個人統計情報の取得（過去30日）
$stats = ['とても良い' => 0, '良い' => 0, '普通' => 0, '落ち込み' => 0];
try {
    $stmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM mental_records 
        WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY status
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $results = $stmt->fetchAll();
    foreach ($results as $result) {
        $stats[$result['status']] = $result['count'];
    }
} catch (PDOException $e) {
    // エラーは無視して続行
}

// 全体統計情報の取得（過去30日）
$overall_stats = ['とても良い' => 0, '良い' => 0, '普通' => 0, '落ち込み' => 0];
$total_users_with_records = 0;
try {
    $stmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM mental_records 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY status
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
    foreach ($results as $result) {
        $overall_stats[$result['status']] = $result['count'];
    }

    // 記録があるユーザー数
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT user_id) as user_count
        FROM mental_records 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    $total_users_with_records = $result['user_count'];

} catch (PDOException $e) {
    // エラーは無視して続行
}

// 全体統計の計算
$total_overall = array_sum($overall_stats);
$active_users = $total_users_with_records;

// 過去7日間の推移データ（グラフ用）
$trend_data = [];
try {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count 
            FROM mental_records 
            WHERE date = ?
            GROUP BY status
        ");
        $stmt->execute([$date]);
        $day_stats = ['とても良い' => 0, '良い' => 0, '普通' => 0, '落ち込み' => 0];
        while ($row = $stmt->fetch()) {
            $day_stats[$row['status']] = $row['count'];
        }
        $trend_data[] = [
            'date' => $date,
            'label' => date('m/d', strtotime($date)),
            'stats' => $day_stats
        ];
    }
} catch (PDOException $e) {
    // エラーは無視して続行
}

$pageTitle = 'メンタル記録';
?>

<?php include 'header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="bi bi-emoji-smile me-2"></i>メンタル記録
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
    </div>
</div>

<div class="row">
    <!-- 今日の気分記録 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-day me-1"></i>今日の気分 (<?php echo date('Y年m月d日'); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if ($todayStatus): ?>
                    <div class="alert alert-info mb-3" role="alert">
                        <i class="bi bi-info-circle me-1"></i>
                        今日はすでに「<strong><?php echo h($todayStatus); ?></strong>」で記録されています。
                        下のボタンを押すと更新されます。
                    </div>
                <?php endif; ?>

                <p class="text-muted mb-3">今日の気分はいかがですか？ワンクリックで記録できます。</p>

                <form method="POST">
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <button type="submit" name="status" value="とても良い"
                                class="btn btn-success btn-lg w-100 <?php echo $todayStatus === 'とても良い' ? 'active' : ''; ?>">
                                <i class="bi bi-emoji-laughing d-block fs-1"></i>
                                <span>とても良い</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="submit" name="status" value="良い"
                                class="btn btn-info btn-lg w-100 <?php echo $todayStatus === '良い' ? 'active' : ''; ?>">
                                <i class="bi bi-emoji-smile d-block fs-1"></i>
                                <span>良い</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="submit" name="status" value="普通"
                                class="btn btn-warning btn-lg w-100 <?php echo $todayStatus === '普通' ? 'active' : ''; ?>">
                                <i class="bi bi-emoji-neutral d-block fs-1"></i>
                                <span>普通</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="submit" name="status" value="落ち込み"
                                class="btn btn-danger btn-lg w-100 <?php echo $todayStatus === '落ち込み' ? 'active' : ''; ?>">
                                <i class="bi bi-emoji-frown d-block fs-1"></i>
                                <span>落ち込み</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 統計情報 -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-1"></i>過去30日の統計
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 col-md-3">
                        <div class="text-success">
                            <i class="bi bi-emoji-laughing fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $stats['とても良い']; ?>日</div>
                            <small class="text-muted">とても良い</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-info">
                            <i class="bi bi-emoji-smile fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $stats['良い']; ?>日</div>
                            <small class="text-muted">良い</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-warning">
                            <i class="bi bi-emoji-neutral fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $stats['普通']; ?>日</div>
                            <small class="text-muted">普通</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-danger">
                            <i class="bi bi-emoji-frown fs-2"></i>
                            <div class="fw-bold fs-4"><?php echo $stats['落ち込み']; ?>日</div>
                            <small class="text-muted">落ち込み</small>
                        </div>
                    </div>
                </div>

                <?php
                $total = array_sum($stats);
                if ($total > 0):
                    ?>
                    <div class="progress mt-3" style="height: 25px;">
                        <?php if ($stats['とても良い'] > 0): ?>
                            <div class="progress-bar bg-success"
                                style="width: <?php echo ($stats['とても良い'] / $total) * 100; ?>%">
                                <?php echo round(($stats['とても良い'] / $total) * 100); ?>%
                            </div>
                        <?php endif; ?>
                        <?php if ($stats['良い'] > 0): ?>
                            <div class="progress-bar bg-info" style="width: <?php echo ($stats['良い'] / $total) * 100; ?>%">
                                <?php echo round(($stats['良い'] / $total) * 100); ?>%
                            </div>
                        <?php endif; ?>
                        <?php if ($stats['普通'] > 0): ?>
                            <div class="progress-bar bg-warning" style="width: <?php echo ($stats['普通'] / $total) * 100; ?>%">
                                <?php echo round(($stats['普通'] / $total) * 100); ?>%
                            </div>
                        <?php endif; ?>
                        <?php if ($stats['落ち込み'] > 0): ?>
                            <div class="progress-bar bg-danger" style="width: <?php echo ($stats['落ち込み'] / $total) * 100; ?>%">
                                <?php echo round(($stats['落ち込み'] / $total) * 100); ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 履歴一覧 -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-1"></i>記録履歴
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-1"></i>
                        まだ記録がありません。今日の気分を記録してみましょう！
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($history as $record): ?>
                            <div class="col-md-3 col-sm-4 col-6">
                                <div class="card text-center h-100">
                                    <div class="card-body py-2">
                                        <div class="small text-muted">
                                            <?php echo date('m/d', strtotime($record['date'])); ?>
                                            <?php if ($record['date'] === date('Y-m-d')): ?>
                                                <span class="badge bg-primary">今日</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        $emoji = '';
                                        $colorClass = '';
                                        switch ($record['status']) {
                                            case '元気':
                                                $emoji = 'bi-emoji-laughing';
                                                $colorClass = 'text-success';
                                                break;
                                            case '普通':
                                                $emoji = 'bi-emoji-neutral';
                                                $colorClass = 'text-warning';
                                                break;
                                            case '落ち込み':
                                                $emoji = 'bi-emoji-frown';
                                                $colorClass = 'text-danger';
                                                break;
                                        }
                                        ?>
                                        <div class="<?php echo $colorClass; ?>">
                                            <i class="bi <?php echo $emoji; ?> fs-4"></i>
                                            <div class="small fw-bold"><?php echo h($record['status']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 全員の統計（過去30日） -->
<div class="row mt-4">
    <div class="col-md-8">
        <h4>全員のメンタル統計（過去30日）</h4>

        <!-- 総合統計 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success"><?php echo $overall_stats['とても良い']; ?></h5>
                        <p class="card-text">とても良い</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info"><?php echo $overall_stats['良い']; ?></h5>
                        <p class="card-text">良い</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><?php echo $overall_stats['普通']; ?></h5>
                        <p class="card-text">普通</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-danger"><?php echo $overall_stats['落ち込み']; ?></h5>
                        <p class="card-text">落ち込み</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7日間のトレンドグラフ -->
        <div class="card">
            <div class="card-header">
                <h5>過去7日間のメンタルトレンド</h5>
            </div>
            <div class="card-body">
                <canvas id="mentalTrendChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>全体の統計情報</h5>
            </div>
            <div class="card-body">
                <p><strong>総記録数:</strong> <?php echo $total_overall; ?></p>
                <p><strong>参加者数:</strong> <?php echo $active_users; ?></p>
                <p><strong>平均記録数/人:</strong>
                    <?php echo $total_overall > 0 ? round($total_overall / $active_users, 1) : 0; ?></p>

                <?php if ($total_overall > 0): ?>
                    <div class="mt-3">
                        <h6>全体の分布</h6>
                        <div class="progress mb-1">
                            <?php foreach ($overall_stats as $mood => $count): ?>
                                <?php if ($count > 0): ?>
                                    <?php
                                    $class = '';
                                    switch ($mood) {
                                        case 'とても良い':
                                            $class = 'bg-success';
                                            break;
                                        case '良い':
                                            $class = 'bg-info';
                                            break;
                                        case '普通':
                                            $class = 'bg-warning';
                                            break;
                                        case '落ち込み':
                                            $class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <div class="progress-bar <?php echo $class; ?>"
                                        style="width: <?php echo ($count / $total_overall) * 100; ?>%"
                                        title="<?php echo $mood; ?>: <?php echo $count; ?>回">
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php foreach ($overall_stats as $mood => $count): ?>
                            <?php if ($count > 0): ?>
                                <small class="text-muted"><?php echo $mood; ?>:
                                    <?php echo round(($count / $total_overall) * 100); ?>% </small>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Chart.jsでトレンドグラフを描画
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('mentalTrendChart').getContext('2d');

        // PHPデータをJavaScriptに渡す
        const trendData = <?php echo json_encode($trend_data); ?>;

        // データの準備
        const labels = trendData.map(item => item.label);

        const datasets = [
            {
                label: 'とても良い',
                data: trendData.map(item => item.stats['とても良い'] || 0),
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.1
            },
            {
                label: '良い',
                data: trendData.map(item => item.stats['良い'] || 0),
                borderColor: 'rgb(13, 202, 240)',
                backgroundColor: 'rgba(13, 202, 240, 0.1)',
                tension: 0.1
            },
            {
                label: '普通',
                data: trendData.map(item => item.stats['普通'] || 0),
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.1
            },
            {
                label: '落ち込み',
                data: trendData.map(item => item.stats['落ち込み'] || 0),
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.1
            }
        ];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: '全員のメンタル状態推移'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    });
</script>

<?php include 'footer.php'; ?>