document.addEventListener('DOMContentLoaded', async () => {
    // ステータスの初期読み込み
    try {
        const data = await window.apiFetch('../../api/web-hosting.php?action=status');
        if (data.success && data.data) {
            const phpVersionSelect = document.getElementById('phpVersion');
            if (phpVersionSelect && data.data.php_version) {
                phpVersionSelect.value = data.data.php_version;
            }
        }
    } catch (e) {
        console.error('Failed to fetch hosting status', e);
    }

    // WordPress Installer
    const wpInstallForm = document.getElementById('wpInstallForm');
    const wpMsg = document.getElementById('wpMsg');

    if (wpInstallForm) {
        wpInstallForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const domain = document.getElementById('wp_domain').value;
            const title = document.getElementById('wp_title').value;

            window.ui.showMessage(wpMsg, `${domain} へのインストール準備中...`, 'info');

            try {
                const result = await window.apiFetch('../../api/web-hosting.php?action=install-wp', {
                    method: 'POST',
                    body: JSON.stringify({ domain, title })
                });
                
                if (result.success) {
                    window.ui.showMessage(wpMsg, result.message, 'success');
                    wpInstallForm.reset();
                } else {
                    window.ui.showMessage(wpMsg, result.message || 'インストールに失敗しました。', 'error');
                }
            } catch (err) {
                window.ui.showMessage(wpMsg, '通信エラーが発生しました。', 'error');
            }
        });
    }

    // File Manager
    const btnFileManager = document.getElementById('btnFileManager');
    if (btnFileManager) {
        btnFileManager.addEventListener('click', () => {
            window.location.href = '../file-manager/';
        });
    }

    // PHP Settings
    const btnUpdatePhp = document.getElementById('btnUpdatePhp');
    const phpVersion = document.getElementById('phpVersion');
    const phpMsg = document.getElementById('phpMsg');

    if (btnUpdatePhp && phpVersion) {
        btnUpdatePhp.addEventListener('click', async () => {
            const version = phpVersion.value;
            window.ui.showMessage(phpMsg, '更新中...', 'info');

            try {
                const result = await window.apiFetch('../../api/web-hosting.php?action=update-php', {
                    method: 'POST',
                    body: JSON.stringify({ version })
                });
                
                if (result.success) {
                    window.ui.showMessage(phpMsg, result.message, 'success');
                } else {
                    window.ui.showMessage(phpMsg, result.message || '更新に失敗しました。', 'error');
                }
                setTimeout(() => {
                    phpMsg.textContent = '';
                }, 3000);
            } catch (err) {
                window.ui.showMessage(phpMsg, '通信エラーが発生しました。', 'error');
            }
        });
    }

    // Database Manager
    const btnDbManager = document.getElementById('btnDbManager');
    if (btnDbManager) {
        btnDbManager.addEventListener('click', () => {
            window.open('http://localhost/phpmyadmin', '_blank');
        });
    }
});