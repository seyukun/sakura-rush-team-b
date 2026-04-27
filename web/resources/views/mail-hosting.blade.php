@extends('layouts.app')

@section('title', 'メールホスティング – 管理パネル')

@section('content')
  <h1 style="margin-bottom:1.5rem;">メールホスティング</h1>

  <div class="cards-grid">
    <!-- 新規作成 -->
    <div class="card">
      <h3 style="margin-bottom:1rem;">メールアカウント作成</h3>
      <form id="createMailForm">
        <label for="mail_user">メールアカウント</label>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
          <input type="text" id="mail_user" name="mail_user" class="input" required placeholder="info" style="flex:1;">
          <span>@</span>
          <select id="mail_domain" name="domain_id" class="input" required style="flex:1;">
            <option value="">読み込み中...</option>
          </select>
        </div>

        <label for="mail_password">パスワード</label>
        <input type="password" id="mail_password" name="mail_password" class="input" style="margin-bottom:1rem;" required>

        <button type="submit" class="btn" style="background:#10b981;">作成</button>
      </form>
      <p id="mailMsg" style="margin-top:1rem;font-weight:bold;"></p>
    </div>

    <!-- 一覧 -->
    <div class="card">
      <h3 style="margin-bottom:1rem;">登録済みアカウント</h3>
      <ul id="mailList" style="list-style: none; padding: 0;">
        <li style="color: #999;">読み込み中...</li>
      </ul>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    const createMailForm = document.getElementById('createMailForm');
    const mailMsg = document.getElementById('mailMsg');
    const mailList = document.getElementById('mailList');
    const domainSelect = document.getElementById('mail_domain');

    const showMessage = (el, text, type) => {
      el.textContent = text;
      el.style.color = type === 'success' ? '#10b981' : (type === 'error' ? '#e11d48' : '#3b82f6');
    };

    async function fetchApi(url, options = {}) {
      options.headers = options.headers || {};
      options.headers['Accept'] = 'application/json';
      options.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
      if (options.body && !(options.body instanceof FormData)) {
        options.headers['Content-Type'] = 'application/json';
      }
      const res = await fetch(url, options);
      return res.json();
    }

    // ドメイン一覧の取得
    async function loadDomains() {
      try {
        const data = await fetchApi('{{ url('/api/mail-hosting/domains') }}');
        if (data.success) {
          domainSelect.innerHTML = '';
          if (data.domains.length === 0) {
            domainSelect.innerHTML = '<option value="">ドメインが登録されていません</option>';
            return;
          }
          data.domains.forEach(domain => {
            const option = document.createElement('option');
            option.value = domain.id;
            option.textContent = domain.name;
            option.dataset.name = domain.name;
            domainSelect.appendChild(option);
          });
        }
      } catch (e) {
        domainSelect.innerHTML = '<option value="">通信エラー</option>';
      }
    }

    // メール一覧の取得
    async function loadMails() {
      try {
        const data = await fetchApi('{{ url('/api/mail-hosting/list') }}');
        if (data.success) {
          mailList.innerHTML = '';
          if (data.emails.length === 0) {
            mailList.innerHTML = '<li style="color: #999;">メールアカウントは登録されていません</li>';
            return;
          }
          data.emails.forEach(email => {
            const li = document.createElement('li');
            li.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;';
            
            const emailText = document.createElement('span');
            emailText.textContent = email.email;
            
            const deleteBtn = document.createElement('button');
            deleteBtn.textContent = '削除';
            deleteBtn.className = 'btn';
            deleteBtn.style.cssText = 'background: #ef4444; padding: 0.25rem 0.5rem; font-size: 0.8rem;';
            deleteBtn.onclick = () => deleteMail(email.id);
            
            li.appendChild(emailText);
            li.appendChild(deleteBtn);
            mailList.appendChild(li);
          });
        }
      } catch (e) {
        mailList.innerHTML = '<li style="color: #e11d48;">読み込みに失敗しました</li>';
      }
    }

    // 削除処理
    async function deleteMail(id) {
      if (!confirm('本当にこのメールアドレスを削除しますか？')) return;
      try {
        const data = await fetchApi('{{ url('/api/mail-hosting/delete') }}', { method: 'DELETE', body: JSON.stringify({ id }) });
        if (data.success) loadMails();
        else alert(data.message || '削除に失敗しました');
      } catch (e) { alert('通信エラーが発生しました'); }
    }

    // 作成処理
    createMailForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      showMessage(mailMsg, '作成中...', 'info');
      
      const mailUser = createMailForm.mail_user.value.trim();
      const domainId = domainSelect.value;
      const password = createMailForm.mail_password.value;
      
      if (!domainId) return showMessage(mailMsg, 'ドメインを選択してください', 'error');
      
      const domainName = domainSelect.options[domainSelect.selectedIndex].dataset.name;
      const email = `${mailUser}@${domainName}`;
      
      try {
        const data = await fetchApi('{{ url('/api/mail-hosting/create') }}', { method: 'POST', body: JSON.stringify({ email, domain_id: parseInt(domainId, 10), password }) });
        showMessage(mailMsg, data.message, data.success ? 'success' : 'error');
        if (data.success) { createMailForm.reset(); loadMails(); }
      } catch (error) { showMessage(mailMsg, '通信エラーが発生しました', 'error'); }
    });

    loadMails(); loadDomains();
  </script>
@endpush
