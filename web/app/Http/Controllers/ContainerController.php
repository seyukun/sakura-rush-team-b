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
        $domain = $subdomain . '.kubernetes.jp';

        // コンテナIDの生成（小文字英数字で10文字以内： "c_" + 8文字 = 10文字）
        do {
            $containerId = 'c_' . strtolower(Str::random(8));
        } while (Container::where('id', $containerId)->exists());

        // IPアドレスの生成（10.200.1.2 ~ 10.200.1.254）
        // toBase() を使ってミューテタを通さず、データベースに保存されている生の数値(整数)をそのまま取得
        $usedIps = Container::toBase()->pluck('ip')->toArray();
        $ip = null;
        for ($i = 2; $i <= 254; $i++) {
            // 比較用にはサブネットマスクなしのIPを使用する
            $candidateIp = "10.200.1.{$i}";
            $candidateLong = ip2long($candidateIp); // 候補IPも整数に変換する

            // 生の数値(整数)同士で比較する
            if (!in_array($candidateLong, $usedIps)) {
                // まだ使われていなければ、API送信用の /24 付きを変数にセットしてループを抜ける
                $ip = $candidateIp . '/24';
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
            // サブドメインの登録
            $subdomainUrl = env('INTERNAL_API_URL', 'http://127.0.0.1:9080') . '/internal/register-subdomain';
            $subdomainResponse = Http::post($subdomainUrl, [
                'subdomain' => $subdomain,
            ]);

            if (!$subdomainResponse->successful()) {
                $errorMessage = $subdomainResponse->json('detail') ?? 'サブドメインの登録に失敗しました。';
                Log::error('Subdomain registration failed: ' . $subdomainResponse->body());
                return response()->json(['success' => false, 'message' => $errorMessage], $subdomainResponse->status());
            }

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
                'domain'        => $domain,
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
                // FastAPIから返ってきたエラーメッセージ("detail")があれば取得する
                $errorMessage = $response->json('detail') ?? 'コンテナの作成に失敗しました。サーバーの応答をご確認ください。';
                Log::error('Container API creation failed: ' . $response->body());
                
                // FastAPIのステータスコード(400など)をそのままフロントエンドに返す
                return response()->json(['success' => false, 'message' => $errorMessage], $response->status());
            }
        } catch (\Exception $exception) {
            Log::error('Container API connection error: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => '内部APIサーバーとの通信に失敗しました。'], 500);
        }
    }
}