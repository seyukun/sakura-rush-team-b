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

    // ---- 2️⃣ 表示するユーザ名 ----
    const userNameEl = document.getElementById('userName');
    if (userNameEl) {
      userNameEl.textContent = data.username;
    }

    // ---- 3️⃣ ログアウト処理 ----
    const logoutEl = document.getElementById('logout');
    if (logoutEl) {
      logoutEl.addEventListener('click', async e => {
        e.preventDefault();

        try {
          const response = await fetch('../../api/auth.php?action=logout', {
            method: 'POST',
            credentials: 'include' // クッキーを含める
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
