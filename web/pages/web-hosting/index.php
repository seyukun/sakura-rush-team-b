<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Webホスティング – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard">
  <!-- ① サイドバー -->
  <nav class="sidebar">
    <h2 style="margin-bottom:2rem;text-align:center;">Admin</h2>
    <a href="../dashboard/">ダッシュボード</a>
    <a href="../web-hosting/" class="active">Web Hosting</a>
    <a href="../file-manager/">File Manager</a>
    <a href="../php-db-settings/">PHP / DB 設定</a>
    <a href="../ftp-info/">FTP Information</a>
    <a href="../mail-hosting/">Mail Hosting</a>
    <a href="../account-settings/">アカウント設定</a>
    <a href="../help/">Help</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1 style="margin-bottom:1.5rem;">Webホスティング管理</h1>

    <div class="cards-grid">
      <!-- WordPress Installer -->
      <div class="card" style="grid-column: 1 / -1;">
        <h2 style="font-size:1.2rem;margin-bottom:1rem;border-bottom:1px solid #e5e7eb;padding-bottom:0.5rem;">WordPress インストーラー</h2>
        <p style="margin-bottom:1rem;color:#4b5563;">ワンクリックでWordPressをインストールします。</p>
        <form id="wpInstallForm">
          <label for="wp_domain" style="display:block;margin-bottom:0.5rem;font-weight:bold;color:#374151;">インストール先ドメイン</label>
          <input type="text" id="wp_domain" name="domain" class="input" placeholder="example.com" required style="width:100%;max-width:400px;margin-bottom:1rem;">
          
          <label for="wp_title" style="display:block;margin-bottom:0.5rem;font-weight:bold;color:#374151;">サイトのタイトル</label>
          <input type="text" id="wp_title" name="title" class="input" placeholder="My Awesome Site" required style="width:100%;max-width:400px;margin-bottom:1.5rem;">

          <button type="submit" class="btn" style="width:100%;max-width:400px;">WordPressをインストール</button>
        </form>
        <p id="wpMsg" style="margin-top:1rem;font-size:0.9rem;"></p>
      </div>
    </div>
  </section>

  <script src="../dashboard/js/dashboard.js"></script>
  <script src="js/web-hosting.js"></script>
</body>
</html>