<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <!-- LaravelのCSRFトークンを埋め込む -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>ログイン – 管理パネル</title>
  <!-- CSSは後ほど public/assets/css/ に配置します -->
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="login-wrapper">
  <div class="login-box">
    <h2 style="text-align:center;margin-bottom:1.5rem;">管理者ログイン</h2>
    <form id="loginForm">
      <label for="username">ユーザ名</label>
      <input type="text" id="username" name="username" class="input" required placeholder="admin">

      <label style="margin-top:1rem;" for="password">パスワード</label>
      <div style="position:relative;">
        <input type="password" id="password" name="password" class="input" required placeholder="********">
        <span id="togglePwd" style="
          position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);
          cursor:pointer;color:#666;font-size:0.9rem;">👁︎</span>
      </div>

      <button type="submit" class="btn" style="width:100%;margin-top:1.5rem;">ログイン</button>
    </form>
    <p id="msg" style="color:#e11d48;margin-top:0.75rem;font-size:0.9rem;"></p>
    <div class="form-links" style="margin-top:1rem;text-align:center;">
      <a href="{{ url('/register') }}" style="margin-right:1rem;">アカウント作成</a>
      <a href="{{ url('/reset') }}">パスワードを忘れた場合</a>
    </div>
  </div>

  <script>
    const form = document.getElementById('loginForm');
    const msg = document.getElementById('msg');
    const pwdToggle = document.getElementById('togglePwd');
    const pwdInput = document.getElementById('password');

    const showMessage = (text, type) => {
      msg.textContent = text;
      msg.style.color = type === 'success' ? '#10b981' : (type === 'error' ? '#e11d48' : '#3b82f6');
    };

    pwdToggle.addEventListener('click', () => {
      const type = pwdInput.type === 'password' ? 'text' : 'password';
      pwdInput.type = type;
      pwdToggle.textContent = type === 'password' ? '👁︎' : '🙈';
    });

    form.addEventListener('submit', async e => {
      e.preventDefault();
      showMessage('ログイン中...', 'info');

      try {
          const response = await fetch('{{ url('/login') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            // LaravelのCSRFトークンをヘッダーにセット
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            username: form.username.value.trim(),
            password: form.password.value
          })
        });

        const data = await response.json();
        if (response.ok && data.success) {
          showMessage('ログインに成功しました。', 'success');
          setTimeout(() => window.location.href = '{{ url('/dashboard') }}', 500); // 成功時はダッシュボードへ
        } else {
          showMessage(data.message || 'ログインに失敗しました', 'error');
        }
      } catch (error) {
        showMessage('ネットワークエラーが発生しました', 'error');
      }
    });
  </script>
</body>
</html>
