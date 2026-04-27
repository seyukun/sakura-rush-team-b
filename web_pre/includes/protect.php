<?php
/**
 * protect.php - ページアクセス保護スクリプト
 * ダッシュボードなど保護されたページに include してください
 */

require_once __DIR__ . '/config.php';

// ログイン状態の確認
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_name'])) {
    // ログインしていない場合は JSON で 401 を返す
    if (!empty($_GET['ajax']) || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'ログインが必要です',
            'redirect' => BASE_URL . '/index.php'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // 通常のリクエストはリダイレクト
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// セッション タイムアウト設定（24時間）
$timeout = 24 * 60 * 60;
if (time() - $_SESSION['login_time'] > $timeout) {
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// セッションの最終アクセス時刻を更新
$_SESSION['login_time'] = time();
