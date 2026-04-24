<?php
/**
 * api/web-hosting.php - Webホスティング処理
 * RESTful APIエンドポイント
 */

require_once '../includes/config.php';

if (empty($_SESSION['logged_in']) || empty($_SESSION['user_name'])) {
    sendJson(['success' => false, 'message' => 'ログインが必要です'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// POST / DELETE リクエストのCSRFトークン検証
if ($method === 'POST' || $method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
    if (!verifyCsrfToken($csrf_token)) {
        sendJson(['success' => false, 'message' => '不正なリクエストです (CSRFトークンが無効)'], 403);
    }
}

$username = $_SESSION['user_name'];
$dataFile = __DIR__ . '/../data/hosting_' . md5($username) . '.json';

function getHostingData($file) {
    if (!file_exists($file)) return ['php_version' => '8.2', 'wordpress' => []];
    $data = file_get_contents($file);
    return json_decode($data, true) ?: ['php_version' => '8.2', 'wordpress' => []];
}

function saveHostingData($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

if ($method === 'GET' && $action === 'status') {
    $data = getHostingData($dataFile);
    sendJson(['success' => true, 'data' => $data], 200);
}

if ($method === 'POST' && $action === 'install-wp') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['domain']) || empty($input['title'])) {
        sendJson(['success' => false, 'message' => 'ドメインとタイトルは必須です'], 400);
    }
    
    $data = getHostingData($dataFile);
    
    // 既存のドメインかチェック
    foreach ($data['wordpress'] as $wp) {
        if ($wp['domain'] === $input['domain']) {
            sendJson(['success' => false, 'message' => 'このドメインには既にインストールされています'], 400);
        }
    }

    $data['wordpress'][] = [
        'domain' => $input['domain'],
        'title' => $input['title'],
        'installed_at' => time()
    ];
    saveHostingData($dataFile, $data);
    
    sendJson(['success' => true, 'message' => "✅ {$input['title']} ({$input['domain']}) にWordPressがインストールされました。"], 200);
}

if ($method === 'POST' && $action === 'update-php') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['version'])) {
        sendJson(['success' => false, 'message' => 'バージョンを指定してください'], 400);
    }
    
    $data = getHostingData($dataFile);
    $data['php_version'] = $input['version'];
    saveHostingData($dataFile, $data);
    
    sendJson(['success' => true, 'message' => "✅ PHPバージョンを {$input['version']} に更新しました。"], 200);
}

sendJson(['success' => false, 'message' => '無効なリクエストです'], 400);
