<?php
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$navItems = [
    ['href' => BASE_URL . '/pages/dashboard/', 'label' => 'ダッシュボード', 'dir' => 'dashboard'],
    ['href' => BASE_URL . '/pages/web-hosting/', 'label' => 'Web Hosting', 'dir' => 'web-hosting'],
    ['href' => BASE_URL . '/pages/file-manager/', 'label' => 'File Manager', 'dir' => 'file-manager'],
    ['href' => BASE_URL . '/pages/php-db-settings/', 'label' => 'PHP / DB 設定', 'dir' => 'php-db-settings'],
    ['href' => BASE_URL . '/pages/ftp-info/', 'label' => 'SFTP Information', 'dir' => 'ftp-info'],
    ['href' => BASE_URL . '/pages/mail-hosting/', 'label' => 'Mail Hosting', 'dir' => 'mail-hosting'],
    ['href' => BASE_URL . '/pages/account-settings/', 'label' => 'アカウント設定', 'dir' => 'account-settings'],
    ['href' => BASE_URL . '/pages/help/', 'label' => 'Help', 'dir' => 'help'],
];

// CSRFトークンを生成・取得
$csrf_token = generateCsrfToken();
?>
<script>
  // フロントエンドからのAPIリクエスト用にCSRFトークンをグローバル変数として保持
  window.csrfToken = "<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>";
</script>
<nav class="sidebar">
  <h2 style="margin-bottom:2rem;text-align:center;">Admin</h2>
  <?php foreach ($navItems as $item): ?>
    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="<?= $current_dir === $item['dir'] ? 'active' : '' ?>"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
  <?php endforeach; ?>
  <a href="#" id="logout">ログアウト</a>
</nav>
