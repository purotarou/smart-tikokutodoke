<?php
// =============================================================
// 生徒情報（student_info）の管理ページ
// 一覧の表示・新規追加・編集・削除・CSVでの一括追加ができます。
// =============================================================
require __DIR__ . '/db.php';

$message = '';        // 画面に出すお知らせ文
$message_type = '';   // success（成功） か error（失敗）

// この表の列の並び順（CSVの並びと合わせています）
// student_id, grade, class, number, name, late_count

// -------------------------------------------------------------
// ボタンが押されたとき（POST送信）の処理を振り分けます
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // ---- 新規追加 ----
        if ($action === 'add') {
            $sql = "INSERT INTO student_info (student_id, grade, class, number, name, late_count)
                    VALUES (:student_id, :grade, :class, :number, :name, :late_count)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':student_id', (int)$_POST['student_id'], PDO::PARAM_INT);
            $stmt->bindValue(':grade',      (int)$_POST['grade'], PDO::PARAM_INT);
            $stmt->bindValue(':class',      (int)$_POST['class'], PDO::PARAM_INT);
            $stmt->bindValue(':number',     (int)$_POST['number'], PDO::PARAM_INT);
            $stmt->bindValue(':name',       trim($_POST['name']));
            $stmt->bindValue(':late_count', (int)$_POST['late_count'], PDO::PARAM_INT);
            $stmt->execute();
            $message = '生徒を1人追加しました。';
            $message_type = 'success';

        // ---- 編集を保存 ----
        } elseif ($action === 'update') {
            $sql = "UPDATE student_info
                    SET grade = :grade, class = :class, number = :number,
                        name = :name, late_count = :late_count
                    WHERE student_id = :student_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':grade',      (int)$_POST['grade'], PDO::PARAM_INT);
            $stmt->bindValue(':class',      (int)$_POST['class'], PDO::PARAM_INT);
            $stmt->bindValue(':number',     (int)$_POST['number'], PDO::PARAM_INT);
            $stmt->bindValue(':name',       trim($_POST['name']));
            $stmt->bindValue(':late_count', (int)$_POST['late_count'], PDO::PARAM_INT);
            $stmt->bindValue(':student_id', (int)$_POST['student_id'], PDO::PARAM_INT);
            $stmt->execute();
            $message = '生徒情報を変更しました。';
            $message_type = 'success';

        // ---- 削除 ----
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM student_info WHERE student_id = :student_id");
            $stmt->bindValue(':student_id', (int)$_POST['student_id'], PDO::PARAM_INT);
            $stmt->execute();
            $message = '生徒を1人削除しました。';
            $message_type = 'success';

        // ---- 一括削除 ----
        } elseif ($action === 'delete_bulk') {
            $ids = array_filter(array_map('intval', $_POST['ids'] ?? []), fn($v) => $v > 0);
            if (empty($ids)) {
                throw new Exception('削除する生徒にチェックが入っていません。');
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM student_info WHERE student_id IN ($placeholders)");
            $stmt->execute(array_values($ids));
            $count = $stmt->rowCount();
            $message = "{$count} 人の生徒を削除しました。";
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

            // 1行目が見出し（文字）の場合は読み飛ばす
            if (isset($rows[0][0]) && !is_numeric(trim($rows[0][0]))) {
                array_shift($rows);
            }

            $sql = "INSERT INTO student_info (student_id, grade, class, number, name, late_count)
                    VALUES (:student_id, :grade, :class, :number, :name, :late_count)
                    ON DUPLICATE KEY UPDATE
                        grade = VALUES(grade), class = VALUES(class),
                        number = VALUES(number), name = VALUES(name),
                        late_count = VALUES(late_count)";
            $stmt = $pdo->prepare($sql);

            $count = 0;
            foreach ($rows as $i => $row) {
                if (count($row) < 5) {
                    throw new Exception(($i + 1) . '行目の項目数が足りません。（6項目必要です）');
                }
                $stmt->bindValue(':student_id', (int)$row[0], PDO::PARAM_INT);
                $stmt->bindValue(':grade',      (int)$row[1], PDO::PARAM_INT);
                $stmt->bindValue(':class',      (int)$row[2], PDO::PARAM_INT);
                $stmt->bindValue(':number',     (int)$row[3], PDO::PARAM_INT);
                $stmt->bindValue(':name',       trim($row[4]));
                $stmt->bindValue(':late_count', isset($row[5]) ? (int)$row[5] : 0, PDO::PARAM_INT);
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

    // 二重送信を防ぐため、処理後はGETで開き直す
    $_SESSION_MSG = null; // （セッションは使わずURLに載せます）
    header('Location: edit_student_info.php?msg=' . rawurlencode($message) . '&type=' . $message_type);
    exit;
}

// リダイレクト後のお知らせを受け取る
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = $_GET['type'] ?? '';
}

// 編集モードかどうか（?edit=生徒ID）
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// 一覧データを取得
$students = $pdo->query("SELECT * FROM student_info ORDER BY grade, class, number")->fetchAll();

$pdo = null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/admin.css">
    <title>生徒情報の管理 | Smart遅刻届</title>
</head>
<body class="admin">
<h1 class="management-header">生徒情報の管理</h1>
<div class="admin-container">

    <a class="back-link" href="DBcontrol.php">← 管理メニューにもどる</a>

    <?php if ($message !== ''): ?>
        <div class="msg <?php echo $message_type === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo h($message); ?>
        </div>
    <?php endif; ?>

    <div class="guide-box">
        <strong>このページでできること</strong><br>
        生徒の名前や学年などの一覧を見たり、追加・修正・削除ができます。<br>
        ・1人ずつ直すときは、表の右にある「編集」ボタンを押してください。<br>
        ・まとめて消すときは左の□にチェックを入れて「選択した生徒をまとめて削除」を押してください。<br>
        ・たくさんまとめて追加するときは、いちばん下の「CSVで一括追加」をお使いください。
    </div>

    <!-- ============ 一覧表 ============ -->
    <h2 class="management-subheader">登録されている生徒の一覧</h2>
    <p class="management-description">現在 <?php echo count($students); ?> 人が登録されています。</p>

    <div style="margin-bottom:10px">
        <button type="button" class="btn btn-delete" id="bulk-delete-btn" style="font-size:18px;padding:12px 28px">
            ☑ 選択した生徒をまとめて削除する
        </button>
        <span id="checked-count" style="margin-left:14px;font-size:16px;color:#555"></span>
    </div>

    <form method="post" action="edit_student_info.php" id="bulk-delete-form">
        <input type="hidden" name="action" value="delete_bulk">
    </form>

    <table class="data-table">
        <tr>
            <th><label title="全て選択／全て解除">
                <input type="checkbox" id="check-all" style="transform:scale(1.5)">
            </label></th>
            <th>生徒ID</th>
            <th>学年</th>
            <th>組</th>
            <th>出席番号</th>
            <th>名前</th>
            <th>遅刻回数</th>
            <th>操作</th>
        </tr>
        <?php foreach ($students as $s): ?>
            <?php if ($edit_id === (int)$s['student_id']): ?>
                <!-- 編集モードの行（入力できる状態） -->
                <tr class="editing-row">
                    <form method="post" action="edit_student_info.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="student_id" value="<?php echo h($s['student_id']); ?>">
                        <td></td>
                        <td><?php echo h($s['student_id']); ?></td>
                        <td><input type="number" name="grade"  value="<?php echo h($s['grade']); ?>" style="width:60px" required></td>
                        <td><input type="number" name="class"  value="<?php echo h($s['class']); ?>" style="width:60px" required></td>
                        <td><input type="number" name="number" value="<?php echo h($s['number']); ?>" style="width:60px" required></td>
                        <td><input type="text"   name="name"   value="<?php echo h($s['name']); ?>" required></td>
                        <td><input type="number" name="late_count" value="<?php echo h($s['late_count']); ?>" style="width:60px" required></td>
                        <td>
                            <button type="submit" class="btn btn-edit">保存</button>
                            <a class="btn btn-cancel" href="edit_student_info.php">やめる</a>
                        </td>
                    </form>
                </tr>
            <?php else: ?>
                <!-- ふつうの表示の行 -->
                <tr>
                    <td><input type="checkbox" class="row-check" value="<?php echo h($s['student_id']); ?>" style="transform:scale(1.5)"></td>
                    <td><?php echo h($s['student_id']); ?></td>
                    <td><?php echo h($s['grade']); ?></td>
                    <td><?php echo h($s['class']); ?></td>
                    <td><?php echo h($s['number']); ?></td>
                    <td><?php echo h($s['name']); ?></td>
                    <td><?php echo h($s['late_count']); ?></td>
                    <td>
                        <a class="btn btn-edit" href="edit_student_info.php?edit=<?php echo h($s['student_id']); ?>">編集</a>
                        <form method="post" action="edit_student_info.php" style="display:inline"
                              onsubmit="return confirm('<?php echo h($s['name']); ?> さんを本当に削除しますか？\nこの操作は元にもどせません。');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="student_id" value="<?php echo h($s['student_id']); ?>">
                            <button type="submit" class="btn btn-delete">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$students): ?>
            <tr><td colspan="8">まだ生徒が登録されていません。</td></tr>
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
                alert('削除したい生徒の左にある□にチェックを入れてから押してください。');
                return;
            }
            if (!confirm(checked.length + ' 人の生徒を削除します。\nこの操作は元にもどせません。よろしいですか？')) {
                return;
            }
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
    <h2 class="management-subheader">生徒を1人ずつ追加する</h2>
    <div class="form-card">
        <p class="management-description">下の欄をすべて入力して、いちばん下の「この生徒を追加する」を押してください。</p>
        <form method="post" action="edit_student_info.php">
            <input type="hidden" name="action" value="add">
            <div class="form-row"><label>生徒ID</label>   <input type="number" name="student_id" required></div>
            <div class="form-row"><label>学年</label>     <input type="number" name="grade" required></div>
            <div class="form-row"><label>組</label>       <input type="number" name="class" required></div>
            <div class="form-row"><label>出席番号</label> <input type="number" name="number" required></div>
            <div class="form-row"><label>名前</label>     <input type="text"   name="name" required></div>
            <div class="form-row"><label>遅刻回数</label> <input type="number" name="late_count" value="0" required></div>
            <button type="submit" class="btn btn-add">この生徒を追加する</button>
        </form>
    </div>

    <!-- ============ CSV一括追加 ============ -->
    <h2 class="management-subheader">CSVファイルでまとめて追加する</h2>
    <div class="form-card">
        <div class="guide-box">
            <strong>CSVファイルの作り方</strong><br>
            ExcelやGoogleスプレッドシートで下のように入力し、「CSV形式」で保存してください。<br>
            1行目の見出し（生徒ID, 学年…）は、あってもなくても大丈夫です。<br>
            すでにある生徒ID（左端）と同じ番号は、上書きで更新されます。
        </div>
        <p class="management-description">並び順は次のとおりです（左から）:</p>
        <div class="csv-sample">
            生徒ID, 学年, 組, 出席番号, 名前, 遅刻回数<br>
            3, 1, 1, 3, 田中三郎, 0<br>
            4, 1, 2, 1, 鈴木花子, 0
        </div>
        <br>
        <form method="post" action="edit_student_info.php" enctype="multipart/form-data">
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
