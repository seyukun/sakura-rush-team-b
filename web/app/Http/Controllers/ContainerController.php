<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Container;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ContainerController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        // 既にコンテナが存在するかチェック
        if (Container::where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'コンテナは既に作成されています'], 400);
        }

        // フロントエンドから送信されたパスワードの検証
        $request->validate([
            'sftp_password' => 'required|string|min:8',
            'subdomain'     => 'required|string|max:63|regex:/^[a-z0-9-]+$/', // サブドメインのバリデーション
        ], [
            'sftp_password.required' => 'SFTPパスワードは必須です',
            'sftp_password.min' => 'SFTPパスワードは8文字以上で設定してください',
            'subdomain.required' => 'サブドメインは必須です',
            'subdomain.regex' => 'サブドメインは小文字の英数字とハイフンのみ使用できます',
        ]);

        $sftpPassword = $request->sftp_password;
        $subdomain = $request->subdomain;

        // コンテナIDの生成（小文字英数字で10文字以内： "c_" + 8文字 = 10文字）
        do {
            $containerId = 'c_' . strtolower(Str::random(8));
        } while (Container::where('id', $containerId)->exists());

        // IPアドレスの生成（10.200.1.2 ~ 10.200.1.254）
        $usedIps = Container::pluck('ip')->toArray();
        $ip = null;
        for ($i = 2; $i <= 254; $i++) {
            $candidate = "10.200.1.{$i}/24";
            if (!in_array($candidate, $usedIps)) {
                $ip = $candidate;
                break;
            }
        }
        if (!$ip) {
            return response()->json(['success' => false, 'message' => '割り当て可能なIPアドレスがありません'], 500);
        }

        // SFTPポートの生成（重複チェックあり）
        do {
            $sftpPort = rand(10000, 60000);
        } while (Container::where('sftp_port', $sftpPort)->exists());

        try {
            // 内部APIへJSONでPOSTリクエストを送信
            $apiUrl = env('INTERNAL_API_URL', 'http://127.0.0.1:9080') . '/internal/create-user-container';
            $response = Http::post($apiUrl, [
                'id'            => $containerId,
                'user_id'       => $user->id,
                'ip'            => $ip,
                'cpu_quota_ms'  => 50000,
                'cpu_period_ms' => 100000,
                'mem_m'         => 1024,
                'volume_size'   => 20,
                'sftp_port'     => $sftpPort,
                'sftp_password' => $sftpPassword,
                'subdomain'     => $subdomain,
            ]);

            if ($response->successful()) {
                // APIリクエストが成功した場合、データベースに登録
                Container::create([
                    'id'            => $containerId, 
                    'user_id'       => $user->id,
                    'ip'            => $ip,          
                    'cpu_quota_ms'  => 50000,
                    'cpu_period_ms' => 100000,
                    'mem_m'         => 1024,
                    'volume_size'   => 20,
                    'sftp_port'     => $sftpPort,
                    'sftp_password' => $sftpPassword,
                ]);

                return response()->json(['success' => true, 'message' => 'コンテナを作成しました']);
            } else {
                Log::error('Container API creation failed: ' . $response->body());
                return response()->json(['success' => false, 'message' => 'コンテナの作成に失敗しました。サーバーの応答をご確認ください。'], 500);
            }
        } catch (\Exception $exception) {
            Log::error('Container API connection error: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => '内部APIサーバーとの通信に失敗しました。'], 500);
        }
    }
}