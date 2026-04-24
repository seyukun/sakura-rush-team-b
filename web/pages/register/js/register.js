/* pages/register/js/register.js */
(() => {
  const form = document.getElementById('registerForm');
  const msg = document.getElementById('msg');

  const showMessage = (text, type) => {
    msg.textContent = text;
    if (type === 'info') msg.style.color = '#3b82f6';
    else if (type === 'success') msg.style.color = '#10b981';
    else if (type === 'error') msg.style.color = '#e11d48';
  };

  form.addEventListener('submit', async e => {
    e.preventDefault();
    showMessage('アカウントを作成しています...', 'info');

    const response = await fetch('../../api/auth.php?action=register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: form.username.value.trim(),
        email: form.email.value.trim(),
        password: form.password.value,
        confirm_password: form.confirm_password.value
      })
    });

    const data = await response.json();
    showMessage(data.message || '処理が完了しました。', data.success ? 'success' : 'error');

    if (data.success) {
      setTimeout(() => {
        window.location.href = '../../index.php';
      }, 1000);
    }
  });
})();
