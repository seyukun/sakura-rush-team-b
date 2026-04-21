<?php
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$navItems = [
    ['href' => BASE_URL . '/dashboard/', 'label' => '概要', 'dir' => 'dashboard'],
    ['href' => BASE_URL . '/account-settings/', 'label' => 'アカウント設定', 'dir' => 'account-settings'],
    ['href' => BASE_URL . '/ftp-info/', 'label' => 'FTP情報', 'dir' => 'ftp-info'],
    ['href' => BASE_URL . '/web-hosting/', 'label' => 'Webホスティング', 'dir' => 'web-hosting'],
    ['href' => BASE_URL . '/mail-hosting/', 'label' => 'メールホスティング', 'dir' => 'mail-hosting'],
    ['href' => BASE_URL . '/help/', 'label' => 'ヘルプ', 'dir' => 'help'],
];
?>
<nav class="sidebar">
  <h2 class="sidebar-title">管理パネル</h2>
  <?php foreach ($navItems as $item): ?>
    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>" class="<?= $current_dir === $item['dir'] ? 'active' : '' ?>"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
  <?php endforeach; ?>
  <button id="logout" class="btn btn-ghost">ログアウト</button>
</nav>
