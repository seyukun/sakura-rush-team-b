<?php
/**
 * api_middleware.php - 認証が必要なAPIの共通処理
 */
require_once __DIR__ . '/config.php';

if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    sendJson(['success' => false, 'message' => 'ログインが必要です'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = $_SESSION['user_id'];

// POST / DELETE / PUT リクエストのCSRFトークン検証
if (in_array($method, ['POST', 'DELETE', 'PUT'])) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
    if (!verifyCsrfToken($csrf_token)) {
        sendJson(['success' => false, 'message' => '不正なリクエストです (CSRFトークンが無効)'], 403);
    }
}