<?php
// =============================================================
// 学籍番号がわからない人向けの入力ページ
// 学年・組・出席番号・名前をセレクトボックスで選び、
// student_info と照合して student_id を特定し、form.php へ送ります。
// =============================================================
require __DIR__ . '/db.php';

// 照合に使う全生徒の情報を取得（JS側で連動入力に使います）
$students = $pdo->query(
    "SELECT student_id, grade, class, number, name FROM student_info ORDER BY grade, class, number"
)->fetchAll();

$pdo = null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart遅刻届</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/style.css">
    <script>var STUDENTS = <?php echo json_encode($students, JSON_UNESCAPED_UNICODE); ?>;</script>
    <script src="script.js" defer></script>
</head>
<body>
    <h1 class="reading_instructions">
    下の項目を選んでください</h1>

    <form id="manual-form" class="manual-entry-form" method="post" action="form.php">
        <input type="hidden" name="student_id" id="student-id" value="">

        <div class="manual-row">
            <label for="sel-grade">学年</label>
            <select id="sel-grade"><option value="">選択してください</option></select>
        </div>
        <div class="manual-row">
            <label for="sel-class">組</label>
            <select id="sel-class" disabled><option value="">選択してください</option></select>
        </div>
        <div class="manual-row">
            <label for="sel-number">出席番号</label>
            <select id="sel-number" disabled><option value="">選択してください</option></select>
        </div>
        <div class="manual-row">
            <label for="sel-name">名前</label>
            <select id="sel-name" disabled><option value="">選択してください</option></select>
        </div>

        <button type="submit" id="manual-submit" disabled>この内容で進む</button>
        <a class="manual-back" href="index.html">もどる</a>
    </form>

</body>
</html>
