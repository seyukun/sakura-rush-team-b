/* assets/js/register.js */
(() => {
  const form = document.getElementById('registerForm');
  const msg = document.getElementById('msg');

  form.addEventListener('submit', async e => {
    e.preventDefault();
    msg.textContent = 'アカウントを作成しています...';
    msg.style.color = '#3b82f6';

    const response = await fetch('../../api/auth.php?action=register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: form.username.value.trim(),
        password: form.password.value,
        confirm_password: form.confirm_password.value
      })
    });

    const data = await response.json();
    msg.textContent = data.message || '処理が完了しました。';
    msg.style.color = data.success ? '#10b981' : '#e11d48';

    if (data.success) {
      setTimeout(() => {
        window.location.href = '../../index.php';
      }, 1000);
    }
  });
})();
