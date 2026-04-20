<?php
/**
 * api/auth.php - ログイン/ログアウト処理
 * RESTful APIエンドポイント
 */

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ログイン処理
if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 入力値の検証
    if (empty($input['username']) || empty($input['password'])) {
        sendJson(['success' => false, 'message' => 'ユーザ名とパスワードは必須です'], 400);
    }

    $username = trim($input['username']);
    $password = $input['password'];

    // デモユーザーの認証（本番ではデータベースから取得）
    if (isset($DEMO_USERS[$username]) && password_verify($password, $DEMO_USERS[$username])) {
        // セッションにユーザー情報を保存
        $_SESSION['user_name'] = $username;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        sendJson([
            'success' => true,
            'message' => 'ログインに成功しました',
            'username' => $username
        ], 200);
    } else {
        // セキュリティ: 同じエラーメッセージを返す
        sendJson([
            'success' => false,
            'message' => 'ユーザ名またはパスワードが違います'
        ], 401);
    }
}

// ログアウト処理
if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    session_destroy();
    sendJson([
        'success' => true,
        'message' => 'ログアウトしました'
    ], 200);
}

// セッション状態確認
if ($method === 'GET' && $action === 'status') {
    if (!empty($_SESSION['logged_in'])) {
        sendJson([
            'success' => true,
            'logged_in' => true,
            'username' => $_SESSION['user_name']
        ], 200);
    } else {
        sendJson([
            'success' => false,
            'logged_in' => false
        ], 401);
    }
}

// 無効なリクエスト
sendJson(['success' => false, 'message' => '無効なリクエストです'], 400);
