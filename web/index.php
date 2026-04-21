<?php
/**
 * index.php - ログインページ
 * 既にログイン済みならダッシュボードへリダイレクト
 */

require_once 'includes/config.php';

// セッションをチェック、既にログイン済みならダッシュボードへ
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_name'])) {
    header('Location: pages/dashboard/');
    exit;
}
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログイン – 管理パネル</title>
  <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body class="login-wrapper">
  <div class="login-box">
    <h2 style="text-align:center;margin-bottom:1.5rem;">管理者ログイン</h2>
    <form id="loginForm">
      <label for="username">ユーザ名</label>
      <input type="text" id="username" name="username" class="input" required placeholder="admin">

      <label style="margin-top:1rem;" for="password">パスワード</label>
      <div style="position:relative;">
        <input type="password" id="password" name="password" class="input" required placeholder="********">
        <span id="togglePwd" style="
          position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);
          cursor:pointer;color:#666;font-size:0.9rem;">👁︎</span>
      </div>

      <button type="submit" class="btn" style="width:100%;margin-top:1.5rem;">ログイン</button>
    </form>
    <p id="msg" style="color:#e11d48;margin-top:0.75rem;font-size:0.9rem;"></p>
    <div class="form-links" style="margin-top:1rem;text-align:center;">
      <a href="pages/register/" style="margin-right:1rem;">アカウント作成</a>
      <a href="pages/reset/">パスワードを忘れた場合</a>
    </div>
  </div>

  <script src="./assets/js/login.js"></script>
</body>
</html>
