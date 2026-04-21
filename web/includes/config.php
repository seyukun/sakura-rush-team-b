<?php
/**
 * config.php - セッション設定と認証設定
 * セキュリティ強化版：PHPセッションベースの認証
 */

// セッション設定の開始（必ず最初に実行）
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);  // 本番環境ではHTTPSを有効にして1に設定
ini_set('session.cookie_samesite', 'Strict');
session_start();

// ベースURLを動的に判定して定義 (例: /sakura-rush-team-b/web)
$webRootFs = str_replace('\\', '/', dirname(__DIR__));
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$relativePath = str_replace($webRootFs, '', $scriptFilename);
$baseUrl = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($relativePath));
define('BASE_URL', rtrim($baseUrl, '/'));

// デモ用ユーザーデータベース（本番ではデータベースから取得）
// 実運用ではこれを削除し、データベースから認証情報を取得する
$DEMO_USERS = array(
    'admin' => password_hash('admin123', PASSWORD_BCRYPT)
);

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
