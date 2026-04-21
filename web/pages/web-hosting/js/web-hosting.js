document.addEventListener('DOMContentLoaded', async () => {
    // ステータスの初期読み込み
    try {
        const res = await fetch('../../api/web-hosting.php?action=status');
        const json = await res.json();
        if (json.success && json.data) {
            const phpVersionSelect = document.getElementById('phpVersion');
            if (phpVersionSelect && json.data.php_version) {
                phpVersionSelect.value = json.data.php_version;
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

            wpMsg.style.color = '#3b82f6';
            wpMsg.textContent = `${domain} へのインストール準備中...`;

            try {
                const response = await fetch('../../api/web-hosting.php?action=install-wp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ domain, title })
                });
                const result = await response.json();
                
                if (result.success) {
                    wpMsg.style.color = 'green';
                    wpMsg.textContent = result.message;
                    wpInstallForm.reset();
                } else {
                    wpMsg.style.color = 'red';
                    wpMsg.textContent = result.message || 'インストールに失敗しました。';
                }
            } catch (err) {
                wpMsg.style.color = 'red';
                wpMsg.textContent = '通信エラーが発生しました。';
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
            phpMsg.style.color = '#3b82f6';
            phpMsg.textContent = '更新中...';

            try {
                const response = await fetch('../../api/web-hosting.php?action=update-php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ version })
                });
                const result = await response.json();
                
                if (result.success) {
                    phpMsg.style.color = 'green';
                    phpMsg.textContent = result.message;
                } else {
                    phpMsg.style.color = 'red';
                    phpMsg.textContent = result.message || '更新に失敗しました。';
                }
                setTimeout(() => {
                    phpMsg.textContent = '';
                }, 3000);
            } catch (err) {
                phpMsg.style.color = 'red';
                phpMsg.textContent = '通信エラーが発生しました。';
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