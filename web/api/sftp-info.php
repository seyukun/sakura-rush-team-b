<?php
/**
 * api/sftp-info.php - SFTP接続情報取得
 * RESTful APIエンドポイント
 */

require_once '../includes/api_middleware.php';

// SFTP接続情報を取得
if ($method === 'GET' && $action === 'info') {
    $stmt = $mysqli->prepare('SELECT sftp_port, sftp_password FROM containers WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        sendJson([
            'success' => false,
            'message' => 'SFTP情報が見つかりません'
        ], 404);
    }

    $container = $result->fetch_assoc();
    $stmt->close();

    sendJson([
        'success' => true,
        'sftp_port' => (int)$container['sftp_port'],
        'sftp_password' => $container['sftp_password']
    ], 200);
}

// 無効なリクエスト
sendJson(['success' => false, 'message' => '不正なリクエストです'], 400);
