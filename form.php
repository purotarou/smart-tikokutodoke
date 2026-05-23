<?php

$student_array = array();
date_default_timezone_set('Asia/Tokyo');
$week = ["日", "月", "火", "水", "木", "金", "土"];
$current_date = date('Y/m/d');
$current_week = $week[date('w')];
$current_time = date('H:i');
$student_id = $_POST['student_id'] ?? $_GET['student_id'] ?? 1;

//DB接続
$dbname = 'mysql:host=localhost;dbname=tikokutodoke';
$username = 'root';
$password = 'password';
try {
    $pdo = new PDO($dbname, $username, $password); 
} catch (PDOException $e) {
    echo 'DB接続エラー:' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_late_count'])) {
    $sql = "UPDATE `tikokutodoke-table` SET `late-count` = `late-count` + 1 WHERE `id` = :student_id;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: end.html');
    exit;
}

// function send() {
//     // 送信処理
//     $stmt = $pdo ->prepare("INSERT INTO `tikokutodoke-table` ( `grade`, `class`, `number`, `name`, `date`, `time`, `late-count`, `reason`) VALUES (:grade, :class, :number, :name, :date, :time, :late_count, :reason)");
//     $stmt->bindParam(':name', $name);
//     $stmt->bindParam(':value', $value);
// }

//DBからデータを取得
$sql = "SELECT `student_id`, `grade`, `class`, `number`, `name`, `late-count` FROM `student-info` WHERE `student_id` = :student_id;";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
$stmt->execute();
$student_array = $stmt;

//DBの接続を閉じる
$pdo = null;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="script.js" defer></script>
</head>
<body class="main-layout">
    <h2>ご確認ください</h2>
    <form method="post" action="form.php">
    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- 表を囲っている枠 -->
    <div class="table-outline">
    <?php foreach($student_array as $student): ?>
        <div class="grade"><?php echo $student["grade"]; ?>年</div>
        <div class="class"><?php echo $student["class"]; ?>組</div>
        <div class="number"><?php echo $student["number"]; ?>番</div>
        <div class="name"><span class="name-indicate">名前:</span><span class="name-actual"><?php echo $student["name"]; ?></span></div>
        <div class="date">
            <?php echo $current_date; ?> 
            (<?php echo $current_week; ?>) 曜日
        </div>
        <div class="time">
            <span>登校時間</span> <span><?php echo $current_time; ?></span>
        </div>  
        <div class="late-count">遅刻回数 <?php echo $student["late-count"] + 1; ?>回</div>
        <div class="slc-inst"><!-- select-instructionの略 -->
            <span class="late-reason">遅刻理由</span><p>(下記から選択してください)</p>
        </div>
        <div class="reason1">
            <div class="reason1-inner">
                <label><input type="checkbox" value="体調不良">体調不良</label>
                <label><input type="checkbox" value="発熱">発熱</label>
                <br>
                <label><input type="checkbox" value="頭痛">頭痛</label>
                <label><input type="checkbox" value="腹痛">腹痛</label>
                <br>
                <label><input type="checkbox" value="嘔吐">嘔吐</label>
                <label><input type="checkbox" value="下痢">下痢</label>
                <label><input type="checkbox" value="めまい">めまい</label>
            </div>
        </div>

        <div class="reason2">
            <div class="reason2-inner">
                <label><input type="checkbox" value="通院">通院</label>
                <label><input type="checkbox" value="家事">家事</label>
                <br>
                <label><input type="checkbox" value="電車の遅れ">電車の遅れ</label> 
                <label><input type="checkbox" value="寝坊">寝坊</label>
                <br>
                <label><input type="checkbox" value="忘れ物">忘れ物</label>
                <label><input type="checkbox" value="バスの遅れ">バスの遅れ</label>
                <br>
                <label><input class="other-reason-check" type="checkbox" value="その他">その他</label>
                <input class="other-reason-text" type="text" placeholder="具体的に記入">
            </div>
        </div>
    <?php endforeach; ?>
    </div>
     <input type="hidden" name="update_late_count" value="1">
     <input class="submit-btn" type="submit" value="提出">
    </form>
</body>
</html>