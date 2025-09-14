<?php
require_once 'config.php';
requireLogin();

$db = Database::getConnection();
$error = '';
$success = '';

// TODO操作処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' && isset($_POST['content'])) {
            $content = trim($_POST['content']);
            $priority = $_POST['priority'] ?? '中';
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if (empty($content)) {
                $error = 'TODOの内容を入力してください。';
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO todos (user_id, content, priority, due_date) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$_SESSION['user_id'], $content, $priority, $due_date]);
                    $success = 'TODOを追加しました。';
                } catch (PDOException $e) {
                    $error = 'TODOの追加に失敗しました。';
                }
            }
        } elseif ($action === 'update' && isset($_POST['todo_id'])) {
            $todo_id = $_POST['todo_id'];
            $content = trim($_POST['content']);
            $priority = $_POST['priority'];
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if (empty($content)) {
                $error = 'TODOの内容を入力してください。';
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE todos 
                        SET content = ?, priority = ?, due_date = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$content, $priority, $due_date, $todo_id, $_SESSION['user_id']]);
                    $success = 'TODOを更新しました。';
                } catch (PDOException $e) {
                    $error = 'TODOの更新に失敗しました。';
                }
            }
        } elseif ($action === 'toggle' && isset($_POST['todo_id'])) {
            $todo_id = $_POST['todo_id'];
            $current_status = $_POST['current_status'];
            $new_status = ($current_status === '完了') ? '未完了' : '完了';
            $completed_at = ($new_status === '完了') ? 'CURRENT_TIMESTAMP' : 'NULL';

            try {
                $stmt = $db->prepare("
                    UPDATE todos 
                    SET status = ?, completed_at = " . $completed_at . ", updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$new_status, $todo_id, $_SESSION['user_id']]);
                $success = 'TODOのステータスを更新しました。';
            } catch (PDOException $e) {
                $error = 'ステータスの更新に失敗しました。';
            }
        } elseif ($action === 'delete' && isset($_POST['todo_id'])) {
            $todo_id = $_POST['todo_id'];

            try {
                $stmt = $db->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?");
                $stmt->execute([$todo_id, $_SESSION['user_id']]);
                $success = 'TODOを削除しました。';
            } catch (PDOException $e) {
                $error = 'TODOの削除に失敗しました。';
            }
        }
    }
}

// フィルター処理
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
$params = [$_SESSION['user_id']];

switch ($filter) {
    case 'pending':
        $where_clause = "AND status = '未完了'";
        break;
    case 'completed':
        $where_clause = "AND status = '完了'";
        break;
    case 'high':
        $where_clause = "AND priority = '高' AND status = '未完了'";
        break;
    case 'overdue':
        $where_clause = "AND due_date < CURDATE() AND status = '未完了'";
        break;
}

// TODOリスト取得
try {
    $stmt = $db->prepare("
        SELECT * FROM todos 
        WHERE user_id = ? $where_clause
        ORDER BY 
            CASE status WHEN '未完了' THEN 0 ELSE 1 END,
            CASE priority WHEN '高' THEN 0 WHEN '中' THEN 1 ELSE 2 END,
            CASE 
                WHEN due_date IS NULL THEN '9999-12-31'
                ELSE due_date 
            END ASC,
            created_at DESC
    ");
    $stmt->execute($params);
    $todos = $stmt->fetchAll();
} catch (PDOException $e) {
    $todos = [];
    $error = 'TODOリストの取得に失敗しました。';
}

// 統計情報の取得
try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_todos,
            COUNT(CASE WHEN status = '未完了' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = '完了' THEN 1 END) as completed_count,
            COUNT(CASE WHEN priority = '高' AND status = '未完了' THEN 1 END) as high_priority_count,
            COUNT(CASE WHEN due_date < CURDATE() AND status = '未完了' THEN 1 END) as overdue_count
        FROM todos 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = ['total_todos' => 0, 'pending_count' => 0, 'completed_count' => 0, 'high_priority_count' => 0, 'overdue_count' => 0];
}

include 'header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-check2-square"></i> TODOリスト</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTodoModal">
                    <i class="bi bi-plus-circle"></i> TODO追加
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
                            <h5 class="card-title text-primary"><?php echo $stats['total_todos']; ?></h5>
                            <p class="card-text small">総TODO数</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><?php echo $stats['pending_count']; ?></h5>
                            <p class="card-text small">未完了</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-success"><?php echo $stats['completed_count']; ?></h5>
                            <p class="card-text small">完了</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-danger"><?php echo $stats['high_priority_count']; ?></h5>
                            <p class="card-text small">高優先度</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-dark"><?php echo $stats['overdue_count']; ?></h5>
                            <p class="card-text small">期限切れ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title text-muted">
                                <?php echo $stats['total_todos'] > 0 ? round(($stats['completed_count'] / $stats['total_todos']) * 100) : 0; ?>%
                            </h5>
                            <p class="card-text small">完了率</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- フィルター -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="btn-group" role="group">
                        <a href="?filter=all"
                            class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            すべて (<?php echo $stats['total_todos']; ?>)
                        </a>
                        <a href="?filter=pending"
                            class="btn <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            未完了 (<?php echo $stats['pending_count']; ?>)
                        </a>
                        <a href="?filter=completed"
                            class="btn <?php echo $filter === 'completed' ? 'btn-success' : 'btn-outline-success'; ?>">
                            完了 (<?php echo $stats['completed_count']; ?>)
                        </a>
                        <a href="?filter=high"
                            class="btn <?php echo $filter === 'high' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                            高優先度 (<?php echo $stats['high_priority_count']; ?>)
                        </a>
                        <a href="?filter=overdue"
                            class="btn <?php echo $filter === 'overdue' ? 'btn-dark' : 'btn-outline-dark'; ?>">
                            期限切れ (<?php echo $stats['overdue_count']; ?>)
                        </a>
                    </div>
                </div>
            </div>

            <!-- TODOリスト -->
            <div class="card">
                <div class="card-header">
                    <h5>TODOリスト（<?php echo count($todos); ?>件）</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($todos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check2-square text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3">
                                <?php if ($filter === 'all'): ?>
                                    まだTODOが登録されていません。<br>「TODO追加」ボタンから最初のタスクを追加してみましょう。
                                <?php else: ?>
                                    該当するTODOがありません。
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($todos as $todo): ?>
                                <div
                                    class="list-group-item <?php echo $todo['status'] === '完了' ? 'list-group-item-success' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <!-- 完了チェックボックス -->
                                                <form method="POST" style="display: inline;" class="me-3">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                                    <input type="hidden" name="current_status"
                                                        value="<?php echo $todo['status']; ?>">
                                                    <button type="submit" class="btn btn-link p-0 border-0"
                                                        style="font-size: 1.2rem;">
                                                        <?php if ($todo['status'] === '完了'): ?>
                                                            <i class="bi bi-check-square-fill text-success"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-square text-muted"></i>
                                                        <?php endif; ?>
                                                    </button>
                                                </form>

                                                <!-- 優先度バッジ -->
                                                <?php
                                                $priorityClass = '';
                                                switch ($todo['priority']) {
                                                    case '高':
                                                        $priorityClass = 'bg-danger';
                                                        break;
                                                    case '中':
                                                        $priorityClass = 'bg-warning';
                                                        break;
                                                    case '低':
                                                        $priorityClass = 'bg-secondary';
                                                        break;
                                                }
                                                ?>
                                                <span
                                                    class="badge <?php echo $priorityClass; ?> me-2"><?php echo h($todo['priority']); ?></span>

                                                <!-- TODO内容 -->
                                                <h6
                                                    class="mb-0 <?php echo $todo['status'] === '完了' ? 'text-decoration-line-through text-muted' : ''; ?>">
                                                    <?php echo h($todo['content']); ?>
                                                </h6>
                                            </div>

                                            <div class="text-muted small">
                                                <?php if ($todo['due_date']): ?>
                                                    <?php
                                                    $due_date = new DateTime($todo['due_date']);
                                                    $today = new DateTime();
                                                    $diff = $today->diff($due_date);
                                                    $is_overdue = $due_date < $today && $todo['status'] === '未完了';
                                                    ?>
                                                    <i class="bi bi-calendar-event"></i>
                                                    <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                                        期限: <?php echo $due_date->format('n月j日'); ?>
                                                        <?php if ($is_overdue): ?>
                                                            (期限切れ)
                                                        <?php elseif ($todo['status'] === '未完了'): ?>
                                                            <?php if ($diff->days == 0): ?>
                                                                (今日)
                                                            <?php elseif ($diff->days == 1 && $due_date > $today): ?>
                                                                (明日)
                                                            <?php elseif ($due_date > $today): ?>
                                                                (あと<?php echo $diff->days; ?>日)
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <i class="bi bi-calendar-x"></i> 期限なし
                                                <?php endif; ?>

                                                <span class="ms-3">
                                                    <i class="bi bi-clock"></i>
                                                    作成: <?php echo date('n/j H:i', strtotime($todo['created_at'])); ?>
                                                </span>

                                                <?php if ($todo['completed_at']): ?>
                                                    <span class="ms-3">
                                                        <i class="bi bi-check-circle"></i>
                                                        完了: <?php echo date('n/j H:i', strtotime($todo['completed_at'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- 操作ボタン -->
                                        <div class="ms-3">
                                            <button class="btn btn-sm btn-outline-primary me-1"
                                                onclick="editTodo(<?php echo htmlspecialchars(json_encode($todo)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirm('このTODOを削除しますか？');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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
</div>

<!-- TODO追加モーダル -->
<div class="modal fade" id="addTodoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">TODO追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label for="content" class="form-label">TODO内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="3" required
                            placeholder="例: 履歴書を作成する"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">優先度</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="低">低</option>
                                <option value="中" selected>中</option>
                                <option value="高">高</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">期限</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">追加</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- TODO編集モーダル -->
<div class="modal fade" id="editTodoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">TODO編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="todo_id" id="edit_todo_id">

                    <div class="mb-3">
                        <label for="edit_content" class="form-label">TODO内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_content" name="content" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_priority" class="form-label">優先度</label>
                            <select class="form-select" id="edit_priority" name="priority">
                                <option value="低">低</option>
                                <option value="中">中</option>
                                <option value="高">高</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_due_date" class="form-label">期限</label>
                            <input type="date" class="form-control" id="edit_due_date" name="due_date">
                        </div>
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
    function editTodo(todo) {
        document.getElementById('edit_todo_id').value = todo.id;
        document.getElementById('edit_content').value = todo.content;
        document.getElementById('edit_priority').value = todo.priority;
        document.getElementById('edit_due_date').value = todo.due_date || '';

        var editModal = new bootstrap.Modal(document.getElementById('editTodoModal'));
        editModal.show();
    }

    // 今日の日付を期限の最小値に設定
    document.addEventListener('DOMContentLoaded', function () {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('due_date').setAttribute('min', today);
        document.getElementById('edit_due_date').setAttribute('min', today);
    });
</script>

<?php include 'footer.php'; ?>