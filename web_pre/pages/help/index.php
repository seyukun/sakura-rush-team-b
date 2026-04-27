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
  <?php include '../../includes/sidebar.php'; ?>

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