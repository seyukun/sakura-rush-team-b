<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>パスワードリセット – 管理パネル</title>
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="login-wrapper">
  <div class="login-box">
    <h2 style="text-align:center;margin-bottom:1.5rem;">パスワードリセット</h2>

    <!-- トークン発行フォーム -->
    <form id="requestResetForm" style="margin-bottom: 2rem;">
      <label for="req_username">ユーザ名</label>
      <input type="text" id="req_username" name="username" class="input" required placeholder="admin">
      <button type="submit" class="btn" style="width:100%;margin-top:1rem;">トークンを発行</button>
    </form>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin-bottom: 2rem;">

    <!-- パスワード再設定フォーム -->
    <form id="resetPasswordForm">
      <label for="res_username">ユーザ名</label>
      <input type="text" id="res_username" name="username" class="input" required placeholder="admin">

      <label style="margin-top:1rem;" for="token">トークン</label>
      <input type="text" id="token" name="token" class="input" required placeholder="発行されたトークン">

      <label style="margin-top:1rem;" for="new_password">新しいパスワード</label>
      <input type="password" id="new_password" name="new_password" class="input" required placeholder="********">

      <label style="margin-top:1rem;" for="confirm_password">新しいパスワード（確認）</label>
      <input type="password" id="confirm_password" name="confirm_password" class="input" required placeholder="********">

      <button type="submit" class="btn" style="width:100%;margin-top:1.5rem;background:#10b981;">パスワードをリセット</button>
    </form>

    <p id="msg" style="color:#e11d48;margin-top:1.5rem;font-size:0.9rem;text-align:center;font-weight:bold;"></p>
    <div class="form-links" style="margin-top:1rem;text-align:center;">
      <a href="{{ url('/') }}">ログイン画面へ戻る</a>
    </div>
  </div>

  <script>
    const requestForm = document.getElementById('requestResetForm');
    const resetForm = document.getElementById('resetPasswordForm');
    const msg = document.getElementById('msg');

    const showMessage = (text, type) => {
      msg.textContent = text;
      msg.style.color = type === 'success' ? '#10b981' : (type === 'error' ? '#e11d48' : '#3b82f6');
    };

    async function postJson(url, body) {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(body)
      });
      return response.json();
    }

    requestForm.addEventListener('submit', async e => {
      e.preventDefault();
      showMessage('トークンを発行しています...', 'info');
      try {
        const data = await postJson('{{ url('/reset-request') }}', { username: requestForm.username.value.trim() });
        if (data.success && data.token) {
          showMessage((data.message || '処理が完了しました。') + ` トークン: ${data.token}`, 'success');
          // 親切設計：自動的に下のフォームへ値をセットする
          resetForm.username.value = requestForm.username.value.trim();
          resetForm.token.value = data.token;
          resetForm.new_password.focus();
        } else {
          showMessage(data.message || 'エラーが発生しました', 'error');
        }
      } catch (err) {
        showMessage('通信エラーが発生しました', 'error');
      }
    });

    resetForm.addEventListener('submit', async e => {
      e.preventDefault();
      showMessage('パスワードをリセットしています...', 'info');
      try {
        const data = await postJson('{{ url('/reset-password') }}', {
          username: resetForm.username.value.trim(),
          token: resetForm.token.value.trim(),
          new_password: resetForm.new_password.value,
          confirm_password: resetForm.confirm_password.value
        });
        showMessage(data.message || '処理が完了しました。', data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => window.location.href = '{{ url('/') }}', 1500);
      } catch (err) {
        showMessage('通信エラーが発生しました', 'error');
      }
    });
  </script>
</body>
</html>
