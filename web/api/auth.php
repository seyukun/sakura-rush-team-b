<?php
/**
 * api/auth.php - ログイン/ログアウト・アカウント処理
 * RESTful APIエンドポイント
 */

require_once '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

function getUsers() {
    $file = __DIR__ . '/../data/users.json';
    if (!file_exists($file)) return [];
    $data = file_get_contents($file);
    return json_decode($data, true) ?: [];
}

function saveUsers($users) {
    $file = __DIR__ . '/../data/users.json';
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

// ログイン処理
if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['password'])) {
        sendJson(['success' => false, 'message' => 'ユーザ名とパスワードは必須です'], 400);
    }

    $username = trim($input['username']);
    $password = $input['password'];
    $users = getUsers();

    // デモユーザーまたはJSONのユーザー認証
    $hash = isset($users[$username]) ? $users[$username]['password'] : (isset($DEMO_USERS[$username]) ? $DEMO_USERS[$username] : '');
    
    if ($hash && password_verify($password, $hash)) {
        $_SESSION['user_name'] = $username;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        sendJson([
            'success' => true,
            'message' => 'ログインに成功しました',
            'username' => $username
        ], 200);
    } else {
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

// アカウント作成処理
if ($method === 'POST' && $action === 'register') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['password']) || empty($input['confirm_password'])) {
        sendJson(['success' => false, 'message' => 'すべての項目を入力してください'], 400);
    }
    if ($input['password'] !== $input['confirm_password']) {
        sendJson(['success' => false, 'message' => 'パスワードが一致しません'], 400);
    }
    
    $username = trim($input['username']);
    $users = getUsers();
    
    if (isset($users[$username]) || isset($DEMO_USERS[$username])) {
        sendJson(['success' => false, 'message' => '既に存在するユーザ名です'], 400);
    }

    $users[$username] = [
        'password' => password_hash($input['password'], PASSWORD_BCRYPT),
        'created_at' => time(),
        'reset_token' => null
    ];
    saveUsers($users);

    sendJson(['success' => true, 'message' => 'アカウントを作成しました'], 200);
}

// パスワードリセット - トークン発行
if ($method === 'POST' && $action === 'reset-request') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username'])) {
        sendJson(['success' => false, 'message' => 'ユーザ名を入力してください'], 400);
    }
    
    $username = trim($input['username']);
    $users = getUsers();
    
    if (isset($users[$username])) {
        $token = bin2hex(random_bytes(16));
        $users[$username]['reset_token'] = $token;
        saveUsers($users);
        
        // デモ環境のため、画面にトークンを返す
        sendJson(['success' => true, 'message' => 'トークンを発行しました', 'token' => $token], 200);
    } else {
        sendJson(['success' => false, 'message' => 'ユーザが存在しません'], 400);
    }
}

// パスワードリセット - パスワード再設定
if ($method === 'POST' && $action === 'reset-password') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['token']) || empty($input['new_password']) || empty($input['confirm_password'])) {
        sendJson(['success' => false, 'message' => 'すべての項目を入力してください'], 400);
    }
    if ($input['new_password'] !== $input['confirm_password']) {
        sendJson(['success' => false, 'message' => '新しいパスワードが一致しません'], 400);
    }

    $username = trim($input['username']);
    $users = getUsers();
    
    if (isset($users[$username]) && $users[$username]['reset_token'] === $input['token']) {
        $users[$username]['password'] = password_hash($input['new_password'], PASSWORD_BCRYPT);
        $users[$username]['reset_token'] = null; // トークンを無効化
        saveUsers($users);
        
        sendJson(['success' => true, 'message' => 'パスワードをリセットしました'], 200);
    } else {
        sendJson(['success' => false, 'message' => 'トークンが無効か、ユーザが存在しません'], 400);
    }
}

// パスワード変更処理
if ($method === 'POST' && $action === 'change-password') {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_name'])) {
        sendJson(['success' => false, 'message' => 'ログインが必要です'], 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['current_password']) || empty($input['new_password']) || empty($input['confirm_password'])) {
        sendJson(['success' => false, 'message' => 'すべての項目を入力してください'], 400);
    }
    if ($input['new_password'] !== $input['confirm_password']) {
        sendJson(['success' => false, 'message' => '新しいパスワードが一致しません'], 400);
    }

    $username = $_SESSION['user_name'];
    $users = getUsers();
    $hash = isset($users[$username]) ? $users[$username]['password'] : (isset($DEMO_USERS[$username]) ? $DEMO_USERS[$username] : '');
    
    if ($hash && password_verify($input['current_password'], $hash)) {
        if (isset($users[$username])) {
            $users[$username]['password'] = password_hash($input['new_password'], PASSWORD_BCRYPT);
            saveUsers($users);
            sendJson(['success' => true, 'message' => 'パスワードを変更しました'], 200);
        } else {
            sendJson(['success' => false, 'message' => 'デモユーザのパスワードは変更できません'], 400);
        }
    } else {
        sendJson(['success' => false, 'message' => '現在のパスワードが違います'], 400);
    }
}

// アカウント削除処理
if ($method === 'POST' && $action === 'delete-account') {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_name'])) {
        sendJson(['success' => false, 'message' => 'ログインが必要です'], 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['password'])) {
        sendJson(['success' => false, 'message' => 'パスワードを入力してください'], 400);
    }

    $username = $_SESSION['user_name'];
    $users = getUsers();
    $hash = isset($users[$username]) ? $users[$username]['password'] : (isset($DEMO_USERS[$username]) ? $DEMO_USERS[$username] : '');

    if ($hash && password_verify($input['password'], $hash)) {
        if (isset($users[$username])) {
            unset($users[$username]);
            saveUsers($users);
            $_SESSION = [];
            session_destroy();
            sendJson(['success' => true, 'message' => 'アカウントを削除しました'], 200);
        } else {
            sendJson(['success' => false, 'message' => 'デモユーザのアカウントは削除できません'], 400);
        }
    } else {
        sendJson(['success' => false, 'message' => 'パスワードが違います'], 400);
    }
}

// 無効なリクエスト
sendJson(['success' => false, 'message' => '無効なリクエストです'], 400);
