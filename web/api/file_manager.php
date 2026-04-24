<?php
/**
 * api/file-manager.php - ファイルマネージャー処理
 * RESTful APIエンドポイント
 */

require_once '../includes/api_middleware.php';

// コンテナ情報を取得
$stmt = $mysqli->prepare('SELECT id FROM containers WHERE user_id = ? LIMIT 1');
if (!$stmt) {
    sendJson(['success' => false, 'message' => 'データベースエラーが発生しました'], 500);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    sendJson(['success' => false, 'message' => 'コンテナ情報が見つかりません'], 404);
}
$container = $result->fetch_assoc();
$stmt->close();

$container_id = $container['id'];

// アップロード先ディレクトリの指定: ~/rootfses/{container_id}/rootfs/
$homeDir = getenv('HOME') ?: '/home/ubuntu'; // 実行ユーザーのホームディレクトリ（無い場合のフォールバック）
$uploadDir = rtrim($homeDir, '/') . '/rootfses/' . $container_id . '/rootfs/';

// フォルダが存在しない場合は作成
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}


// ファイル一覧取得
if ($method === 'GET' && $action === 'list') {
    $files = [];
    if (is_dir($uploadDir)) {
        $items = scandir($uploadDir);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..') {
                $filePath = $uploadDir . $item;
                if (is_file($filePath)) {
                    $files[] = [
                        'name' => $item,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath)
                    ];
                }
            }
        }
    }
    
    sendJson([
        'success' => true,
        'files' => $files
    ], 200);
}

// ファイルアップロード
if ($method === 'POST' && $action === 'upload') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendJson(['success' => false, 'message' => 'ファイルのアップロードに失敗しました'], 400);
    }

    $file = $_FILES['file'];
    // ファイル名のサニタイズ（ディレクトリトラバーサル防止）
    $filename = basename($file['name']);
    $destination = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        sendJson(['success' => true, 'message' => 'ファイルをアップロードしました'], 200);
    } else {
        sendJson(['success' => false, 'message' => 'ファイルの保存に失敗しました。フォルダの権限を確認してください。'], 500);
    }
}

// ファイル削除
if ($method === 'DELETE' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ディレクトリトラバーサル防止のため basename を使用
    $filename = basename($input['filename'] ?? '');
    $filePath = $uploadDir . $filename;

    if (file_exists($filePath) && is_file($filePath) && unlink($filePath)) {
        sendJson(['success' => true, 'message' => 'ファイルを削除しました'], 200);
    } else {
        sendJson(['success' => false, 'message' => 'ファイルの削除に失敗しました、またはファイルが存在しません'], 500);
    }
}

sendJson(['success' => false, 'message' => '不正なリクエストです'], 400);
