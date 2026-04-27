/* assets/js/login.js */
(() => {
  const form = document.getElementById('loginForm');
  const msg = document.getElementById('msg');
  const pwdToggle = document.getElementById('togglePwd');
  const pwdInput = document.getElementById('password');

  const showMessage = (text, type) => {
    msg.textContent = text;
    if (type === 'info') msg.style.color = '#3b82f6';
    else if (type === 'success') msg.style.color = '#10b981';
    else if (type === 'error') msg.style.color = '#e11d48';
  };

  // 目視アイコンでパスワード表示/非表示切替
  pwdToggle.addEventListener('click', () => {
    const type = pwdInput.type === 'password' ? 'text' : 'password';
    pwdInput.type = type;
    pwdToggle.textContent = type === 'password' ? '👁︎' : '🙈';
  });

  // サーバー側でセッションベース認証を行う
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const username = form.username.value.trim();
    const password = form.password.value;

    // ローディング状態
    showMessage('ログイン中...', 'info');

    try {
      // PHP API エンドポイントにPOST
      const response = await fetch('api/auth.php?action=login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include', // クッキーを含める
        body: JSON.stringify({ username, password })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        // ログイン成功
        showMessage('ログインに成功しました。ダッシュボードへ移動します...', 'success');
        // セッションクッキーがサーバー側で設定されるため、
        // ローカルストレージには保存せず直接リダイレクト
        setTimeout(() => {
          window.location.href = 'pages/dashboard/';
        }, 500);
      } else {
        // ログイン失敗
        showMessage(data.message || 'ログインに失敗しました', 'error');
      }
    } catch (error) {
      showMessage('ネットワークエラーが発生しました', 'error');
      console.error('Login error:', error);
    }
  });
})();
