<?php
/**
 * api/mail-hosting.php - メールホスティング処理
 * RESTful APIエンドポイント
 */

require_once '../includes/config.php';

if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    sendJson(['success' => false, 'message' => 'ログインが必要です'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = $_SESSION['user_id'];

// メールアカウント数を取得
if ($method === 'GET' && $action === 'count') {
    $stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM email_users WHERE user_id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    sendJson([
        'success' => true,
        'count' => (int)$row['count']
    ], 200);
}

// メールアカウント一覧を取得
if ($method === 'GET' && $action === 'list') {
    $stmt = $mysqli->prepare('SELECT id, domain_id, email FROM email_users WHERE user_id = ? ORDER BY email');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row;
    }
    $stmt->close();

    sendJson([
        'success' => true,
        'emails' => $emails
    ], 200);
}

// ドメイン一覧を取得
if ($method === 'GET' && $action === 'domains') {
    $stmt = $mysqli->prepare('SELECT id, name FROM email_domains WHERE user_id = ? AND active = 1 ORDER BY name');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $domains = [];
    while ($row = $result->fetch_assoc()) {
        $domains[] = $row;
    }
    $stmt->close();

    sendJson([
        'success' => true,
        'domains' => $domains
    ], 200);
}

// メールアカウントを作成
if ($method === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['email']) || empty($input['domain_id']) || empty($input['password'])) {
        sendJson(['success' => false, 'message' => 'メールアドレス、ドメイン、パスワードは必須です'], 400);
    }

    $email = trim($input['email']);
    $domain_id = (int)$input['domain_id'];
    $password = password_hash($input['password'], PASSWORD_BCRYPT);

    // メールアドレスの重複チェック
    $stmt = $mysqli->prepare('SELECT id FROM email_users WHERE email = ? AND user_id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('si', $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'このメールアドレスは既に登録されています'], 400);
    }
    $stmt->close();

    // メールアカウントを作成
    $stmt = $mysqli->prepare('INSERT INTO email_users (domain_id, user_id, email, password) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('iiss', $domain_id, $user_id, $email, $password);
    
    if ($stmt->execute()) {
        $stmt->close();
        sendJson([
            'success' => true,
            'message' => 'メールアカウントを作成しました',
            'id' => $mysqli->insert_id
        ], 201);
    } else {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'メールアカウント作成に失敗しました'], 500);
    }
}

// メールアカウントを削除
if ($method === 'DELETE' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        sendJson(['success' => false, 'message' => 'IDは必須です'], 400);
    }

    $id = (int)$input['id'];

    // メールアカウントが実在し、user_idに属しているか確認
    $stmt = $mysqli->prepare('SELECT id FROM email_users WHERE id = ? AND user_id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'メールアカウントが見つかりません'], 404);
    }
    $stmt->close();

    // メールアカウントを削除
    $stmt = $mysqli->prepare('DELETE FROM email_users WHERE id = ? AND user_id = ?');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('ii', $id, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        sendJson(['success' => true, 'message' => 'メールアカウントを削除しました'], 200);
    } else {
        $stmt->close();
        sendJson(['success' => false, 'message' => 'メールアカウント削除に失敗しました'], 500);
    }
}

// 無効なリクエスト
sendJson(['success' => false, 'message' => '不正なリクエストです'], 400);
