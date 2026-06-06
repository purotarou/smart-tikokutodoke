<?php
// =============================================================
// 担任情報（class_teacher）の管理ページ
// 一覧の表示・新規追加・編集・削除・CSVでの一括追加ができます。
// ※この表は「メールアドレス」が1人を見分ける目印になっています。
// =============================================================
require __DIR__ . '/db.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // ---- 新規追加 ----
        if ($action === 'add') {
            $sql = "INSERT INTO class_teacher (grade, class, name, c_teacher_mail)
                    VALUES (:grade, :class, :name, :mail)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':grade', (int)$_POST['grade'], PDO::PARAM_INT);
            $stmt->bindValue(':class', (int)$_POST['class'], PDO::PARAM_INT);
            $stmt->bindValue(':name',  trim($_POST['name']));
            $stmt->bindValue(':mail',  trim($_POST['c_teacher_mail']));
            $stmt->execute();
            $message = '担任の先生を1人追加しました。';
            $message_type = 'success';

        // ---- 編集を保存（メールアドレスは変えられません） ----
        } elseif ($action === 'update') {
            $sql = "UPDATE class_teacher
                    SET grade = :grade, class = :class, name = :name
                    WHERE c_teacher_mail = :mail";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':grade', (int)$_POST['grade'], PDO::PARAM_INT);
            $stmt->bindValue(':class', (int)$_POST['class'], PDO::PARAM_INT);
            $stmt->bindValue(':name',  trim($_POST['name']));
            $stmt->bindValue(':mail',  trim($_POST['c_teacher_mail']));
            $stmt->execute();
            $message = '担任の先生の情報を変更しました。';
            $message_type = 'success';

        // ---- 削除 ----
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM class_teacher WHERE c_teacher_mail = :mail");
            $stmt->bindValue(':mail', trim($_POST['c_teacher_mail']));
            $stmt->execute();
            $message = '担任の先生を1人削除しました。';
            $message_type = 'success';

        // ---- 一括削除 ----
        } elseif ($action === 'delete_bulk') {
            $mails = array_filter(array_map('trim', $_POST['mails'] ?? []), fn($v) => $v !== '');
            if (empty($mails)) {
                throw new Exception('削除する先生にチェックが入っていません。');
            }
            $placeholders = implode(',', array_fill(0, count($mails), '?'));
            $stmt = $pdo->prepare("DELETE FROM class_teacher WHERE c_teacher_mail IN ($placeholders)");
            $stmt->execute(array_values($mails));
            $count = $stmt->rowCount();
            $message = "{$count} 人の先生を削除しました。";
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

            $sql = "INSERT INTO class_teacher (grade, class, name, c_teacher_mail)
                    VALUES (:grade, :class, :name, :mail)
                    ON DUPLICATE KEY UPDATE
                        grade = VALUES(grade), class = VALUES(class), name = VALUES(name)";
            $stmt = $pdo->prepare($sql);

            $count = 0;
            foreach ($rows as $i => $row) {
                if (count($row) < 4) {
                    throw new Exception(($i + 1) . '行目の項目数が足りません。（4項目必要です）');
                }
                $stmt->bindValue(':grade', (int)$row[0], PDO::PARAM_INT);
                $stmt->bindValue(':class', (int)$row[1], PDO::PARAM_INT);
                $stmt->bindValue(':name',  trim($row[2]));
                $stmt->bindValue(':mail',  trim($row[3]));
                $stmt->execute();
                $count++;
            }
            $message = "CSVから {$count} 件を追加・更新しました。";
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = 'エラー: ' . $e->getMessage();
        $message_type = 'error';
    }

    header('Location: edit_teacher_info.php?msg=' . rawurlencode($message) . '&type=' . $message_type);
    exit;
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = $_GET['type'] ?? '';
}

// 編集モード（?edit=メールアドレス）
$edit_mail = isset($_GET['edit']) ? $_GET['edit'] : '';

// 絞り込みフィルター
$filter_grade = isset($_GET['grade']) && $_GET['grade'] !== '' ? (int)$_GET['grade'] : null;
$filter_class = isset($_GET['class']) && $_GET['class'] !== '' ? (int)$_GET['class'] : null;
$filter_name  = isset($_GET['name'])  ? trim($_GET['name']) : '';

$where_parts  = [];
$where_params = [];
if ($filter_grade !== null) { $where_parts[] = 'grade = ?';  $where_params[] = $filter_grade; }
if ($filter_class !== null) { $where_parts[] = 'class = ?';  $where_params[] = $filter_class; }
if ($filter_name  !== '')   { $where_parts[] = 'name LIKE ?'; $where_params[] = '%' . $filter_name . '%'; }
$where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$stmt = $pdo->prepare("SELECT * FROM class_teacher {$where_sql} ORDER BY grade, class");
$stmt->execute($where_params);
$teachers = $stmt->fetchAll();

// セレクトボックス用の選択肢
$grades  = $pdo->query("SELECT DISTINCT grade FROM class_teacher ORDER BY grade")->fetchAll(PDO::FETCH_COLUMN);
$classes = $pdo->query("SELECT DISTINCT class FROM class_teacher ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);

$pdo = null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/admin.css">
    <title>担任情報の管理 | Smart遅刻届</title>
</head>
<body class="admin">
<h1 class="management-header">担任情報の管理</h1>
<div class="admin-container">

    <a class="back-link" href="DBcontrol.php">← 管理メニューにもどる</a>

    <?php if ($message !== ''): ?>
        <div class="msg <?php echo $message_type === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo h($message); ?>
        </div>
    <?php endif; ?>

    <div class="guide-box">
        <strong>このページでできること</strong><br>
        クラスごとの担任の先生と、その連絡用メールアドレスを管理します。<br>
        生徒が遅刻届を出すと、ここに登録されたメールアドレスへ通知が届きます。<br>
        ・1人ずつ直すときは「編集」ボタン、まとめて消すときは左の□にチェックを入れて「選択した先生をまとめて削除」を押してください。<br>
        ・まとめて追加するときは下の「CSVで一括追加」をお使いください。<br>
        ・<strong>メールアドレスは1人を見分ける目印</strong>のため、編集では変更できません。変えたいときは一度削除して、追加し直してください。
    </div>

    <!-- ============ 一覧表 ============ -->
    <h2 class="management-subheader">登録されている担任の先生の一覧</h2>

    <form method="get" action="edit_teacher_info.php" class="filter-form">
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
        <label class="filter-label">名前：</label>
        <input type="text" name="name" value="<?php echo h($filter_name); ?>" placeholder="例：田中" class="filter-input">
        <button type="submit" class="btn btn-add" style="padding:10px 24px;font-size:18px">絞り込む</button>
        <?php if ($filter_grade !== null || $filter_class !== null || $filter_name !== ''): ?>
            <a href="edit_teacher_info.php" class="btn btn-cancel" style="padding:10px 16px;font-size:18px">リセット</a>
        <?php endif; ?>
    </form>

    <p class="management-description"><?php echo count($teachers); ?> 人が表示されています。</p>

    <div style="margin-bottom:10px">
        <button type="button" class="btn btn-delete" id="bulk-delete-btn" style="font-size:18px;padding:12px 28px">
            ☑ 選択した先生をまとめて削除する
        </button>
        <span id="checked-count" style="margin-left:14px;font-size:16px;color:#555"></span>
    </div>

    <form method="post" action="edit_teacher_info.php" id="bulk-delete-form">
        <input type="hidden" name="action" value="delete_bulk">
    </form>

    <table class="data-table">
        <tr>
            <th><label title="全て選択／全て解除">
                <input type="checkbox" id="check-all" style="transform:scale(1.5)">
            </label></th>
            <th>学年</th>
            <th>組</th>
            <th>名前</th>
            <th>メールアドレス</th>
            <th>操作</th>
        </tr>
        <?php foreach ($teachers as $t): ?>
            <?php if ($edit_mail === $t['c_teacher_mail']): ?>
                <!-- 編集モードの行 -->
                <tr class="editing-row">
                    <form method="post" action="edit_teacher_info.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="c_teacher_mail" value="<?php echo h($t['c_teacher_mail']); ?>">
                        <td></td>
                        <td><input type="number" name="grade" value="<?php echo h($t['grade']); ?>" style="width:60px" required></td>
                        <td><input type="number" name="class" value="<?php echo h($t['class']); ?>" style="width:60px" required></td>
                        <td><input type="text"   name="name"  value="<?php echo h($t['name']); ?>" required></td>
                        <td><?php echo h($t['c_teacher_mail']); ?>（変更不可）</td>
                        <td>
                            <button type="submit" class="btn btn-edit">保存</button>
                            <a class="btn btn-cancel" href="edit_teacher_info.php">やめる</a>
                        </td>
                    </form>
                </tr>
            <?php else: ?>
                <tr>
                    <td><input type="checkbox" class="row-check" value="<?php echo h($t['c_teacher_mail']); ?>" style="transform:scale(1.5)"></td>
                    <td><?php echo h($t['grade']); ?></td>
                    <td><?php echo h($t['class']); ?></td>
                    <td><?php echo h($t['name']); ?></td>
                    <td><?php echo h($t['c_teacher_mail']); ?></td>
                    <td>
                        <a class="btn btn-edit" href="edit_teacher_info.php?edit=<?php echo rawurlencode($t['c_teacher_mail']); ?>">編集</a>
                        <form method="post" action="edit_teacher_info.php" style="display:inline"
                              onsubmit="return confirm('<?php echo h($t['name']); ?> 先生を本当に削除しますか？\nこの操作は元にもどせません。');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="c_teacher_mail" value="<?php echo h($t['c_teacher_mail']); ?>">
                            <button type="submit" class="btn btn-delete">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$teachers): ?>
            <tr><td colspan="6">まだ担任の先生が登録されていません。</td></tr>
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
            countSpan.textContent = n > 0 ? n + ' 人を選択中' : '';
        }

        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (cb) { cb.checked = checkAll.checked; });
            updateCount();
        });

        document.querySelectorAll('.row-check').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var all = document.querySelectorAll('.row-check');
                checkAll.checked = Array.from(all).every(function (c) { return c.checked; });
                checkAll.indeterminate = !checkAll.checked && Array.from(all).some(function (c) { return c.checked; });
                updateCount();
            });
        });

        bulkBtn.addEventListener('click', function () {
            var checked = getChecked();
            if (checked.length === 0) {
                alert('削除したい先生の左にある□にチェックを入れてから押してください。');
                return;
            }
            if (!confirm(checked.length + ' 人の先生を削除します。\nこの操作は元にもどせません。よろしいですか？')) {
                return;
            }
            bulkForm.querySelectorAll('input[name="mails[]"]').forEach(function (el) { el.remove(); });
            checked.forEach(function (cb) {
                var hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'mails[]';
                hidden.value = cb.value;
                bulkForm.appendChild(hidden);
            });
            bulkForm.submit();
        });
    })();
    </script>

    <!-- ============ 新規追加 ============ -->
    <h2 class="management-subheader">担任の先生を1人ずつ追加する</h2>
    <div class="form-card">
        <p class="management-description">下の欄をすべて入力して、「この先生を追加する」を押してください。</p>
        <form method="post" action="edit_teacher_info.php">
            <input type="hidden" name="action" value="add">
            <div class="form-row"><label>学年</label> <input type="number" name="grade" required></div>
            <div class="form-row"><label>組</label>   <input type="number" name="class" required></div>
            <div class="form-row"><label>名前</label> <input type="text" name="name" required></div>
            <div class="form-row"><label>メールアドレス</label> <input type="email" name="c_teacher_mail" required></div>
            <button type="submit" class="btn btn-add">この先生を追加する</button>
        </form>
    </div>

    <!-- ============ CSV一括追加 ============ -->
    <h2 class="management-subheader">CSVファイルでまとめて追加する</h2>
    <div class="form-card">
        <div class="guide-box">
            <strong>CSVファイルの作り方</strong><br>
            ExcelやGoogleスプレッドシートで下のように入力し、「CSV形式」で保存してください。<br>
            1行目の見出しは、あってもなくても大丈夫です。<br>
            すでにあるメールアドレスと同じものは、上書きで更新されます。
        </div>
        <p class="management-description">並び順は次のとおりです（左から）:</p>
        <div class="csv-sample">
            学年, 組, 名前, メールアドレス<br>
            1, 2, 佐藤一郎, sato@example.com<br>
            2, 1, 高橋花子, takahashi@example.com
        </div>
        <br>
        <form method="post" action="edit_teacher_info.php" enctype="multipart/form-data">
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
