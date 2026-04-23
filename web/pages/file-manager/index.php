<?php
require_once '../../includes/protect.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ファイルマネージャー</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dashboard">
  <!-- ① サイドバー -->
  <?php include '../../includes/sidebar.php'; ?>

  <section class="main-content">
    <h1 style="margin-bottom:1.5rem;">ファイルマネージャー</h1>
    <div class="card">
        <p style="margin-bottom:1rem;color:#4b5563;">ここでファイルをアップロード、編集、削除できます。（モック画面です）</p>
        <button class="btn" style="background:#10b981;color:white;" onclick="alert('アップロード処理')">アップロード</button>
    </div>
  </section>
  <script src="../dashboard/js/dashboard.js"></script>
</body>
</html>