/* pages/mail-hosting/js/mail-hosting.js */
(() => {
  const createMailForm = document.getElementById('createMailForm');
  const mailMsg = document.getElementById('mailMsg');
  const mailList = document.getElementById('mailList');
  const domainSelect = document.getElementById('mail_domain');

  // ドメイン一覧を取得してセレクトボックスにセットする関数
  async function loadDomains() {
    if (!domainSelect) return;
    try {
      const response = await fetch('../../api/mail-hosting.php?action=domains');
      const data = await response.json();
      
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
          option.dataset.name = domain.name; // メールアドレス結合用に保持
          domainSelect.appendChild(option);
        });
      } else {
        domainSelect.innerHTML = '<option value="">読み込みエラー</option>';
      }
    } catch (e) {
      domainSelect.innerHTML = '<option value="">通信エラー</option>';
    }
  }

  // メール一覧を取得して表示する関数
  async function loadMails() {
    try {
      const response = await fetch('../../api/mail-hosting.php?action=list');
      const data = await response.json();
      
      if (data.success) {
        mailList.innerHTML = '';
        if (data.emails.length === 0) {
          mailList.innerHTML = '<li style="color: #999;">メールアカウントは登録されていません</li>';
          return;
        }
        
        data.emails.forEach(email => {
          const li = document.createElement('li');
          li.style.display = 'flex';
          li.style.justifyContent = 'space-between';
          li.style.alignItems = 'center';
          li.style.padding = '0.5rem 0';
          li.style.borderBottom = '1px solid #e5e7eb';
          
          const emailText = document.createElement('span');
          emailText.textContent = email.email;
          
          const deleteBtn = document.createElement('button');
          deleteBtn.textContent = '削除';
          deleteBtn.className = 'btn';
          deleteBtn.style.background = '#ef4444';
          deleteBtn.style.padding = '0.25rem 0.5rem';
          deleteBtn.style.fontSize = '0.8rem';
          deleteBtn.onclick = () => deleteMail(email.id);
          
          li.appendChild(emailText);
          li.appendChild(deleteBtn);
          mailList.appendChild(li);
        });
      } else {
        mailList.innerHTML = `<li style="color: #e11d48;">${data.message}</li>`;
      }
    } catch (e) {
      mailList.innerHTML = '<li style="color: #e11d48;">読み込みに失敗しました</li>';
    }
  }

  // メール削除関数
  async function deleteMail(id) {
    if (!confirm('本当にこのメールアドレスを削除しますか？')) return;
    
    try {
      const response = await fetch('../../api/mail-hosting.php?action=delete', {
        method: 'DELETE',
        credentials: 'include', // クッキーを含める
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.csrfToken
        },
        body: JSON.stringify({ id })
      });
      const data = await response.json();
      
      if (data.success) {
        loadMails();
      } else {
        alert(data.message || '削除に失敗しました');
      }
    } catch (e) {
      alert('通信エラーが発生しました');
    }
  }

  // 新規メールアドレス作成時の処理
  if (createMailForm) {
    createMailForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      mailMsg.textContent = '作成中...';
      mailMsg.style.color = '#3b82f6';
      
      const mailUser = createMailForm.mail_user.value.trim();
      const domainSelectEl = createMailForm.domain_id;
      const domainId = domainSelectEl.value;
      const password = createMailForm.mail_password.value;
      
      if (!domainId) {
        mailMsg.textContent = 'ドメインを選択してください';
        mailMsg.style.color = '#e11d48';
        return;
      }
      const domainName = domainSelectEl.options[domainSelectEl.selectedIndex].dataset.name;
      const email = `${mailUser}@${domainName}`;
      
      try {
        const response = await fetch('../../api/mail-hosting.php?action=create', {
          method: 'POST',
          credentials: 'include', // クッキーを含める
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': window.csrfToken
          },
          body: JSON.stringify({
            email: email,
            domain_id: parseInt(domainId, 10),
            password: password
          })
        });
        const data = await response.json();
        
        mailMsg.textContent = data.message;
        mailMsg.style.color = data.success ? '#10b981' : '#e11d48';
        
        if (data.success) {
          createMailForm.reset();
          loadMails(); // 登録後、一覧を再読み込みして表示を更新する
        }
      } catch (error) {
        mailMsg.textContent = '通信エラーが発生しました';
        mailMsg.style.color = '#e11d48';
      }
    });
  }

  // 初期読み込み時にメール一覧を表示
  loadMails();
  loadDomains();
})();