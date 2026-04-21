<?php
/**
 * pages/register/index.php - アカウント作成ページ
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
  <title>アカウント作成 – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="login-wrapper">
  <div class="login-box">
    <h2 style="text-align:center;margin-bottom:1.5rem;">アカウント作成</h2>
    <form id="registerForm">
      <label for="username">ユーザ名</label>
      <input type="text" id="username" name="username" class="input" required placeholder="admin">

      <label style="margin-top:1rem;" for="password">パスワード</label>
      <input type="password" id="password" name="password" class="input" required placeholder="********">

      <label style="margin-top:1rem;" for="confirm_password">パスワード（確認）</label>
      <input type="password" id="confirm_password" name="confirm_password" class="input" required placeholder="********">

      <button type="submit" class="btn" style="width:100%;margin-top:1.5rem;">アカウントを作成する</button>
    </form>
    <p id="msg" style="color:#e11d48;margin-top:0.75rem;font-size:0.9rem;"></p>
    <div class="form-links" style="margin-top:1rem;text-align:center;">
      <a href="../../index.php" style="margin-right:1rem;">ログイン画面に戻る</a>
    </div>
  </div>

  <script src="../../assets/js/register.js"></script>
</body>
</html>
