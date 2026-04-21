<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Help – 管理パネル</title>
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
    <a href="../mail-hosting/">Mail Hosting</a>
    <a href="../account-settings/">アカウント設定</a>
    <a href="../help/" class="active">Help</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1>Help</h1>
    <p>FAQとチュートリアルを提供します。</p>
    <div class="card">
      <h3>FAQ</h3>
      <p><strong>Q: パスワードを忘れた場合？</strong></p>
      <p>A: リセットページからパスワードをリセットしてください。</p>
    </div>
    <div class="card">
      <h3>チュートリアル</h3>
      <p>ダッシュボードの使い方: 概要と使用率を確認できます。</p>
    </div>
  </section>

  <script src="js/dashboard.js"></script>
</body>
</html>