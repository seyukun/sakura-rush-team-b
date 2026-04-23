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

    // ---- 2.5️⃣ メールアドレス一覧を取得 ----
    async function loadMailList() {
      const mailList = document.getElementById('mailList');
      try {
        const response = await fetch('../../api/mail-hosting.php?action=list', {
          method: 'GET',
          credentials: 'include'
        });

        const mailData = await response.json();

        if (response.ok && mailData.success) {
          if (mailList) {
            if (mailData.emails.length === 0) {
              mailList.innerHTML = '<li style="color: #999;">メールアドレスが登録されていません</li>';
            } else {
              mailList.innerHTML = mailData.emails
                .map(email => `<li>${email.email}</li>`)
                .join('');
            }
          }
        } else {
          console.warn('Mail list API error:', mailData);
          if (mailList) {
            mailList.innerHTML = `<li style="color: red;">エラー: ${mailData.message || 'メールアドレス取得エラー'}</li>`;
          }
        }
      } catch (error) {
        console.error('Mail list fetch error:', error);
        if (mailList) {
          mailList.innerHTML = `<li style="color: red;">エラー: ${error.message}</li>`;
        }
      }
    }

    // メール一覧を読み込む
    loadMailList();

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
