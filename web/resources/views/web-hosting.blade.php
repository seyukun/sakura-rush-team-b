@extends('layouts.app')

@section('title', 'Webホスティング – Admin')

@section('content')
  <div class="card" id="hostingInfoCard" style="margin-bottom: 1.5rem; display: none;">
    <h3 style="margin-bottom:1rem; color:#4b5563;">現在のサイト情報</h3>
    <div style="display: flex; align-items: center; gap: 0.5rem;">
        <strong>サイトURL:</strong>
        <span><a id="currentWpUrl" href="#" target="_blank" rel="noopener noreferrer" style="color: #3b82f6; text-decoration: underline;"></a></span>
    </div>
  </div>

  <h1 style="margin-bottom:1.5rem;">Webホスティング (WordPress設定)</h1>
  <p style="margin-bottom:1.5rem;">WordPressの自動セットアップを行います。以下の情報を入力してください。</p>

  <form id="wpSetupForm">
    <div class="cards-grid" style="margin-bottom: 1.5rem;">
      <!-- 左カラム: MariaDB 設定 -->
      <div class="card">
        <h3 style="margin-bottom:1.5rem; color:#4b5563;">MariaDB 設定</h3>
        
        <label for="mariadb_root_password" style="display: block; margin-bottom: 0.5rem;">Root パスワード</label>
        <input type="password" id="mariadb_root_password" name="mariadb_root_password" class="input" required style="width: 100%; margin-bottom: 1rem;" placeholder="rootpass">

        <label for="mariadb_database" style="display: block; margin-bottom: 0.5rem;">データベース名</label>
        <input type="text" id="mariadb_database" name="mariadb_database" class="input" required style="width: 100%; margin-bottom: 1rem;" placeholder="wordpress">

        <label for="mariadb_user" style="display: block; margin-bottom: 0.5rem;">データベースユーザー名</label>
        <input type="text" id="mariadb_user" name="mariadb_user" class="input" required style="width: 100%; margin-bottom: 1rem;" placeholder="wpuser">

        <label for="mariadb_password" style="display: block; margin-bottom: 0.5rem;">データベースパスワード</label>
        <input type="password" id="mariadb_password" name="mariadb_password" class="input" required style="width: 100%; margin-bottom: 1rem;" placeholder="wppass">
      </div>

      <!-- 右カラム: WordPress 設定 -->
      <div class="card">
        <h3 style="margin-bottom:1.5rem; color:#4b5563;">WordPress 設定</h3>

        <label for="wp_title" style="display: block; margin-bottom: 0.5rem;">サイトのタイトル (wp_title)</label>
        <input type="text" id="wp_title" name="wp_title" class="input" required placeholder="My Blog" style="width: 100%; margin-bottom: 1rem;">

        <h4 style="margin-bottom:1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">管理者設定</h4>

        <label for="wp_admin" style="display: block; margin-bottom: 0.5rem;">管理者ユーザー名 (wp_admin)</label>
        <input type="text" id="wp_admin" name="wp_admin" class="input" required placeholder="admin" style="width: 100%; margin-bottom: 1rem;">

        <label for="wp_admin_password" style="display: block; margin-bottom: 0.5rem;">管理者パスワード (wp_admin_password)</label>
        <input type="password" id="wp_admin_password" name="wp_admin_password" class="input" required minlength="8" placeholder="adminpass" style="width: 100%; margin-bottom: 1rem;">

        <label for="wp_admin_email" style="display: block; margin-bottom: 0.5rem;">管理者メールアドレス (wp_admin_email)</label>
        <input type="email" id="wp_admin_email" name="wp_admin_email" class="input" required placeholder="admin@example.local" style="width: 100%; margin-bottom: 1.5rem;">

        <h4 style="margin-bottom:1rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">一般ユーザー設定</h4>

        <label for="wp_username" style="display: block; margin-bottom: 0.5rem;">ユーザー名 (wp_username)</label>
        <input type="text" id="wp_username" name="wp_username" class="input" required placeholder="user1" style="width: 100%; margin-bottom: 1rem;">

        <label for="wp_email" style="display: block; margin-bottom: 0.5rem;">メールアドレス (wp_email)</label>
        <input type="email" id="wp_email" name="wp_email" class="input" required placeholder="user1@example.local" style="width: 100%; margin-bottom: 1rem;">

        <label for="wp_password" style="display: block; margin-bottom: 0.5rem;">パスワード (wp_password)</label>
        <input type="password" id="wp_password" name="wp_password" class="input" required placeholder="userpass" style="width: 100%; margin-bottom: 1rem;">

        <label for="wp_displayname" style="display: block; margin-bottom: 0.5rem;">表示名 (wp_displayname)</label>
        <input type="text" id="wp_displayname" name="wp_displayname" class="input" required placeholder="User One" style="width: 100%; margin-bottom: 1rem;">
      </div>
    </div>

    <div class="card">
      <button type="submit" id="setupBtn" class="btn" style="background:#10b981; width: 100%;">WordPressをセットアップする</button>
      <div id="resultArea" style="margin-top: 1.5rem; display: none; padding: 1rem; border-radius: 0.25rem;">
        <p id="setupMsg" style="font-weight: bold; margin-bottom: 0.5rem;"></p>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // --- 現在のサイト情報を取得 ---
      const hostingInfoCard = document.getElementById('hostingInfoCard');
      const currentWpUrl = document.getElementById('currentWpUrl');

      fetch('{{ url('/api/container') }}')
        .then(response => {
          if (response.ok) { return response.json(); }
          return null;
        })
        .then(data => {
          if (data && data.wp_url) {
            hostingInfoCard.style.display = 'block';
            currentWpUrl.href = data.wp_url;
            currentWpUrl.textContent = data.wp_url;
          }
        })
        .catch(error => console.error('Error fetching container info:', error));

      // --- WordPressセットアップフォームの処理 ---
      const wpSetupForm = document.getElementById('wpSetupForm');
      const setupBtn = document.getElementById('setupBtn');
      const resultArea = document.getElementById('resultArea');
      const setupMsg = document.getElementById('setupMsg');

      wpSetupForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        setupBtn.disabled = true;
        setupBtn.textContent = 'セットアップ中... しばらくお待ちください';
        setupBtn.style.opacity = '0.7';
        
        resultArea.style.display = 'block';
        resultArea.style.backgroundColor = '#eff6ff';
        setupMsg.textContent = 'WordPressのセットアップをリクエストしています...';
        setupMsg.style.color = '#3b82f6';

        const formData = new FormData(wpSetupForm);
        const requestData = Object.fromEntries(formData.entries());

        try {
          const response = await fetch('{{ url('/api/web-hosting/setup') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
              'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
          });

          const result = await response.json();

          setupMsg.textContent = result.message;

          if (response.ok && result.success) {
            resultArea.style.backgroundColor = '#ecfdf5';
            setupMsg.style.color = '#10b981';
            if (result.wp_url) {
              hostingInfoCard.style.display = 'block';
              currentWpUrl.href = result.wp_url;
              currentWpUrl.textContent = result.wp_url;
            }
            wpSetupForm.reset();
          } else {
            resultArea.style.backgroundColor = '#fef2f2';
            setupMsg.style.color = '#e11d48';
          }
        } catch (error) {
          resultArea.style.backgroundColor = '#fef2f2';
          setupMsg.textContent = '通信エラーが発生しました';
          setupMsg.style.color = '#e11d48';
        } finally {
          setupBtn.disabled = false;
          setupBtn.textContent = 'WordPressをセットアップする';
          setupBtn.style.opacity = '1';
        }
      });
    });
  </script>
@endpush