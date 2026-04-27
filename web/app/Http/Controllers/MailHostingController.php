<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EmailUser;
use App\Models\EmailDomain;

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

        $emailUser = EmailUser::create([
            'user_id' => $request->user()->id,
            'domain_id' => $request->domain_id,
            'email' => $request->email,
            'password' => $request->password, // EmailUserモデルの casts により自動でハッシュ化されます
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
