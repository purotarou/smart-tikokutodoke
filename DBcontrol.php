<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/admin.css">
    <title>Smart遅刻届管理サイト</title>
</head>
<body class="admin">
<h1 class="management-header">Smart遅刻届 管理サイト</h1>
<div class="admin-container">

    <div class="guide-box">
        <strong>ようこそ。このサイトでできること</strong><br>
        パソコンが苦手な方でも使えるように作ってあります。<br>
        下の3つのボタンから、それぞれの情報を「見る・追加する・直す・消す」ことができます。<br>
        分からなくなったら、各ページの上にある黄色い案内をお読みください。
    </div>

    <h2 class="management-subheader">管理メニュー</h2>
    <p class="management-description">管理したい情報のボタンを押してください。</p>

    <div class="management-menu">
        <a class="menu-link" href="edit_student_info.php">
            生徒情報を管理する<br>
            <span style="font-size:16px">（学年・組・名前・遅刻回数など）</span>
        </a>
        <a class="menu-link" href="edit_teacher_info.php">
            担任情報を管理する<br>
            <span style="font-size:16px">（クラス担任の先生と連絡用メールアドレス）</span>
        </a>
        <a class="menu-link" href="edit_late_history.php">
            遅刻履歴を管理する<br>
            <span style="font-size:16px">（生徒が出した遅刻届の記録）</span>
        </a>
    </div>

</div>
</body>
</html>
