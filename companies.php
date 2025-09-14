<?php
require_once 'config.php';
requireLogin();

$db = Database::getConnection();
$error = '';
$success = '';

// 企業追加・更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' && isset($_POST['company_name']) && isset($_POST['progress_status'])) {
            $company_name = trim($_POST['company_name']);
            $progress_status = $_POST['progress_status'];
            $notes = trim($_POST['notes']) ?: null;
            $applied_date = !empty($_POST['applied_date']) ? $_POST['applied_date'] : null;
            $interview_date = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;
            $result_date = !empty($_POST['result_date']) ? $_POST['result_date'] : null;

            if (empty($company_name)) {
                $error = '企業名を入力してください。';
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO company_status (user_id, company_name, progress_status, notes, applied_date, interview_date, result_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            progress_status = VALUES(progress_status),
                            notes = VALUES(notes),
                            applied_date = VALUES(applied_date),
                            interview_date = VALUES(interview_date),
                            result_date = VALUES(result_date),
                            updated_at = CURRENT_TIMESTAMP
                    ");
                    $stmt->execute([$_SESSION['user_id'], $company_name, $progress_status, $notes, $applied_date, $interview_date, $result_date]);
                    $success = '企業情報を保存しました。';
                } catch (PDOException $e) {
                    $error = '企業情報の保存に失敗しました。';
                }
            }
        } elseif ($action === 'update' && isset($_POST['company_id'])) {
            $company_id = $_POST['company_id'];
            $progress_status = $_POST['progress_status'];
            $notes = trim($_POST['notes']) ?: null;
            $applied_date = !empty($_POST['applied_date']) ? $_POST['applied_date'] : null;
            $interview_date = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;
            $result_date = !empty($_POST['result_date']) ? $_POST['result_date'] : null;

            try {
                $stmt = $db->prepare("
                    UPDATE company_status 
                    SET progress_status = ?, notes = ?, applied_date = ?, interview_date = ?, result_date = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$progress_status, $notes, $applied_date, $interview_date, $result_date, $company_id, $_SESSION['user_id']]);
                $success = '企業情報を更新しました。';
            } catch (PDOException $e) {
                $error = '企業情報の更新に失敗しました。';
            }
        } elseif ($action === 'delete' && isset($_POST['company_id'])) {
            $company_id = $_POST['company_id'];

            try {
                $stmt = $db->prepare("DELETE FROM company_status WHERE id = ? AND user_id = ?");
                $stmt->execute([$company_id, $_SESSION['user_id']]);
                $success = '企業情報を削除しました。';
            } catch (PDOException $e) {
                $error = '企業情報の削除に失敗しました。';
            }
        }
    }
}

// ユーザーの企業リスト取得
try {
    $stmt = $db->prepare("
        SELECT * FROM company_status 
        WHERE user_id = ? 
        ORDER BY 
            CASE progress_status
                WHEN '内定' THEN 1
                WHEN '最終面接' THEN 2
                WHEN '2次面接' THEN 3
                WHEN '1次面接' THEN 4
                WHEN '面接準備中' THEN 5
                WHEN '書類通過' THEN 6
                WHEN '書類選考中' THEN 7
                WHEN '応募済み' THEN 8
                WHEN '応募準備中' THEN 9
                WHEN '興味あり' THEN 10
                WHEN '不採用' THEN 11
                WHEN '辞退' THEN 12
                ELSE 13
            END,
            updated_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $companies = $stmt->fetchAll();
} catch (PDOException $e) {
    $companies = [];
    $error = '企業リストの取得に失敗しました。';
}

// 統計情報の取得
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_companies,
            COUNT(CASE WHEN progress_status IN ('応募済み', '書類選考中', '書類通過', '面接準備中', '1次面接', '2次面接', '最終面接', '内定', '不採用') THEN 1 END) as applied_count,
            COUNT(CASE WHEN progress_status IN ('書類通過', '面接準備中', '1次面接', '2次面接', '最終面接', '内定') THEN 1 END) as passed_count,
            COUNT(CASE WHEN progress_status = '内定' THEN 1 END) as offer_count,
            COUNT(CASE WHEN progress_status = '不採用' THEN 1 END) as rejected_count
        FROM company_status 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = ['total_companies' => 0, 'applied_count' => 0, 'passed_count' => 0, 'offer_count' => 0, 'rejected_count' => 0];
}

include 'header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-building"></i> 企業進捗管理</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                    <i class="bi bi-plus-circle"></i> 企業追加
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo h($success); ?></div>
            <?php endif; ?>

            <!-- 統計情報 -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo $stats['total_companies']; ?></h5>
                            <p class="card-text small">管理中企業</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['applied_count']; ?></h5>
                            <p class="card-text small">応募済み</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-info"><?php echo $stats['passed_count']; ?></h5>
                            <p class="card-text small">書類通過</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success"><?php echo $stats['offer_count']; ?></h5>
                            <p class="card-text small">内定</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger"><?php echo $stats['rejected_count']; ?></h5>
                            <p class="card-text small">不採用</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">
                                <?php echo $stats['applied_count'] > 0 ? round(($stats['passed_count'] / $stats['applied_count']) * 100) : 0; ?>%
                            </h5>
                            <p class="card-text small">通過率</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 企業リスト -->
            <div class="card">
                <div class="card-header">
                    <h5>企業一覧（<?php echo count($companies); ?>社）</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($companies)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">まだ企業が登録されていません。<br>「企業追加」ボタンから最初の企業を追加してみましょう。</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>企業名</th>
                                        <th>進捗状況</th>
                                        <th>応募日</th>
                                        <th>面接日</th>
                                        <th>結果日</th>
                                        <th>メモ</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                        <tr>
                                            <td><strong><?php echo h($company['company_name']); ?></strong></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch ($company['progress_status']) {
                                                    case '内定':
                                                        $statusClass = 'badge bg-success';
                                                        break;
                                                    case '最終面接':
                                                    case '2次面接':
                                                    case '1次面接':
                                                        $statusClass = 'badge bg-warning';
                                                        break;
                                                    case '面接準備中':
                                                    case '書類通過':
                                                        $statusClass = 'badge bg-info';
                                                        break;
                                                    case '書類選考中':
                                                    case '応募済み':
                                                        $statusClass = 'badge bg-primary';
                                                        break;
                                                    case '応募準備中':
                                                    case '興味あり':
                                                        $statusClass = 'badge bg-secondary';
                                                        break;
                                                    case '不採用':
                                                        $statusClass = 'badge bg-danger';
                                                        break;
                                                    case '辞退':
                                                        $statusClass = 'badge bg-dark';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge bg-light text-dark';
                                                }
                                                ?>
                                                <span
                                                    class="<?php echo $statusClass; ?>"><?php echo h($company['progress_status']); ?></span>
                                            </td>
                                            <td><?php echo $company['applied_date'] ? date('n/j', strtotime($company['applied_date'])) : '-'; ?>
                                            </td>
                                            <td><?php echo $company['interview_date'] ? date('n/j', strtotime($company['interview_date'])) : '-'; ?>
                                            </td>
                                            <td><?php echo $company['result_date'] ? date('n/j', strtotime($company['result_date'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <?php if ($company['notes']): ?>
                                                    <small
                                                        class="text-muted"><?php echo h(mb_substr($company['notes'], 0, 30)); ?><?php echo mb_strlen($company['notes']) > 30 ? '...' : ''; ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1"
                                                    onclick="editCompany(<?php echo htmlspecialchars(json_encode($company)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('この企業を削除しますか？');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="company_id"
                                                        value="<?php echo $company['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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
</div>

<!-- 企業追加モーダル -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">企業追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">企業名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="progress_status" class="form-label">進捗状況 <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="progress_status" name="progress_status" required>
                                <option value="興味あり">興味あり</option>
                                <option value="応募準備中">応募準備中</option>
                                <option value="応募済み">応募済み</option>
                                <option value="書類選考中">書類選考中</option>
                                <option value="書類通過">書類通過</option>
                                <option value="面接準備中">面接準備中</option>
                                <option value="1次面接">1次面接</option>
                                <option value="2次面接">2次面接</option>
                                <option value="最終面接">最終面接</option>
                                <option value="内定">内定</option>
                                <option value="不採用">不採用</option>
                                <option value="辞退">辞退</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="applied_date" class="form-label">応募日</label>
                            <input type="date" class="form-control" id="applied_date" name="applied_date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="interview_date" class="form-label">面接日</label>
                            <input type="date" class="form-control" id="interview_date" name="interview_date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="result_date" class="form-label">結果日</label>
                            <input type="date" class="form-control" id="result_date" name="result_date">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">メモ</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                            placeholder="企業の特徴、面接内容、感想など"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 企業編集モーダル -->
<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">企業情報編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="company_id" id="edit_company_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_company_name" class="form-label">企業名</label>
                            <input type="text" class="form-control" id="edit_company_name" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_progress_status" class="form-label">進捗状況 <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="edit_progress_status" name="progress_status" required>
                                <option value="興味あり">興味あり</option>
                                <option value="応募準備中">応募準備中</option>
                                <option value="応募済み">応募済み</option>
                                <option value="書類選考中">書類選考中</option>
                                <option value="書類通過">書類通過</option>
                                <option value="面接準備中">面接準備中</option>
                                <option value="1次面接">1次面接</option>
                                <option value="2次面接">2次面接</option>
                                <option value="最終面接">最終面接</option>
                                <option value="内定">内定</option>
                                <option value="不採用">不採用</option>
                                <option value="辞退">辞退</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_applied_date" class="form-label">応募日</label>
                            <input type="date" class="form-control" id="edit_applied_date" name="applied_date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_interview_date" class="form-label">面接日</label>
                            <input type="date" class="form-control" id="edit_interview_date" name="interview_date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_result_date" class="form-label">結果日</label>
                            <input type="date" class="form-control" id="edit_result_date" name="result_date">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">メモ</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"
                            placeholder="企業の特徴、面接内容、感想など"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editCompany(company) {
        document.getElementById('edit_company_id').value = company.id;
        document.getElementById('edit_company_name').value = company.company_name;
        document.getElementById('edit_progress_status').value = company.progress_status;
        document.getElementById('edit_applied_date').value = company.applied_date || '';
        document.getElementById('edit_interview_date').value = company.interview_date || '';
        document.getElementById('edit_result_date').value = company.result_date || '';
        document.getElementById('edit_notes').value = company.notes || '';

        var editModal = new bootstrap.Modal(document.getElementById('editCompanyModal'));
        editModal.show();
    }
</script>

<?php include 'footer.php'; ?>