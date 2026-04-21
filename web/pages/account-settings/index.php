<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>アカウント設定 – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard">
  <!-- ① サイドバー -->
  <nav class="sidebar">
    <h2 style="margin-bottom:2rem;text-align:center;">Admin</h2>
    <a href="../dashboard/">ダッシュボード</a>
    <a href="../account-settings/" class="active">アカウント設定</a>
    <a href="#">メール設定</a>
    <a href="#">FTP 管理</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1 style="margin-bottom:1.5rem;">アカウント設定</h1>

    <!-- パスワード変更セクション -->
    <div style="background:#fff;padding:2rem;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);max-width:600px;margin-bottom:2rem;">
      <h2 style="font-size:1.2rem;margin-bottom:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;">パスワードの変更</h2>
      <form id="changePasswordForm">
        <label for="current_password" style="display:block;margin-bottom:0.5rem;font-weight:bold;color:#374151;">現在のパスワード</label>
        <input type="password" id="current_password" name="current_password" class="input" required style="width:100%;margin-bottom:1rem;">

        <label for="new_password" style="display:block;margin-bottom:0.5rem;font-weight:bold;color:#374151;">新しいパスワード</label>
        <input type="password" id="new_password" name="new_password" class="input" required style="width:100%;margin-bottom:1rem;">

        <label for="confirm_password" style="display:block;margin-bottom:0.5rem;font-weight:bold;color:#374151;">新しいパスワード（確認）</label>
        <input type="password" id="confirm_password" name="confirm_password" class="input" required style="width:100%;margin-bottom:1.5rem;">

        <button type="submit" class="btn" style="width:100%;">パスワードを変更する</button>
      </form>
      <p id="pwdMsg" style="margin-top:1rem;font-size:0.9rem;"></p>
    </div>

    <!-- アカウント削除セクション -->
    <div style="background:#fef2f2;padding:2rem;border-radius:8px;border:1px solid #fecaca;max-width:600px;">
      <h2 style="font-size:1.2rem;margin-bottom:1rem;color:#b91c1c;border-bottom:1px solid #fecaca;padding-bottom:0.5rem;">アカウントの削除</h2>
      <p style="margin-bottom:1rem;color:#7f1d1d;font-size:0.95rem;">アカウントを削除すると、すべてのデータが完全に削除され、元に戻すことはできません。</p>
      <form id="deleteAccountForm">
        <label for="delete_password" style="display:block;margin-bottom:0.5rem;font-weight:bold;color:#991b1b;">アカウントを削除するには、パスワードを入力してください</label>
        <input type="password" id="delete_password" name="password" class="input" required style="width:100%;border-color:#fca5a5;margin-bottom:1.5rem;">
        
        <button type="submit" class="btn" style="width:100%;background:#ef4444;color:#fff;">アカウントを完全に削除する</button>
      </form>
      <p id="delMsg" style="margin-top:1rem;font-size:0.9rem;"></p>
    </div>
  </section>

  <script src="../../assets/js/dashboard.js"></script>
  <script src="../../assets/js/account-settings.js"></script>
</body>
</html>
