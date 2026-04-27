<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Mail Hosting – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard">
  <!-- ① サイドバー -->
  <?php include '../../includes/sidebar.php'; ?>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1>Mail Hosting</h1>
    <p>メールホスティングの設定とメールアドレスを表示します。</p>

    <div class="card" style="margin-bottom: 2rem;">
      <h3 style="border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;margin-bottom:1rem;">新規メールアドレス作成</h3>
      <form id="createMailForm">
        <div style="display:flex;gap:1rem;align-items:center;">
          <input type="text" id="mail_user" name="mail_user" class="input" placeholder="user" required style="flex-grow:1;max-width:200px;">
          <span style="font-weight:bold;color:#4b5563;">@</span>
          <select id="mail_domain" name="domain_id" class="input" required style="flex-grow:1;max-width:200px;">
            <option value="">読み込み中...</option>
          </select>
          <input type="password" id="mail_password" name="mail_password" class="input" placeholder="パスワード" required style="flex-grow:1;max-width:200px;">
          <button type="submit" class="btn">作成</button>
        </div>
      </form>
      <p id="mailMsg" style="margin-top:1rem;font-size:0.9rem;"></p>
    </div>

    <div class="card">
      <h3>メールアドレス</h3>
      <ul id="mailList">
        <li style="color: #999;">読み込み中...</li>
      </ul>
    </div>
  </section>

  <script src="js/dashboard.js"></script>
  <script src="js/mail-hosting.js"></script>
</body>
</html>