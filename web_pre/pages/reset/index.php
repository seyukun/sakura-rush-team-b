<?php
/**
 * pages/reset/index.php - パスワードリセットページ
 */
require_once '../../includes/config.php';

// 既にログイン済みならダッシュボードへ
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_name'])) {
    header('Location: ../dashboard/');
    exit;
}
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>パスワードリセット – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="login-wrapper">
  <div class="login-box" style="max-width:400px;width:100%;">
    <h2 style="text-align:center;margin-bottom:1.5rem;">パスワードリセット</h2>
    
    <div style="border-bottom:1px solid #ddd; margin-bottom:1.5rem; padding-bottom:1.5rem;">
      <h3 style="font-size:1.1rem;margin-bottom:1rem;">1. トークン発行</h3>
      <form id="requestResetForm">
        <label for="req_username">ユーザ名</label>
        <input type="text" id="req_username" name="username" class="input" required placeholder="admin">
        <button type="submit" class="btn" style="width:100%;margin-top:1rem;">トークンを発行する</button>
      </form>
    </div>

    <div>
      <h3 style="font-size:1.1rem;margin-bottom:1rem;">2. パスワード再設定</h3>
      <form id="resetPasswordForm">
        <label for="res_username">ユーザ名</label>
        <input type="text" id="res_username" name="username" class="input" required placeholder="admin">

        <label style="margin-top:1rem;" for="token">リセットトークン</label>
        <input type="text" id="token" name="token" class="input" required>

        <label style="margin-top:1rem;" for="new_password">新しいパスワード</label>
        <input type="password" id="new_password" name="new_password" class="input" required placeholder="********">

        <label style="margin-top:1rem;" for="confirm_password">新しいパスワード（確認）</label>
        <input type="password" id="confirm_password" name="confirm_password" class="input" required placeholder="********">

        <button type="submit" class="btn" style="width:100%;margin-top:1.5rem;">パスワードをリセットする</button>
      </form>
    </div>

    <p id="msg" style="color:#e11d48;margin-top:1rem;font-size:0.9rem;text-align:center;"></p>
    
    <div class="form-links" style="margin-top:1.5rem;text-align:center;">
      <a href="../../index.php" style="margin-right:1rem;">ログイン画面に戻る</a>
    </div>
  </div>

  <script src="js/reset.js"></script>
</body>
</html>
