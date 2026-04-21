/* assets/js/reset.js */
(() => {
  const requestForm = document.getElementById('requestResetForm');
  const resetForm = document.getElementById('resetPasswordForm');
  const msg = document.getElementById('msg');

  async function postJson(action, body) {
    const response = await fetch(`../../api/auth.php?action=${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    return response.json();
  }

  requestForm.addEventListener('submit', async e => {
    e.preventDefault();
    msg.textContent = 'トークンを発行しています...';
    msg.style.color = '#3b82f6';

    const data = await postJson('reset-request', {
      username: requestForm.username.value.trim()
    });

    msg.textContent = data.message || '処理が完了しました。';
    msg.style.color = data.success ? '#10b981' : '#e11d48';
    if (data.success && data.token) {
      msg.textContent += ` トークン: ${data.token}`;
    }
  });

  resetForm.addEventListener('submit', async e => {
    e.preventDefault();
    msg.textContent = 'パスワードをリセットしています...';
    msg.style.color = '#3b82f6';

    const data = await postJson('reset-password', {
      username: resetForm.username.value.trim(),
      token: resetForm.token.value.trim(),
      new_password: resetForm.new_password.value,
      confirm_password: resetForm.confirm_password.value
    });

    msg.textContent = data.message || '処理が完了しました。';
    msg.style.color = data.success ? '#10b981' : '#e11d48';
  });
})();
