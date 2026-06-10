<?php
// =============================================================
// データベースに接続するための共通ファイルです。
// 各管理ページの先頭で require 'db.php'; と書いて読み込みます。
// 接続情報を変えたいときは、この下の3行だけを直してください。
// =============================================================

$dbname   = 'mysql:host=localhost;dbname=tikokutodoke;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dbname, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('データベースに接続できませんでした。XAMPPのMySQLが起動しているか確認してください。（詳細: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '）');
}

// -------------------------------------------------------------
// 画面に文字を安全に表示するための補助関数です。
// （タグなどが入っていても文字として表示されます）
// -------------------------------------------------------------
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// -------------------------------------------------------------
// アップロードされたCSVを1行ずつの配列にして返します。
// ・Excelで保存したCSV（Shift-JIS）も、メモ帳のCSV（UTF-8）も
//   自動で見分けて読み込みます。
// ・先頭の空行や、項目名だけの見出し行はそのまま返すので、
//   呼び出し側で必要に応じて読み飛ばしてください。
// -------------------------------------------------------------
function read_csv_file($tmp_path) {
    $content = file_get_contents($tmp_path);
    if ($content === false) {
        return [];
    }

    // 文字コードを判定してUTF-8に統一する
    $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-win', 'SJIS', 'EUC-JP'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    // 先頭にBOM（目に見えない記号）が付いていたら取り除く
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
        if ($line === '') {
            continue; // 空行は飛ばす
        }
        $rows[] = str_getcsv($line);
    }
    return $rows;
}
