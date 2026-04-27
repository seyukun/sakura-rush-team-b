@extends('layouts.app')

@section('title', 'アカウント設定 – 管理パネル')

@section('content')
  <h1 style="margin-bottom:1.5rem;">アカウント設定</h1>

  <div class="cards-grid">
    <!-- パスワード変更 -->
    <div class="card">
      <h3 style="margin-bottom:1rem;">パスワード変更</h3>
      <form id="changePasswordForm">
        <label for="current_password">現在のパスワード</label>
        <input type="password" id="current_password" name="current_password" class="input" style="margin-bottom:1rem;" required>

        <label for="new_password">新しいパスワード</label>
        <input type="password" id="new_password" name="new_password" class="input" style="margin-bottom:1rem;" required>

        <label for="confirm_password">新しいパスワード（確認）</label>
        <input type="password" id="confirm_password" name="confirm_password" class="input" style="margin-bottom:1rem;" required>

        <button type="submit" class="btn" style="background:#10b981;">パスワードを変更</button>
      </form>
      <p id="pwdMsg" style="margin-top:1rem;font-weight:bold;"></p>
    </div>

    <!-- アカウント削除 -->
    <div class="card" style="border:1px solid #fca5a5;">
      <h3 style="margin-bottom:1rem;color:#ef4444;">アカウントの削除</h3>
      <p style="margin-bottom:1rem;color:#4b5563;">アカウントを削除すると、すべてのデータが完全に消去されます。この操作は取り消せません。</p>
      <form id="deleteAccountForm">
        <label for="del_password">パスワード</label>
        <input type="password" id="del_password" name="password" class="input" style="margin-bottom:1rem;" required placeholder="確認のためパスワードを入力">
        <button type="submit" class="btn" style="background:#ef4444;">アカウントを削除する</button>
      </form>
      <p id="delMsg" style="margin-top:1rem;font-weight:bold;"></p>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    const pwdForm = document.getElementById('changePasswordForm');
    const pwdMsg = document.getElementById('pwdMsg');
    const delForm = document.getElementById('deleteAccountForm');
    const delMsg = document.getElementById('delMsg');

    const showMessage = (el, text, type) => {
      el.textContent = text;
      el.style.color = type === 'success' ? '#10b981' : (type === 'error' ? '#e11d48' : '#3b82f6');
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

    // パスワード変更
    pwdForm.addEventListener('submit', async e => {
      e.preventDefault();
      showMessage(pwdMsg, '処理中...', 'info');
      try {
        const data = await postJson('{{ url('/change-password') }}', {
          current_password: pwdForm.current_password.value,
          new_password: pwdForm.new_password.value,
          confirm_password: pwdForm.confirm_password.value
        });
        showMessage(pwdMsg, data.message, data.success ? 'success' : 'error');
        if (data.success) pwdForm.reset();
      } catch (err) {
        showMessage(pwdMsg, '通信エラーが発生しました', 'error');
      }
    });

    // アカウント削除
    delForm.addEventListener('submit', async e => {
      e.preventDefault();
      if (!confirm('本当にアカウントを削除しますか？この操作は取り消せません。')) return;
      showMessage(delMsg, '処理中...', 'info');
      try {
        const data = await postJson('{{ url('/delete-account') }}', {
          password: delForm.password.value
        });
        showMessage(delMsg, data.message, data.success ? 'success' : 'error');
        if (data.success) {
          setTimeout(() => window.location.href = '{{ url('/') }}', 1000); // 削除後はログイン画面へ
        }
      } catch (err) {
        showMessage(delMsg, '通信エラーが発生しました', 'error');
      }
    });
  </script>
@endpush
