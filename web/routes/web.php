<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MailHostingController;
use App\Http\Controllers\FileManagerController;
use Illuminate\Http\Request;
use App\Models\Container;
use App\Models\EmailUser;

// ログイン画面の表示
Route::get('/', function () {
    return view('login');
})->name('login');


// ログイン処理 (APIとしてJSONを返す)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// アカウント作成画面の表示
Route::get('/register', function () {
    return view('register');
})->name('register');

// アカウント作成処理
Route::post('/register', [AuthController::class, 'register']);

// パスワードリセット画面の表示
Route::get('/reset', function () {
    return view('reset');
})->name('reset');
Route::post('/reset-request', [AuthController::class, 'resetRequest']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ログイン済みユーザーのみアクセス可能なルート
Route::middleware('auth')->group(function () {
    // ダッシュボード
    Route::get('/dashboard', function (Request $request) {
    $container = Container::where('user_id', $request->user()->id)->first();
    $mailCount = EmailUser::where('user_id', $request->user()->id)->count();
    
    return view('dashboard', compact('container', 'mailCount'));
    })->name('dashboard');

    // アカウント設定
    Route::get('/account-settings', function () {
        return view('account-settings');
    })->name('account-settings');
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/delete-account', [AuthController::class, 'deleteAccount']);

     // メールホスティング
    Route::get('/mail-hosting', function () {
        return view('mail-hosting');
    })->name('mail-hosting');
    Route::get('/api/mail-hosting/count', [MailHostingController::class, 'count']);
    Route::get('/api/mail-hosting/list', [MailHostingController::class, 'list']);
    Route::get('/api/mail-hosting/domains', [MailHostingController::class, 'domains']);
    Route::post('/api/mail-hosting/create', [MailHostingController::class, 'create']);
    Route::delete('/api/mail-hosting/delete', [MailHostingController::class, 'delete']);

    // ファイルマネージャー
    Route::get('/file-manager', function () {
        return view('file-manager');
    })->name('file-manager');
    Route::get('/api/file-manager/list', [FileManagerController::class, 'list']);
    Route::post('/api/file-manager/upload', [FileManagerController::class, 'upload']);
    Route::delete('/api/file-manager/delete', [FileManagerController::class, 'delete']);
});