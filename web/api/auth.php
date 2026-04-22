<?php
/**
 * api/auth.php - ログイン/ログアウト・アカウント処理
 * RESTful APIエンドポイント
 */

require_once '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ログイン処理
if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['password'])) {
        sendJson(['success' => false, 'message' => 'ユーザ名とパスワードは必須です'], 400);
    }

    $username = trim($input['username']);
    $password = $input['password'];

    // データベースからユーザー情報を取得
    $stmt = $mysqli->prepare('SELECT id, username, password FROM users WHERE username = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendJson(['success' => false, 'message' => 'ユーザ名またはパスワードが違います'], 401);
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // パスワード検証
    if (!password_verify($password, $user['password'])) {
        sendJson(['success' => false, 'message' => 'ユーザ名またはパスワードが違います'], 401);
    }

    // セッション設定
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    sendJson([
        'success' => true,
        'message' => 'ログインに成功しました',
        'username' => $user['username']
    ], 200);
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
    if (empty($input['email'])) {
        sendJson(['success' => false, 'message' => 'メールアドレスを入力してください'], 400);
    }

    $username = trim($input['username']);
    $email = trim($input['email']);
    $password = password_hash($input['password'], PASSWORD_BCRYPT);

    // ユーザー名の存在確認
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        sendJson(['success' => false, 'message' => '既に存在するユーザ名またはメールアドレスです'], 400);
    }
    $stmt->close();

    // ユーザーを新規作成
    $stmt = $mysqli->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('sss', $username, $email, $password);
    
    if ($stmt->execute()) {
        $stmt->close();
        sendJson(['success' => true, 'message' => 'アカウントを作成しました'], 200);
    } else {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'アカウント作成に失敗しました'], 500);
    }
}

// パスワードリセット - トークン発行
if ($method === 'POST' && $action === 'reset-request') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username'])) {
        sendJson(['success' => false, 'message' => 'ユーザ名を入力してください'], 400);
    }
    
    $username = trim($input['username']);

    // ユーザーが存在するか確認
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'ユーザが存在しません'], 400);
    }
    $stmt->close();

    // トークンを生成（デモ環境のため、画面に返す）
    $token = bin2hex(random_bytes(16));
    sendJson(['success' => true, 'message' => 'トークンを発行しました', 'token' => $token], 200);
}

// パスワードリセット - パスワード再設定
if ($method === 'POST' && $action === 'reset-password') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['username']) || empty($input['new_password']) || empty($input['confirm_password'])) {
        sendJson(['success' => false, 'message' => 'すべての項目を入力してください'], 400);
    }
    if ($input['new_password'] !== $input['confirm_password']) {
        sendJson(['success' => false, 'message' => '新しいパスワードが一致しません'], 400);
    }

    $username = trim($input['username']);
    $new_password = password_hash($input['new_password'], PASSWORD_BCRYPT);

    // ユーザーが存在するか確認
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'ユーザが存在しません'], 400);
    }
    $stmt->close();

    // パスワードを更新
    $stmt = $mysqli->prepare('UPDATE users SET password = ? WHERE username = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('ss', $new_password, $username);
    
    if ($stmt->execute()) {
        $stmt->close();
        sendJson(['success' => true, 'message' => 'パスワードをリセットしました'], 200);
    } else {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'パスワード更新に失敗しました'], 500);
    }
}

// パスワード変更処理
if ($method === 'POST' && $action === 'change-password') {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
        sendJson(['success' => false, 'message' => 'ログインが必要です'], 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['current_password']) || empty($input['new_password']) || empty($input['confirm_password'])) {
        sendJson(['success' => false, 'message' => 'すべての項目を入力してください'], 400);
    }
    if ($input['new_password'] !== $input['confirm_password']) {
        sendJson(['success' => false, 'message' => '新しいパスワードが一致しません'], 400);
    }

    $user_id = $_SESSION['user_id'];
    $current_password = $input['current_password'];
    $new_password = password_hash($input['new_password'], PASSWORD_BCRYPT);

    // ユーザーのパスワードを取得
    $stmt = $mysqli->prepare('SELECT password FROM users WHERE id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'ユーザが見つかりません'], 404);
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // 現在のパスワードを検証
    if (!password_verify($current_password, $user['password'])) {
        sendJson(['success' => false, 'message' => '現在のパスワードが違います'], 401);
    }

    // 新しいパスワードに更新
    $stmt = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('si', $new_password, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        sendJson(['success' => true, 'message' => 'パスワードを変更しました'], 200);
    } else {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'パスワード変更に失敗しました'], 500);
    }
}

// アカウント削除処理
if ($method === 'POST' && $action === 'delete-account') {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
        sendJson(['success' => false, 'message' => 'ログインが必要です'], 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['password'])) {
        sendJson(['success' => false, 'message' => 'パスワードを入力してください'], 400);
    }

    $user_id = $_SESSION['user_id'];
    $password = $input['password'];

    // ユーザーのパスワードを取得
    $stmt = $mysqli->prepare('SELECT password FROM users WHERE id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'ユーザが見つかりません'], 404);
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // パスワードを検証
    if (!password_verify($password, $user['password'])) {
        sendJson(['success' => false, 'message' => 'パスワードが違います'], 401);
    }

    // ユーザーアカウントを削除
    $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('i', $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION = [];
        session_destroy();
        sendJson(['success' => true, 'message' => 'アカウントを削除しました'], 200);
    } else {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'アカウント削除に失敗しました'], 500);
    }
}

// 無効なリクエスト
sendJson(['success' => false, 'message' => '無効なリクエストです'], 400);
