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
    <div class="card">
      <h3>メールアドレス</h3>
      <ul id="mailList">
        <li style="color: #999;">読み込み中...</li>
      </ul>
    </div>
  </section>

  <script src="js/dashboard.js"></script>
</body>
</html>