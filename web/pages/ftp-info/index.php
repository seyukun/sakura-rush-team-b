<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>FTP Information – 管理パネル</title>
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
    <a href="../ftp-info/" class="active">FTP Information</a>
    <a href="../mail-hosting/">Mail Hosting</a>
    <a href="../account-settings/">アカウント設定</a>
    <a href="../help/">Help</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1>FTP Information</h1>
    <p>ここではFTP接続情報を表示します。</p>
    <div class="card">
      <h3>接続情報</h3>
      <p><strong>ホスト:</strong> ftp.example.com</p>
      <p><strong>ポート:</strong> 21</p>
      <p><strong>ユーザ名:</strong> yourusername</p>
      <p><strong>パスワード:</strong> ********</p>
    </div>
    <div class="card">
      <h3>クライアント設定</h3>
      <p>FileZillaなどのFTPクライアントを使って接続してください。</p>
    </div>
  </section>

  <script src="js/dashboard.js"></script>
</body>
</html>