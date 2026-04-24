/* assets/js/account-settings.js */
(() => {
  const pwdForm = document.getElementById('changePasswordForm');
  const pwdMsg = document.getElementById('pwdMsg');

  const delForm = document.getElementById('deleteAccountForm');
  const delMsg = document.getElementById('delMsg');

  function postJson(action, body) {
    return window.apiFetch(`../../api/auth.php?action=${action}`, { method: 'POST', body: JSON.stringify(body) });
  }

  if (pwdForm) {
    pwdForm.addEventListener('submit', async e => {
      e.preventDefault();
      window.ui.showMessage(pwdMsg, '処理中...', 'info');

      const data = await postJson('change-password', {
        current_password: pwdForm.current_password.value,
        new_password: pwdForm.new_password.value,
        confirm_password: pwdForm.confirm_password.value
      });

      window.ui.showMessage(pwdMsg, data.message, data.success ? 'success' : 'error');
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

      window.ui.showMessage(delMsg, '処理中...', 'info');

      const data = await postJson('delete-account', {
        password: delForm.password.value
      });

      window.ui.showMessage(delMsg, data.message, data.success ? 'success' : 'error');

      if (data.success) {
        setTimeout(() => {
          // 削除成功後はログイン画面へ
          window.location.href = '../../index.php';
        }, 1000);
      }
    });
  }
})();
