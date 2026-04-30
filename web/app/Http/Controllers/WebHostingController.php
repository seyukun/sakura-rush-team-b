<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Container;

class WebHostingController extends Controller
{
    public function setup(Request $request)
    {
        $user = $request->user();

        // 1. フロントエンドから送られてくるデータのバリデーション
        $request->validate([
            'wp_title'    => 'required|string|max:255',
            'wp_username' => 'required|string|max:255',
            'wp_password' => 'required|string|min:8',
            'wp_email'    => 'required|email|max:255',
        ]);

        // 2. ユーザーのコンテナ情報を取得
        $container = Container::where('user_id', $user->id)->first();
        if (!$container) {
            return response()->json(['success' => false, 'message' => '先にコンテナを作成してください。'], 400);
        }

        // 3. WP URL の自動生成
        // ユーザーのsubdomainを使用して kubernetes.jp を使用する
        $subdomain = $user->subdomain;
        $wpUrl = 'https://' . $subdomain . '.kubernetes.jp';

        try {
            // 4. チームメンバーが作成した内部APIにJSONでリクエストを送信
            $apiUrl = env('INTERNAL_API_URL', 'http://127.0.0.1:9080') . '/internal/wordpress-install';
            $payload = array_filter([
                'id'                    => $container->id,
                'user_id'               => $user->id,
                'mariadb_root_password' => $request->mariadb_root_password,
                'mariadb_database'      => $request->mariadb_database,
                'mariadb_user'          => $request->mariadb_user,
                'mariadb_password'      => $request->mariadb_password,
                'wp_url'                => $wpUrl,
                'wp_title'              => $request->wp_title,
                'wp_admin'              => $request->wp_admin,
                'wp_admin_password'     => $request->wp_admin_password,
                'wp_admin_email'        => $request->wp_admin_email,
                'wp_username'           => $request->wp_username,
                'wp_email'              => $request->wp_email,
                'wp_password'           => $request->wp_password,
                'wp_displayname'        => $request->wp_displayname,
            ], fn($val) => !is_null($val));

            $response = Http::post($apiUrl, $payload);

            if ($response->successful()) {
                // 成功した場合、フロントエンドに結果と生成したURLを返す
                return response()->json([
                    'success' => true,
                    'message' => 'WordPressのセットアップを開始しました。',
                    'wp_url'  => $wpUrl
                ]);
            } else {
                Log::error('WordPress API failed: ' . $response->body());
                return response()->json(['success' => false, 'message' => 'WordPressのセットアップに失敗しました。'], 500);
            }
        } catch (\Exception $exception) {
            Log::error('WordPress API connection error: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => '内部APIサーバーとの通信に失敗しました。'], 500);
        }
    }
}