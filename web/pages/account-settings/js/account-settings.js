/* assets/js/account-settings.js */
(() => {
  const pwdForm = document.getElementById('changePasswordForm');
  const pwdMsg = document.getElementById('pwdMsg');

  const delForm = document.getElementById('deleteAccountForm');
  const delMsg = document.getElementById('delMsg');

  async function postJson(action, body) {
    const response = await fetch(`../../api/auth.php?action=${action}`, {
      method: 'POST',
      credentials: 'include', // クッキーを含める
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.csrfToken
      },
      body: JSON.stringify(body)
    });
    return response.json();
  }

  if (pwdForm) {
    pwdForm.addEventListener('submit', async e => {
      e.preventDefault();
      pwdMsg.textContent = '処理中...';
      pwdMsg.style.color = '#3b82f6';

      const data = await postJson('change-password', {
        current_password: pwdForm.current_password.value,
        new_password: pwdForm.new_password.value,
        confirm_password: pwdForm.confirm_password.value
      });

      pwdMsg.textContent = data.message;
      pwdMsg.style.color = data.success ? '#10b981' : '#e11d48';
      if (data.success) {
        pwdForm.reset();
      }
    });
  }

  if (delForm) {
    delForm.addEventListener('submit', async e => {
      e.preventDefault();
      
      if (!confirm('本当にアカウントを削除しますか？この操作は取り消せません。')) {
        return;
      }

      delMsg.textContent = '処理中...';
      delMsg.style.color = '#3b82f6';

      const data = await postJson('delete-account', {
        password: delForm.password.value
      });

      delMsg.textContent = data.message;
      delMsg.style.color = data.success ? '#10b981' : '#e11d48';

      if (data.success) {
        setTimeout(() => {
          // 削除成功後はログイン画面へ
          window.location.href = '../../index.php';
        }, 1000);
      }
    });
  }
})();
