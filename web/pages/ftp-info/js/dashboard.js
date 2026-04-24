/* assets/js/dashboard.js */
(() => {
  // ---- 1️⃣ サーバー側のセッション状態を確認 ----
  async function checkSession() {
    try {
      const response = await fetch('../../api/auth.php?action=status', {
        method: 'GET',
        credentials: 'include' // クッキーを含める
      });

      const data = await response.json();

      if (!response.ok || !data.logged_in) {
        // セッションなし → ログインページへリダイレクト
        window.location.href = '../../index.php';
        return null;
      }

      return data;
    } catch (error) {
      console.error('Session check error:', error);
      window.location.href = '../../index.php';
      return null;
    }
  }

  // セッション確認を実行
  checkSession().then(data => {
    if (!data) return;

    // APIから取得したCSRFトークンを保持する
    window.csrfToken = data.csrf_token;

    // ---- 2️⃣ 表示するユーザ名 ----
    const userNameEl = document.getElementById('userName');
    if (userNameEl) {
      userNameEl.textContent = data.username;
    }

    // ---- 2.5️⃣ SFTP接続情報を取得 ----
    async function loadSftpInfo() {
      try {
        const response = await fetch('../../api/sftp-info.php?action=info', {
          method: 'GET',
          credentials: 'include'
        });

        const sftpData = await response.json();

        if (response.ok && sftpData.success) {
          const portEl = document.getElementById('sftpPort');
          const passwordEl = document.getElementById('sftpPassword');
          
          if (portEl) portEl.textContent = sftpData.sftp_port;
          if (passwordEl) passwordEl.textContent = sftpData.sftp_password;
        } else {
          console.warn('SFTP info API error:', sftpData);
          const portEl = document.getElementById('sftpPort');
          if (portEl) portEl.innerHTML = `<span style="color: red;">エラー: ${sftpData.message || 'SFTP情報取得エラー'}</span>`;
        }
      } catch (error) {
        console.error('SFTP info fetch error:', error);
        const portEl = document.getElementById('sftpPort');
        if (portEl) portEl.innerHTML = `<span style="color: red;">エラー: ${error.message}</span>`;
      }
    }

    // SFTP情報を読み込む
    loadSftpInfo();

    // ---- 3️⃣ ログアウト処理 ----
    const logoutEl = document.getElementById('logout');
    if (logoutEl) {
      logoutEl.addEventListener('click', async e => {
        e.preventDefault();

        try {
          const response = await fetch('../../api/auth.php?action=logout', {
            method: 'POST',
            credentials: 'include', // クッキーを含める
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': window.csrfToken
            }
          });

          if (response.ok) {
            // ログアウト成功 → ログインページへリダイレクト
            window.location.href = '../../index.php';
          }
        } catch (error) {
          console.error('Logout error:', error);
          // エラーでもログインページへ
          window.location.href = '../../index.php';
        }
      });
    }
  });
})();
