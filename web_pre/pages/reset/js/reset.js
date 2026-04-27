/* assets/js/reset.js */
(() => {
  const requestForm = document.getElementById('requestResetForm');
  const resetForm = document.getElementById('resetPasswordForm');
  const msg = document.getElementById('msg');

  const showMessage = (text, type) => {
    msg.textContent = text;
    if (type === 'info') msg.style.color = '#3b82f6';
    else if (type === 'success') msg.style.color = '#10b981';
    else if (type === 'error') msg.style.color = '#e11d48';
  };

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
    showMessage('トークンを発行しています...', 'info');

    const data = await postJson('reset-request', {
      username: requestForm.username.value.trim()
    });

    if (data.success && data.token) {
      showMessage((data.message || '処理が完了しました。') + ` トークン: ${data.token}`, 'success');
    } else {
      showMessage(data.message || '処理が完了しました。', data.success ? 'success' : 'error');
    }
  });

  resetForm.addEventListener('submit', async e => {
    e.preventDefault();
    showMessage('パスワードをリセットしています...', 'info');

    const data = await postJson('reset-password', {
      username: resetForm.username.value.trim(),
      token: resetForm.token.value.trim(),
      new_password: resetForm.new_password.value,
      confirm_password: resetForm.confirm_password.value
    });

    showMessage(data.message || '処理が完了しました。', data.success ? 'success' : 'error');
  });
})();
