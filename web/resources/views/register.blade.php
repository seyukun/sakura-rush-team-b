<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>アカウント作成 – 管理パネル</title>
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="login-wrapper">
  <div class="login-box">
    <h2 style="text-align:center;margin-bottom:1.5rem;">アカウント作成</h2>
    <form id="registerForm">
      <label for="username">ユーザ名</label>
      <input type="text" id="username" name="username" class="input" required placeholder="admin">

      <label style="margin-top:1rem;" for="email">メールアドレス</label>
      <input type="email" id="email" name="email" class="input" required placeholder="admin@example.com">

      <label style="margin-top:1rem;" for="password">パスワード</label>
      <input type="password" id="password" name="password" class="input" required placeholder="********">

      <label style="margin-top:1rem;" for="confirm_password">パスワード（確認）</label>
      <input type="password" id="confirm_password" name="confirm_password" class="input" required placeholder="********">

      <button type="submit" class="btn" style="width:100%;margin-top:1.5rem;">作成</button>
    </form>
    <p id="msg" style="color:#e11d48;margin-top:0.75rem;font-size:0.9rem;"></p>
    <div class="form-links" style="margin-top:1rem;text-align:center;">
      <a href="{{ url('/') }}" style="margin-right:1rem;">ログイン画面へ戻る</a>
    </div>
  </div>

  <script>
    const form = document.getElementById('registerForm');
    const msg = document.getElementById('msg');

    const showMessage = (text, type) => {
      msg.textContent = text;
      msg.style.color = type === 'success' ? '#10b981' : (type === 'error' ? '#e11d48' : '#3b82f6');
    };

    form.addEventListener('submit', async e => {
      e.preventDefault();
      showMessage('アカウントを作成しています...', 'info');

      try {
        const response = await fetch('{{ url('/register') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            username: form.username.value.trim(),
            email: form.email.value.trim(),
            password: form.password.value,
            confirm_password: form.confirm_password.value
          })
        });

        const data = await response.json();
        
        if (response.ok && data.success) {
            showMessage(data.message || '処理が完了しました。', 'success');
            setTimeout(() => {
                window.location.href = '{{ url('/') }}'; // 成功したらログイン画面へ
            }, 1000);
        } else {
            showMessage(data.message || 'エラーが発生しました', 'error');
        }
      } catch (error) {
          showMessage('ネットワークエラーが発生しました', 'error');
      }
    });
  </script>
</body>
</html>
