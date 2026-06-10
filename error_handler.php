<?php
// =============================================================
// Smart遅刻届 共通エラー処理（生徒用画面）
//
// 予期せぬエラーが起きたときに、
//   1) エラー内容を error_history テーブルに記録し、
//   2) おわび画面 error.php へ自動的に移動させます。
//
// 使い方:
//   ・各ページの先頭で require_once __DIR__ . '/error_handler.php'; を読み込む
//   ・想定外の状況では throw new Exception('説明'); するだけでよい
//     （捕捉されなかった例外は下の set_exception_handler が拾い、
//       自動でおわび画面へ流れます）
//   ・自分で捕まえた例外を流したいときは go_to_error_page($msg) を呼ぶ
// =============================================================

// エラー内容を error_history テーブルに記録する。
// ※ 記録自体が失敗しても画面遷移は止めたくないので、
//    ここで起きた例外は内部で握りつぶします。
function record_error_history($message) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=tikokutodoke;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare('INSERT INTO `error_history` (`date`, `error`) VALUES (NOW(), :error)');
        // error カラムは varchar(255) なので、長い場合は切り詰める
        $stmt->bindValue(':error', mb_substr($message, 0, 255));
        $stmt->execute();
    } catch (Throwable $e) {
        // 記録できなくても、利用者にはおわび画面を見せる
    }
}

// おわび画面（error.php）へ移動して処理を終了する
function go_to_error_page($message) {
    record_error_history($message);

    if (!headers_sent()) {
        header('Location: error.php');
    } else {
        // すでに画面出力が始まっている場合は meta タグで移動する
        echo '<meta http-equiv="refresh" content="0; URL=error.php">';
    }
    exit;
}

// どこにも捕まえられなかった例外・エラーを、すべておわび画面へ集約する。
// （個別の try/catch を書き忘れても、最終的にここで受け止めます）
set_exception_handler(function (Throwable $e) {
    go_to_error_page($e->getMessage());
});
