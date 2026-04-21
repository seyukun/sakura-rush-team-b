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
  <nav class="sidebar">
    <h2 style="margin-bottom:2rem;text-align:center;">Admin</h2>
    <a href="../dashboard/">ダッシュボード</a>
    <a href="../web-hosting/">Web Hosting</a>
    <a href="../file-manager/" class="active">File Manager</a>
    <a href="../php-db-settings/">PHP / DB 設定</a>
    <a href="../ftp-info/">FTP Information</a>
    <a href="../mail-hosting/">Mail Hosting</a>
    <a href="../account-settings/">アカウント設定</a>
    <a href="../help/">Help</a>
    <a href="#" id="logout">ログアウト</a>
  </nav>

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