@extends('layouts.app')

@section('title', 'ダッシュボード – 管理パネル')

@section('content')
  <h1 style="margin-bottom:1.5rem;">ダッシュボード</h1>
  <p style="margin-bottom:1.5rem;">ようこそ、<strong>{{ Auth::user()->username }}</strong> さん！</p>

  <div class="cards-grid">
    <div class="card">
      <h3 style="margin-bottom:1rem;">サーバー情報</h3>
      @if($container)
        <p><strong>SFTPポート:</strong> <span>{{ $container->sftp_port }}</span></p>
        <p><strong>パスワード:</strong> <span>{{ $container->sftp_password }}</span></p>
      @else
        <p style="color: #e11d48; margin-bottom: 1rem;">SFTP情報が見つかりません（コンテナ未作成）</p>
        <form id="createContainerForm">
            <label for="sftp_password">SFTPパスワードを設定してください（8文字以上）</label>
            <input type="password" id="sftp_password" name="sftp_password" class="input" required minlength="8" style="margin-bottom:1rem;" placeholder="StrongPassword123!">
            <button type="submit" id="createBtn" class="btn" style="background:#10b981;">コンテナを作成する</button>
        </form>
        <p id="containerMsg" style="margin-top:1rem;font-weight:bold;display:none;"></p>
      @endif
    </div>
    
    <div class="card">
      <h3 style="margin-bottom:1rem;">ご利用状況</h3>
       <p><strong>登録メールアカウント:</strong> <span>{{ $mailCount }} 件</span></p>
    </div>
  </div>
@endsection

@push('scripts')
  @if(!$container)
  <script>
    const createContainerForm = document.getElementById('createContainerForm');
    const containerMsg = document.getElementById('containerMsg');
    const createBtn = document.getElementById('createBtn');

    createContainerForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const sftpPassword = document.getElementById('sftp_password').value;

      createBtn.disabled = true;
      createBtn.textContent = '作成中... しばらくお待ちください';
      createBtn.style.opacity = '0.7';
      containerMsg.style.display = 'block';
      containerMsg.textContent = 'コンテナを構築しています。数分かかる場合があります...';
      containerMsg.style.color = '#3b82f6';

      try {
        const response = await fetch('{{ url('/api/container/create') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            sftp_password: sftpPassword
          })
        });

        const result = await response.json();
        
        containerMsg.textContent = result.message;
        containerMsg.style.color = (response.ok && result.success) ? '#10b981' : '#e11d48';
        
        if (response.ok && result.success) {
            setTimeout(() => location.reload(), 1500); // 成功したら画面をリロードして情報を表示
        } else {
            createBtn.disabled = false;
            createBtn.textContent = 'コンテナを作成する';
            createBtn.style.opacity = '1';
        }
      } catch (error) {
        containerMsg.textContent = '通信エラーが発生しました';
        containerMsg.style.color = '#e11d48';
        createBtn.disabled = false;
        createBtn.textContent = 'コンテナを作成する';
        createBtn.style.opacity = '1';
      }
    });
  </script>
  @endif
@endpush
