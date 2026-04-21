<?php
// セッション保護をチェック
require_once '../../includes/protect.php';
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ダッシュボード – 管理パネル</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard">
  <!-- ① サイドバー -->
  <nav class="sidebar">
    <h2 style="margin-bottom:2rem;text-align:center;">Admin</h2>
    <a href="../dashboard/" class="active">ダッシュボード</a>
    <a href="../account-settings/">アカウント設定</a>
    <a href="#">メール設定</a>
    <a href="#">FTP 管理</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

  <!-- ② メインコンテンツ -->
  <section class="main-content">
    <h1 style="margin-bottom:1.5rem;">ようこそ <span id="userName"></span> さん</h1>

    <div class="cards-grid">
      <div class="card">
        <h3>サイト数</h3>
        <p style="font-size:2rem;margin-top:0.5rem;">3</p>
      </div>
      <div class="card">
        <h3>メールアカウント</h3>
        <p style="font-size:2rem;margin-top:0.5rem;">12</p>
      </div>
      <div class="card">
        <h3>ディスク使用率</h3>
        <p style="font-size:2rem;margin-top:0.5rem;">68%</p>
      </div>
      <div class="card">
        <h3>CPU 負荷</h3>
        <p style="font-size:2rem;margin-top:0.5rem;">23%</p>
      </div>
    </div>
  </section>

  <script src="../../assets/js/dashboard.js"></script>
</body>
</html>
