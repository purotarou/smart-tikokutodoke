<?php
//envファイルの読み込み
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

//変数の宣言
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

//メール送信の関数を定義
function create_mailer() {
    if (empty($_ENV['MAIL_USERNAME']) || empty($_ENV['MAIL_PASSWORD'])) {
        throw new Exception('メール送信用の環境変数が設定されていません。');
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($_ENV['MAIL_USERNAME'], '遅刻届システム');

    return $mail;
}

function send_mail($to /* 送信先メールアドレス */, $to_name /* 相手の名前 */, $subject/*件名*/, $body /* メール本文 */) {
    if (empty($to)) {
        return;
    }
    
    $mail = create_mailer();//,メール関数を呼び出す
    $mail->addAddress($to, $to_name);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->send();
}


function get_class_teacher($pdo, $student) {
    $sql = "SELECT `name`, `c_teacher_mail` FROM `class_teacher` WHERE `grade` = :grade AND `class` = :class LIMIT 1;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':grade', $student['grade'], PDO::PARAM_INT);
    $stmt->bindValue(':class', $student['class'], PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function build_late_mail_body($student, $date, $week, $time, $late_count, $reason_text, $extra_lines = []) {
    $lines = [
        '遅刻届が提出されました。',
        '',
        '学年: ' . $student['grade'] . '年',
        '学組: ' . $student['class'] . '組',
        '出席番号: ' . $student['number'] . '番',
        '氏名: ' . $student['name'],
        '登校日時: ' . $date . '（' . $week . '） ' . $time,
        '遅刻回数: ' . $late_count . '回',
        '遅刻理由: ' . $reason_text,
    ];

    if (!empty($extra_lines)) {
        $lines[] = '';
        $lines = array_merge($lines, $extra_lines);
    }

    return implode("\n", $lines);
}

function send_teacher_notifications($pdo, $student, $date, $week, $time, $late_count, $reason_text) {
    $class_teacher = get_class_teacher($pdo, $student);

    if (!$class_teacher) {
        throw new Exception('クラス担任のメールアドレスが見つかりません。');
    }

    $subject = '【遅刻届】' . $student['name'] . 'さんの遅刻届が提出されました';
    $body = build_late_mail_body($student, $date, $week, $time, $late_count, $reason_text);
    send_mail($class_teacher['c_teacher_mail'], $class_teacher['name'], $subject, $body);

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
    $stmt = $pdo ->prepare("INSERT INTO `lateness_history` ( `grade`, `class`, `number`, `name`, `date`, `week`, `time`, `late_count`, `reason`) VALUES (:grade, :class, :number, :name, :date, :week, :time, :late_count, :reason)");
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

    return [
        'late_count' => $late_count,
        'reason_text' => implode('、', $reasons),
    ];
}

//提出ボタンに対応する関数send_ok()を定義
function send_ok($pdo, $student, $date, $week, $time) {
    if (!$student) {
        return "学生情報が見つかりません。";
    }

    if(empty($_POST['reason'])){
        return "遅刻理由を選択してください。";
    }

    return send($pdo, $student, $date, $week, $time);
}

//DBからデータを取得
$sql = "SELECT `student_id`, `grade`, `class`, `number`, `name`, `late_count` FROM `student_info` WHERE `student_id` = :student_id;";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_array = $student ? [$student] : [];

// 遅刻届の提出があった場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        $send_result = send_ok($pdo, $student, $date, $week, $time);

        if (is_array($send_result)) {
            $sql = "UPDATE `student_info` SET `late_count` = `late_count` + 1 WHERE `student_id` = :student_id;";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            send_teacher_notifications($pdo, $student, $date, $week, $time, $send_result['late_count'], $send_result['reason_text']);

            header('Location: end.html');
            exit;
        } else {
            $error_message = $send_result;
        }
    } catch (PDOException $e) {
        $error_message = 'DB処理エラー:' . $e->getMessage();
    } catch (Exception $e) {
        $error_message = 'メール送信エラー:' . $e->getMessage();
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
    <title>Smart遅刻届</title>
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
                <input class="other-reason-text" type="text" name="other_reason" placeholder="その他の記入欄">
            </div>
        </div>
    <?php endforeach; ?>
    </div>
     <input class="submit-btn" type="submit" name="submit" value="提出">
    </form>
</body>
</html>
