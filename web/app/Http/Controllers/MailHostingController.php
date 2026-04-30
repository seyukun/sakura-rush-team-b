<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EmailUser;
use App\Models\EmailDomain;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MailHostingController extends Controller
{
    // ダッシュボード用のカウント取得
    public function count(Request $request)
    {
        $count = EmailUser::where('user_id', $request->user()->id)->count();
        return response()->json(['success' => true, 'count' => $count]);
    }

    // メール一覧の取得
    public function list(Request $request)
    {
        $emails = EmailUser::where('user_id', $request->user()->id)->orderBy('email')->get(['id', 'domain_id', 'email']);

        // 各メールアカウントにIMAP/SMTPの接続情報を付与する
        $emails->transform(function ($email) {
            $email->imap_server = 'mail.kubernetes.jp';
            $email->imap_port = 993;
            $email->smtp_server = 'mail.kubernetes.jp';
            $email->smtp_port = 587;
            $email->username = $email->email; // ユーザー名は作成したメールアドレス
            return $email;
        });

        return response()->json(['success' => true, 'emails' => $emails]);
    }

    // 利用可能なドメイン一覧の取得
    public function domains(Request $request)
    {
        $domains = [
            [
                'id' => 1, // DBを使わないため固定のダミーIDを指定
                'name' => config('app.default_email_domain'),
            ]
        ];
        return response()->json(['success' => true, 'domains' => $domains]);
    }

    // メールアカウント作成
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'domain_id' => 'required|integer',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'メールアドレス、ドメイン、パスワードは必須です'], 400);
        }

        // 重複チェック
        $exists = EmailUser::where('email', $request->email)->where('user_id', $request->user()->id)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'このメールアドレスは既に登録されています'], 400);
        }

        // 内部API (host-api) を呼び出してパスワードをハッシュ化
        try {
            $apiUrl = env('INTERNAL_API_URL', 'http://127.0.0.1:9080') . '/internal/hash-mail-password';
            $response = Http::post($apiUrl, [
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                // FastAPI側が {"hashed_password": "..."} という形式で返す想定
                $hashedPassword = $response->json('hashed_password');

                // 正常なハッシュ値は {SHA512-CRYPT} のように { から始まるため、それで判定
                if (empty($hashedPassword) || !str_starts_with($hashedPassword, '{')) {
                    Log::error("Failed to hash password for email: {$request->email}. Output: {$hashedPassword}");
                    return response()->json(['success' => false, 'message' => 'パスワードの暗号化に失敗しました。'], 500);
                }
            } else {
                Log::error("Password hash API failed: " . $response->body());
                return response()->json(['success' => false, 'message' => 'パスワードの暗号化に失敗しました。'], 500);
            }
        } catch (\Exception $exception) {
            Log::error('Password hash API connection error: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => '内部APIサーバーとの通信に失敗しました。'], 500);
        }

        $emailUser = EmailUser::create([
            'user_id' => $request->user()->id,
            'domain_id' => $request->domain_id,
            'email' => $request->email,
            'password' => $hashedPassword,
        ]);

        return response()->json(['success' => true, 'message' => 'メールアカウントを作成しました', 'id' => $emailUser->id], 201);
    }

    // メールアカウント削除
    public function delete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $emailUser = EmailUser::where('id', $request->id)->where('user_id', $request->user()->id)->first();
        
        if (!$emailUser) {
            return response()->json(['success' => false, 'message' => 'メールアカウントが見つかりません'], 404);
        }

        $emailUser->delete();

        return response()->json(['success' => true, 'message' => 'メールアカウントを削除しました']);
    }
}
