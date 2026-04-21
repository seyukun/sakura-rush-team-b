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
  <nav class="sidebar">
    <h2 style="margin-bottom:2rem;text-align:center;">Admin</h2>
    <a href="../dashboard/">ダッシュボード</a>
    <a href="../web-hosting/">Web Hosting</a>
    <a href="../file-manager/">File Manager</a>
    <a href="../php-db-settings/">PHP / DB 設定</a>
    <a href="../ftp-info/">FTP Information</a>
    <a href="../mail-hosting/" class="active">Mail Hosting</a>
    <a href="../account-settings/">アカウント設定</a>
    <a href="../help/">Help</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1>Mail Hosting</h1>
    <p>メールホスティングの設定とメールアドレスを表示します。</p>
    <div class="card">
      <h3>メールアドレス</h3>
      <ul>
        <li>user@example.com</li>
        <li>admin@example.com</li>
      </ul>
    </div>
    <div class="card">
      <h3>設定</h3>
      <p>IMAP: imap.example.com:993</p>
      <p>SMTP: smtp.example.com:587</p>
    </div>
  </section>

  <script src="js/dashboard.js"></script>
</body>
</html>