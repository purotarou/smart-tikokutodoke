<?php
// =============================================================
// 遅刻履歴（lateness_history）の管理ページ
// 一覧の表示・新規追加・編集・削除・CSVでの一括追加ができます。
// ※「ID」は自動で番号がつくので、追加のときは入力不要です。
// =============================================================
require __DIR__ . '/db.php';

$message = '';
$message_type = '';

// 曜日を日付から自動で求めるための表
$week_names = ['日', '月', '火', '水', '木', '金', '土'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // ---- 新規追加 ----
        if ($action === 'add') {
            // 曜日は日付から自動で決めます
            $week = $week_names[(int)date('w', strtotime($_POST['date']))] ?? '';
            $sql = "INSERT INTO lateness_history (grade, class, number, name, date, week, time, late_count, reason)
                    VALUES (:grade, :class, :number, :name, :date, :week, :time, :late_count, :reason)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':grade',      (int)$_POST['grade'], PDO::PARAM_INT);
            $stmt->bindValue(':class',      (int)$_POST['class'], PDO::PARAM_INT);
            $stmt->bindValue(':number',     (int)$_POST['number'], PDO::PARAM_INT);
            $stmt->bindValue(':name',       trim($_POST['name']));
            $stmt->bindValue(':date',       $_POST['date']);
            $stmt->bindValue(':week',       $week);
            $stmt->bindValue(':time',       $_POST['time']);
            $stmt->bindValue(':late_count', (int)$_POST['late_count'], PDO::PARAM_INT);
            $stmt->bindValue(':reason',     trim($_POST['reason']));
            $stmt->execute();
            $message = '遅刻の記録を1件追加しました。';
            $message_type = 'success';

        // ---- 編集を保存 ----
        } elseif ($action === 'update') {
            $week = $week_names[(int)date('w', strtotime($_POST['date']))] ?? '';
            $sql = "UPDATE lateness_history
                    SET grade = :grade, class = :class, number = :number, name = :name,
                        date = :date, week = :week, time = :time,
                        late_count = :late_count, reason = :reason
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':grade',      (int)$_POST['grade'], PDO::PARAM_INT);
            $stmt->bindValue(':class',      (int)$_POST['class'], PDO::PARAM_INT);
            $stmt->bindValue(':number',     (int)$_POST['number'], PDO::PARAM_INT);
            $stmt->bindValue(':name',       trim($_POST['name']));
            $stmt->bindValue(':date',       $_POST['date']);
            $stmt->bindValue(':week',       $week);
            $stmt->bindValue(':time',       $_POST['time']);
            $stmt->bindValue(':late_count', (int)$_POST['late_count'], PDO::PARAM_INT);
            $stmt->bindValue(':reason',     trim($_POST['reason']));
            $stmt->bindValue(':id',         (int)$_POST['id'], PDO::PARAM_INT);
            $stmt->execute();
            $message = '遅刻の記録を変更しました。';
            $message_type = 'success';

        // ---- 削除 ----
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM lateness_history WHERE id = :id");
            $stmt->bindValue(':id', (int)$_POST['id'], PDO::PARAM_INT);
            $stmt->execute();
            $message = '遅刻の記録を1件削除しました。';
            $message_type = 'success';

        // ---- 一括削除 ----
        } elseif ($action === 'delete_bulk') {
            $ids = array_filter(array_map('intval', $_POST['ids'] ?? []), fn($v) => $v > 0);
            if (empty($ids)) {
                throw new Exception('削除する記録にチェックが入っていません。');
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM lateness_history WHERE id IN ($placeholders)");
            $stmt->execute(array_values($ids));
            $count = $stmt->rowCount();
            $message = "{$count} 件の遅刻記録を削除しました。";
            $message_type = 'success';

        // ---- CSVで一括追加 ----
        } elseif ($action === 'csv') {
            if (empty($_FILES['csv_file']['tmp_name'])) {
                throw new Exception('CSVファイルが選ばれていません。');
            }
            $rows = read_csv_file($_FILES['csv_file']['tmp_name']);
            if (!$rows) {
                throw new Exception('CSVの中身が読み取れませんでした。');
            }
            // 1行目が見出し（左端が数字でない）の場合は読み飛ばす
            if (isset($rows[0][0]) && !is_numeric(trim($rows[0][0]))) {
                array_shift($rows);
            }

            // CSVの並び: 学年, 組, 出席番号, 名前, 日付, 時刻, 遅刻回数, 遅刻理由
            $sql = "INSERT INTO lateness_history (grade, class, number, name, date, week, time, late_count, reason)
                    VALUES (:grade, :class, :number, :name, :date, :week, :time, :late_count, :reason)";
            $stmt = $pdo->prepare($sql);

            $count = 0;
            foreach ($rows as $i => $row) {
                if (count($row) < 8) {
                    throw new Exception(($i + 1) . '行目の項目数が足りません。（8項目必要です）');
                }
                $week = $week_names[(int)date('w', strtotime($row[4]))] ?? '';
                $stmt->bindValue(':grade',      (int)$row[0], PDO::PARAM_INT);
                $stmt->bindValue(':class',      (int)$row[1], PDO::PARAM_INT);
                $stmt->bindValue(':number',     (int)$row[2], PDO::PARAM_INT);
                $stmt->bindValue(':name',       trim($row[3]));
                $stmt->bindValue(':date',       trim($row[4]));
                $stmt->bindValue(':week',       $week);
                $stmt->bindValue(':time',       trim($row[5]));
                $stmt->bindValue(':late_count', (int)$row[6], PDO::PARAM_INT);
                $stmt->bindValue(':reason',     trim($row[7]));
                $stmt->execute();
                $count++;
            }
            $message = "CSVから {$count} 件を追加しました。";
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = 'エラー: ' . $e->getMessage();
        $message_type = 'error';
    }

    header('Location: edit_late_history.php?msg=' . rawurlencode($message) . '&type=' . $message_type);
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = $_GET['type'] ?? '';
}

// 編集モード（?edit=ID）
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// 絞り込みフィルター
$filter_grade  = isset($_GET['grade'])  && $_GET['grade']  !== '' ? (int)$_GET['grade']  : null;
$filter_class  = isset($_GET['class'])  && $_GET['class']  !== '' ? (int)$_GET['class']  : null;
$filter_number = isset($_GET['number']) && $_GET['number'] !== '' ? (int)$_GET['number'] : null;
$filter_month  = isset($_GET['month'])  && $_GET['month']  !== '' ? (int)$_GET['month']  : null;
$filter_name   = isset($_GET['name'])   ? trim($_GET['name']) : '';

$where_parts  = [];
$where_params = [];
if ($filter_grade  !== null) { $where_parts[] = 'grade = ?';       $where_params[] = $filter_grade; }
if ($filter_class  !== null) { $where_parts[] = 'class = ?';       $where_params[] = $filter_class; }
if ($filter_number !== null) { $where_parts[] = 'number = ?';      $where_params[] = $filter_number; }
if ($filter_month  !== null) { $where_parts[] = 'MONTH(date) = ?'; $where_params[] = $filter_month; }
if ($filter_name   !== '')   { $where_parts[] = 'name LIKE ?';     $where_params[] = '%' . $filter_name . '%'; }
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$stmt = $pdo->prepare("SELECT * FROM lateness_history {$where_sql} ORDER BY date DESC, time DESC");
$stmt->execute($where_params);
$histories = $stmt->fetchAll();

// セレクトボックス用の選択肢
$grades  = $pdo->query("SELECT DISTINCT grade  FROM lateness_history ORDER BY grade")->fetchAll(PDO::FETCH_COLUMN);
$classes = $pdo->query("SELECT DISTINCT class  FROM lateness_history ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);
$numbers = $pdo->query("SELECT DISTINCT number FROM lateness_history ORDER BY number")->fetchAll(PDO::FETCH_COLUMN);
$pdo = null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/admin.css">
    <title>遅刻履歴の管理 | Smart遅刻届</title>
</head>
<body class="admin">
<h1 class="management-header">遅刻履歴の管理</h1>
<div class="admin-container">

    <a class="back-link" href="DBcontrol.php">← 管理メニューにもどる</a>

    <?php if ($message !== ''): ?>
        <div class="msg <?php echo $message_type === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo h($message); ?>
        </div>
    <?php endif; ?>

    <div class="guide-box">
        <strong>このページでできること</strong><br>
        生徒が出した遅刻届の記録を一覧で確認したり、修正・削除ができます。<br>
        ・記録は新しいものが上に並びます。<br>
        ・「曜日」は日付から自動で決まるので、入力する必要はありません。<br>
        ・1件ずつ直すときは「編集」ボタン、まとめて消すときは左の□にチェックを入れて「選択した記録をまとめて削除」を押してください。<br>
        ・まとめて追加するときは下の「CSVで一括追加」をお使いください。
    </div>

    <!-- ============ 一覧表 ============ -->
    <h2 class="management-subheader">遅刻届の記録一覧</h2>

    <form method="get" action="edit_late_history.php" class="filter-form">
        <label class="filter-label">学年：</label>
        <select name="grade" class="filter-select">
            <option value="">すべて</option>
            <?php foreach ($grades as $g): ?>
                <option value="<?php echo h($g); ?>" <?php if ($filter_grade === (int)$g) echo 'selected'; ?>><?php echo h($g); ?>年</option>
            <?php endforeach; ?>
        </select>
        <label class="filter-label">組：</label>
        <select name="class" class="filter-select">
            <option value="">すべて</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo h($c); ?>" <?php if ($filter_class === (int)$c) echo 'selected'; ?>><?php echo h($c); ?>組</option>
            <?php endforeach; ?>
        </select>
        <label class="filter-label">番号：</label>
        <select name="number" class="filter-select">
            <option value="">すべて</option>
            <?php foreach ($numbers as $n): ?>
                <option value="<?php echo h($n); ?>" <?php if ($filter_number === (int)$n) echo 'selected'; ?>><?php echo h($n); ?>番</option>
            <?php endforeach; ?>
        </select>
        <label class="filter-label">月：</label>
        <select name="month" class="filter-select">
            <option value="">すべて</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php if ($filter_month === $m) echo 'selected'; ?>><?php echo $m; ?>月</option>
            <?php endfor; ?>
        </select>
        <label class="filter-label">名前：</label>
        <input type="text" name="name" value="<?php echo h($filter_name); ?>" placeholder="例：田中" class="filter-input">
        <button type="submit" class="btn btn-add" style="padding:10px 24px;font-size:18px">絞り込む</button>
        <?php if ($filter_grade !== null || $filter_class !== null || $filter_number !== null || $filter_month !== null || $filter_name !== ''): ?>
            <a href="edit_late_history.php" class="btn btn-cancel" style="padding:10px 16px;font-size:18px">リセット</a>
        <?php endif; ?>
    </form>

    <p class="management-description"><?php echo count($histories); ?> 件が表示されています。</p>

    <!-- 一括削除ボタン（チェックを入れた行をまとめて削除） -->
    <div style="margin-bottom:10px">
        <button type="button" class="btn btn-delete" id="bulk-delete-btn" style="font-size:18px;padding:12px 28px">
            ☑ 選択した記録をまとめて削除する
        </button>
        <span id="checked-count" style="margin-left:14px;font-size:16px;color:#555"></span>
    </div>

    <!-- 一括削除用フォーム（JS経由で送信） -->
    <form method="post" action="edit_late_history.php" id="bulk-delete-form">
        <input type="hidden" name="action" value="delete_bulk">
    </form>

    <table class="data-table">
        <tr>
            <th><label title="全て選択／全て解除">
                <input type="checkbox" id="check-all" style="transform:scale(1.5)">
            </label></th>
            <th>ID</th>
            <th>学年</th>
            <th>組</th>
            <th>番号</th>
            <th>名前</th>
            <th>日付</th>
            <th>曜日</th>
            <th>時刻</th>
            <th>遅刻回数</th>
            <th>理由</th>
            <th>操作</th>
        </tr>
        <?php foreach ($histories as $hrow): ?>
            <?php if ($edit_id === (int)$hrow['id']): ?>
                <!-- 編集モードの行 -->
                <tr class="editing-row">
                    <form method="post" action="edit_late_history.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo h($hrow['id']); ?>">
                        <td></td>
                        <td><?php echo h($hrow['id']); ?></td>
                        <td><input type="number" name="grade"  value="<?php echo h($hrow['grade']); ?>" style="width:50px" required></td>
                        <td><input type="number" name="class"  value="<?php echo h($hrow['class']); ?>" style="width:50px" required></td>
                        <td><input type="number" name="number" value="<?php echo h($hrow['number']); ?>" style="width:50px" required></td>
                        <td><input type="text"   name="name"   value="<?php echo h($hrow['name']); ?>" style="width:90px" required></td>
                        <td><input type="date"   name="date"   value="<?php echo h($hrow['date']); ?>" required></td>
                        <td>自動</td>
                        <td><input type="time"   name="time"   value="<?php echo h($hrow['time']); ?>" required></td>
                        <td><input type="number" name="late_count" value="<?php echo h($hrow['late_count']); ?>" style="width:50px" required></td>
                        <td><input type="text"   name="reason" value="<?php echo h($hrow['reason']); ?>" style="width:120px"></td>
                        <td>
                            <button type="submit" class="btn btn-edit">保存</button>
                            <a class="btn btn-cancel" href="edit_late_history.php">やめる</a>
                        </td>
                    </form>
                </tr>
            <?php else: ?>
                <tr>
                    <td><input type="checkbox" class="row-check" value="<?php echo h($hrow['id']); ?>" style="transform:scale(1.5)"></td>
                    <td><?php echo h($hrow['id']); ?></td>
                    <td><?php echo h($hrow['grade']); ?></td>
                    <td><?php echo h($hrow['class']); ?></td>
                    <td><?php echo h($hrow['number']); ?></td>
                    <td><?php echo h($hrow['name']); ?></td>
                    <td><?php echo h($hrow['date']); ?></td>
                    <td><?php echo h($hrow['week']); ?></td>
                    <td><?php echo h($hrow['time']); ?></td>
                    <td><?php echo h($hrow['late_count']); ?></td>
                    <td style="text-align:left"><?php echo h($hrow['reason']); ?></td>
                    <td>
                        <a class="btn btn-edit" href="edit_late_history.php?edit=<?php echo h($hrow['id']); ?>">編集</a>
                        <form method="post" action="edit_late_history.php" style="display:inline"
                              onsubmit="return confirm('この遅刻記録（<?php echo h($hrow['name']); ?> さん / <?php echo h($hrow['date']); ?>）を本当に削除しますか？\nこの操作は元にもどせません。');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo h($hrow['id']); ?>">
                            <button type="submit" class="btn btn-delete">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$histories): ?>
            <tr><td colspan="12">まだ遅刻の記録がありません。</td></tr>
        <?php endif; ?>
    </table>

    <script>
    (function () {
        var checkAll  = document.getElementById('check-all');
        var bulkBtn   = document.getElementById('bulk-delete-btn');
        var countSpan = document.getElementById('checked-count');
        var bulkForm  = document.getElementById('bulk-delete-form');

        function getChecked() {
            return Array.from(document.querySelectorAll('.row-check:checked'));
        }

        function updateCount() {
            var n = getChecked().length;
            countSpan.textContent = n > 0 ? n + ' 件を選択中' : '';
        }

        // 全選択チェックボックス
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (cb) {
                cb.checked = checkAll.checked;
            });
            updateCount();
        });

        // 個別チェックが変わったら全選択チェックの状態も更新
        document.querySelectorAll('.row-check').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var all = document.querySelectorAll('.row-check');
                checkAll.checked = Array.from(all).every(function (c) { return c.checked; });
                checkAll.indeterminate = !checkAll.checked && Array.from(all).some(function (c) { return c.checked; });
                updateCount();
            });
        });

        // 一括削除ボタン
        bulkBtn.addEventListener('click', function () {
            var checked = getChecked();
            if (checked.length === 0) {
                alert('削除したい記録の左にある□にチェックを入れてから押してください。');
                return;
            }
            if (!confirm(checked.length + ' 件の遅刻記録を削除します。\nこの操作は元にもどせません。よろしいですか？')) {
                return;
            }
            // 既存の hidden ids を削除してから再追加
            bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
            checked.forEach(function (cb) {
                var hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'ids[]';
                hidden.value = cb.value;
                bulkForm.appendChild(hidden);
            });
            bulkForm.submit();
        });
    })();
    </script>

    <!-- ============ 新規追加 ============ -->
    <h2 class="management-subheader">遅刻の記録を1件ずつ追加する</h2>
    <div class="form-card">
        <p class="management-description">下の欄を入力して、「この記録を追加する」を押してください。（IDと曜日は自動で決まります）</p>
        <form method="post" action="edit_late_history.php">
            <input type="hidden" name="action" value="add">
            <div class="form-row"><label>学年</label>     <input type="number" name="grade" required></div>
            <div class="form-row"><label>組</label>       <input type="number" name="class" required></div>
            <div class="form-row"><label>出席番号</label> <input type="number" name="number" required></div>
            <div class="form-row"><label>名前</label>     <input type="text"   name="name" required></div>
            <div class="form-row"><label>日付</label>     <input type="date"   name="date" required></div>
            <div class="form-row"><label>時刻</label>     <input type="time"   name="time" required></div>
            <div class="form-row"><label>遅刻回数</label> <input type="number" name="late_count" value="1" required></div>
            <div class="form-row"><label>理由</label>     <input type="text"   name="reason" placeholder="例：寝坊、通院 など"></div>
            <button type="submit" class="btn btn-add">この記録を追加する</button>
        </form>
    </div>

    <!-- ============ CSV一括追加 ============ -->
    <h2 class="management-subheader">CSVファイルでまとめて追加する</h2>
    <div class="form-card">
        <div class="guide-box">
            <strong>CSVファイルの作り方</strong><br>
            ExcelやGoogleスプレッドシートで下のように入力し、「CSV形式」で保存してください。<br>
            1行目の見出しは、あってもなくても大丈夫です。<br>
            ・IDと曜日は自動でつくので、CSVに入れる必要はありません。<br>
            ・日付は <strong>2026-05-25</strong> のように、年-月-日 の形で書いてください。<br>
            ・時刻は <strong>08:15</strong> のように書いてください。
        </div>
        <p class="management-description">並び順は次のとおりです（左から）:</p>
        <div class="csv-sample">
            学年, 組, 出席番号, 名前, 日付, 時刻, 遅刻回数, 理由<br>
            1, 1, 1, 田中太郎, 2026-05-25, 08:15, 1, 寝坊<br>
            1, 1, 2, 田中次郎, 2026-05-25, 08:30, 2, 通院
        </div>
        <br>
        <form method="post" action="edit_late_history.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="csv">
            <div class="form-row">
                <label>CSVファイル</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-add">CSVを読み込んで追加する</button>
        </form>
    </div>

</div>
</body>
</html>
