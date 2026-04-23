<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>SFTP Information – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard">
  <!-- ① サイドバー -->
  <?php include '../../includes/sidebar.php'; ?>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1>SFTP Information</h1>
    <p>ここではSFTP接続情報を表示します。</p>
    <div class="card">
      <h3>接続情報</h3>
      <p><strong>ポート:</strong> <span id="sftpPort">-</span></p>
      <p><strong>パスワード:</strong> <span id="sftpPassword">-</span></p>
    </div>
    <div class="card">
      <h3>クライアント設定</h3>
      <p>FileZillaなどのSFTPクライアントを使って接続してください。</p>
    </div>
  </section>

  <script src="js/dashboard.js"></script>
</body>
</html>