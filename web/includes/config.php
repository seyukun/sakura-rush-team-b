<?php
/**
 * config.php - セッション設定と認証設定
 * セキュリティ強化版：PHPセッションベースの認証
 */

// セッション設定の開始（必ず最初に実行）
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);  
ini_set('session.cookie_samesite', 'Strict');
session_start();

// ベースURLを動的に判定して定義
$webRootFs = str_replace('\\', '/', dirname(__DIR__));
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$relativePath = str_replace($webRootFs, '', $scriptFilename);
$baseUrl = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($relativePath));
define('BASE_URL', rtrim($baseUrl, '/'));

// .envファイルを読み込む簡易パーサー
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // コメントをスキップ
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'"); // クォーテーションや空白を削除
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// MySQLi接続設定 (環境変数から取得)
$db_host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'teamb';

// MySQLi接続
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// 接続エラーチェック
if ($mysqli->connect_error) {
    error_log('Database connection error: ' . $mysqli->connect_error);
    die(json_encode(['success' => false, 'message' => 'データベース接続エラーが発生しました']));
}

// 文字セット設定
$mysqli->set_charset('utf8mb4');

// レスポンスヘッダー設定（CORS対応）
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CSRF トークン生成関数
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF トークン検証関数
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// レスポンス関数
function sendJson($data, $statusCode = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
