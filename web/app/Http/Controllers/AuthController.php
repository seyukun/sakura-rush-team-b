<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Str;        

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 入力値のチェック（バリデーション）
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt がパスワードのハッシュ照合などを自動で行います
        if (Auth::attempt($credentials)) {
            // セッション固定攻撃対策
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'message' => 'ログインに成功しました',
                'username' => Auth::user()->username
            ]);
        }

        // 認証失敗時
        return response()->json([
            'success' => false,
            'message' => 'ユーザ名またはパスワードが違います'
        ], 401);
    }
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました'
        ]);
    }
     public function register(Request $request)
    {
        // 入力値のチェックと重複確認を自動で行う
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:64', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string'],
            'confirm_password' => ['required', 'same:password'],
        ], [
            'username.unique' => 'このユーザ名は既に登録されています。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'confirm_password.same' => 'パスワードが一致しません。',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        // ユーザーの作成 (パスワードのハッシュ化はUserモデルで自動的に行われます)
        User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return response()->json(['success' => true, 'message' => 'アカウントを作成しました']);
    }
    public function resetRequest(Request $request)
    {
        $request->validate(['username' => 'required|string']);
        
        $user = User::where('username', $request->username)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => '入力されたユーザ情報に誤りがあります'], 400);
        }

        $token = Str::random(16); // 16桁のランダムな文字列（トークン）を生成

        // Laravel標準の password_reset_tokens テーブルに保存
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()]
        );

        return response()->json([
            'success' => true, 
            'message' => 'トークンを発行しました', 
            'token' => $token
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'token' => ['required', 'string'],
            'new_password' => ['required', 'string'],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'confirm_password.same' => '新しいパスワードが一致しません',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user = User::where('username', $request->username)->first();
        $resetRecord = DB::table('password_reset_tokens')->where('email', $user->email ?? '')->where('token', $request->token)->first();

        if (!$user || !$resetRecord) {
            return response()->json(['success' => false, 'message' => 'トークンが無効またはユーザ情報に誤りがあります'], 400);
        }

        $user->update(['password' => $request->new_password]);
        DB::table('password_reset_tokens')->where('email', $user->email)->delete(); // 使用済みトークンの削除

        return response()->json(['success' => true, 'message' => 'パスワードをリセットしました']);
    }
      // パスワード変更（ログイン中ユーザー）
    public function changePassword(Request $request)
    {
        $user = $request->user(); // 現在ログインしているユーザーを取得

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'current_password'], // 現在のパスワードが合っているか自動チェック
            'new_password' => ['required', 'string'],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'current_password.current_password' => '現在のパスワードが違います',
            'confirm_password.same' => '新しいパスワードが一致しません',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user->update(['password' => $request->new_password]);

        return response()->json(['success' => true, 'message' => 'パスワードを変更しました']);
    }

    // アカウント削除（退会）
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'current_password'],
        ], [
            'password.current_password' => 'パスワードが違います',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user->delete(); // ユーザーをデータベースから削除
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => 'アカウントを削除しました']);
    }
}
