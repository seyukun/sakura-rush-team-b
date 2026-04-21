<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>PHP / DB 設定 – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard">
  <!-- ① サイドバー -->
  <nav class="sidebar">
    <h2 style="margin-bottom:2rem;text-align:center;">Admin</h2>
    <a href="../dashboard/">ダッシュボード</a>
    <a href="../web-hosting/">Web Hosting</a>
    <a href="../file-manager/">File Manager</a>
    <a href="../php-db-settings/" class="active">PHP / DB 設定</a>
    <a href="../ftp-info/">FTP Information</a>
    <a href="../mail-hosting/">Mail Hosting</a>
    <a href="../account-settings/">アカウント設定</a>
    <a href="../help/">Help</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1>PHP / DB 設定</h1>
    <p>PHPとデータベースの設定を管理します。</p>
    <div class="card">
      <h3>PHP バージョン</h3>
      <p>現在のバージョン: 8.1</p>
      <select class="input">
        <option>7.4</option>
        <option selected>8.1</option>
        <option>8.2</option>
      </select>
    </div>
    <div class="card">
      <h3>データベース設定</h3>
      <p>ホスト: localhost</p>
      <p>データベース名: mydb</p>
      <button class="btn">設定変更</button>
    </div>
  </section>

  <script src="js/dashboard.js"></script>
</body>
</html>