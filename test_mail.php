<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);

$dotenv->load();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];

        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($_ENV['MAIL_USERNAME'], '遅刻届システム');
        $mail->addAddress($_ENV['MAIL_ADDRESS']);

        $mail->Subject = 'PHPMailer送信テスト';
        $mail->Body = 'これはボタンを押して送信したテストメールです。';

        $mail->send();

        $message = '送信成功';
    } catch (Exception $e) {
        $message = '送信失敗: ' . $mail->ErrorInfo;
    }
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>メール送信テスト</title>
</head>
<body>
    <h1>メール送信テスト</h1>

    <?php if ($message !== ''): ?>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="post">
        <button type="submit">テストメールを送信する</button>
    </form>
</body>
</html>