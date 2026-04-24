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
        <p style="margin-bottom:1rem;color:#4b5563;">ここでファイルをアップロード、削除できます。</p>
        
        <form id="uploadForm" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
            <input type="file" id="fileInput" name="file" required>
            <button type="submit" class="btn">アップロード</button>
        </form>
        <p id="fileMsg" style="margin-bottom:1rem;"></p>
        
        <h3>ファイル一覧</h3>
        <ul id="fileList" style="list-style: none; padding: 0; margin-top: 1rem;">
            <li style="color: #999;">読み込み中...</li>
        </ul>
    </div>
  </section>
  <script src="../dashboard/js/dashboard.js"></script>
  <script src="js/file-manager.js"></script>
</body>
</html>