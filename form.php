<?php

$student_array = array();
date_default_timezone_set('Asia/Tokyo');
$week = ["日", "月", "火", "水", "木", "金", "土"];
$date = date('Y/m/d');
$week = $week[date('w')];
$time = date('H:i');
$student_id = $_POST['student_id'] ?? $_GET['student_id'] ?? 1;
$error_message = '';
    
//DB接続
$dbname = 'mysql:host=localhost;dbname=tikokutodoke';
$username = 'root';
$password = 'password';
try {
    $pdo = new PDO($dbname, $username, $password); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit('DB接続エラー:' . $e->getMessage());
}

// 遅刻届の内容をDBに送信する関数send()を定義
function send($pdo, $student, $date, $week, $time) {

    // 遅刻回数の取得
    $late_count = $student['late_count'] + 1;
    // 遅刻理由の取得
    $reasons = $_POST['reason'];

    // その他の理由が入力されている場合は、理由の配列に追加
    if (!empty($_POST['other_reason'])) {
        $reasons[] = $_POST['other_reason'];
    }

    // 送信処理
    $stmt = $pdo ->prepare("INSERT INTO `lateness-history` ( `grade`, `class`, `number`, `name`, `date`, `week`, `time`, `late_count`, `reason`) VALUES (:grade, :class, :number, :name, :date, :week, :time, :late_count, :reason)");
    $stmt->bindValue(':grade', $student['grade'], PDO::PARAM_INT);
    $stmt->bindValue(':class', $student['class'], PDO::PARAM_INT);
    $stmt->bindValue(':number', $student['number'], PDO::PARAM_INT);
    $stmt->bindValue(':name', $student['name']);
    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':week', $week);
    $stmt->bindValue(':time', $time);
    $stmt->bindValue(':late_count', $late_count, PDO::PARAM_INT);
    $stmt->bindValue(':reason', implode('、', $reasons));

    $stmt->execute();
}

//提出ボタンに対応する関数send_ok()を定義
function send_ok($pdo, $student, $date, $week, $time) {
    if (!$student) {
        return "学生情報が見つかりません。";
    }

    if(empty($_POST['reason'])){
        return "遅刻理由を選択してください。";
    }

    send($pdo, $student, $date, $week, $time);
    return '';
}

//DBからデータを取得
$sql = "SELECT `student_id`, `grade`, `class`, `number`, `name`, `late_count` FROM `student-info` WHERE `student_id` = :student_id;";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_array = $student ? [$student] : [];

// 遅刻届の提出があった場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        $error_message = send_ok($pdo, $student, $date, $week, $time);

        if ($error_message === '') {
            $sql = "UPDATE `student-info` SET `late_count` = `late_count` + 1 WHERE `student_id` = :student_id;";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            header('Location: end.html');
            exit;
        }
    } catch (PDOException $e) {
        $error_message = 'DB処理エラー:' . $e->getMessage();
    }
}

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
    <?php if ($error_message !== ''): ?>
        <p class="reason-select-error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
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
            <?php echo $date; ?> 
            (<?php echo $week; ?>) 曜日
        </div>
        <div class="time">
            <span>登校時間</span> <span><?php echo $time; ?></span>
        </div>  
        <div class="late_count">遅刻回数 <?php echo $student["late_count"] + 1; ?>回</div>
        <div class="slc-inst"><!-- select-instructionの略 -->
            <span class="late-reason">遅刻理由</span><p>(下記から選択してください)</p>
        </div>
        <div class="reason1">
            <div class="reason1-inner">
                <label><input type="checkbox" name="reason[]" value="体調不良">体調不良</label>
                <label><input type="checkbox" name="reason[]" value="発熱">発熱</label>
                <br>
                <label><input type="checkbox" name="reason[]" value="頭痛">頭痛</label>
                <label><input type="checkbox" name="reason[]" value="腹痛">腹痛</label>
                <br>
                <label><input type="checkbox" name="reason[]" value="嘔吐">嘔吐</label>
                <label><input type="checkbox" name="reason[]" value="下痢">下痢</label>
                <label><input type="checkbox" name="reason[]" value="めまい">めまい</label>
            </div>
        </div>

        <div class="reason2">
            <div class="reason2-inner">
                <label><input type="checkbox" name="reason[]" value="通院">通院</label>
                <label><input type="checkbox" name="reason[]" value="家事">家事</label>
                <br>
                <label><input type="checkbox" name="reason[]" value="電車の遅れ">電車の遅れ</label> 
                <label><input type="checkbox" name="reason[]" value="寝坊">寝坊</label>
                <br>
                <label><input type="checkbox" name="reason[]" value="忘れ物">忘れ物</label>
                <label><input type="checkbox" name="reason[]" value="バスの遅れ">バスの遅れ</label>
                <br>
                <label><input class="other-reason-check" type="checkbox" name="reason[]" value="その他">その他</label>
                <input class="other-reason-text" type="text" name="other_reason" placeholder="具体的に記入">
            </div>
        </div>
    <?php endforeach; ?>
    </div>
     <input class="submit-btn" type="submit" name="submit" value="提出">
    </form>
</body>
</html>
