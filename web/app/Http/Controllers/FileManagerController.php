<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Container;

class FileManagerController extends Controller
{
    // 保存先ディレクトリを取得（無ければ作成）
    private function getUploadDir()
    {
        $uploadDir = '/home/ubuntu/web/';
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        return $uploadDir;
    }

    // ファイル一覧取得
    public function list(Request $request)
    {
        // コンテナの存在チェック
        $container = Container::where('user_id', $request->user()->id)->first();
        if (!$container) {
            return response()->json(['success' => false, 'message' => 'コンテナ情報が見つかりません'], 404);
        }

        $uploadDir = $this->getUploadDir();
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

        return response()->json(['success' => true, 'files' => array_values($files)]);
    }

    // ファイルアップロード
    public function upload(Request $request)
    {
        $container = Container::where('user_id', $request->user()->id)->first();
        if (!$container) {
            return response()->json(['success' => false, 'message' => 'コンテナ情報が見つかりません'], 404);
        }

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return response()->json(['success' => false, 'message' => 'ファイルのアップロードに失敗しました'], 400);
        }

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $uploadDir = $this->getUploadDir();
        
        // Laravelの機能で簡単に指定ディレクトリへ保存できます
        $file->move($uploadDir, $filename);

        return response()->json(['success' => true, 'message' => 'ファイルをアップロードしました']);
    }

    // ファイル削除
    public function delete(Request $request)
    {
        $request->validate(['filename' => 'required|string']);
        
        $filename = basename($request->filename); // ディレクトリトラバーサル対策
        $filePath = $this->getUploadDir() . $filename;

        if (file_exists($filePath) && is_file($filePath) && unlink($filePath)) {
            return response()->json(['success' => true, 'message' => 'ファイルを削除しました']);
        }
        return response()->json(['success' => false, 'message' => 'ファイルの削除に失敗しました'], 500);
    }
}
