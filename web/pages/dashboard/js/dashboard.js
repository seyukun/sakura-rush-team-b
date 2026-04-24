/* assets/js/dashboard.js */
(() => {
  // ---- 0️⃣ 共通のAPI Fetchラッパー ----
  window.apiFetch = async (url, options = {}) => {
    options.credentials = 'include';
    options.headers = options.headers || {};
    if (options.method && options.method.toUpperCase() !== 'GET') {
      options.headers['X-CSRF-Token'] = window.csrfToken || '';
      if (!(options.body instanceof FormData)) {
        options.headers['Content-Type'] = 'application/json';
      }
    }
    const res = await fetch(url, options);
    return res.json();
  };

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

    // ---- 2.5️⃣ メールアカウント数を取得 ----
    async function loadMailCount() {
      try {
        const mailData = await window.apiFetch('../../api/mail-hosting.php?action=count');

        if (mailData.success) {
          const mailCountEl = document.getElementById('mailCount');
          if (mailCountEl) {
            mailCountEl.textContent = mailData.count;
          }
        } else {
          console.warn('Mail count API error:', mailData);
        }
      } catch (error) {
        console.error('Mail count fetch error:', error);
      }
    }

    // メール数を読み込む
    loadMailCount();

    // ---- 2.6️⃣ SFTP接続情報を取得 (各ページで要素がある場合のみ) ----
    async function loadSftpInfo() {
      const portEl = document.getElementById('sftpPort');
      const passwordEl = document.getElementById('sftpPassword');
      if (!portEl) return;
      
      try {
        const sftpData = await window.apiFetch('../../api/sftp-info.php?action=info');
        if (sftpData.success) {
          portEl.textContent = sftpData.sftp_port;
          if (passwordEl) passwordEl.textContent = sftpData.sftp_password;
        } else {
          portEl.innerHTML = `<span style="color: red;">エラー: ${sftpData.message || '情報取得エラー'}</span>`;
        }
      } catch (error) {
        portEl.innerHTML = `<span style="color: red;">エラー: ${error.message}</span>`;
      }
    }
    loadSftpInfo();

    // ---- 3️⃣ ログアウト処理 ----
    const logoutEl = document.getElementById('logout');
    if (logoutEl) {
      logoutEl.addEventListener('click', async e => {
        e.preventDefault();

        try {
          const data = await window.apiFetch('../../api/auth.php?action=logout', { method: 'POST' });

          if (data && data.success) {
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
