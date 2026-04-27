<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- 各ページで指定されたタイトルを表示 -->
  <title>@yield('title', 'Admin')</title>
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="dashboard">
  <!-- 共通サイドバー -->
  <div class="sidebar">
    <h2 style="margin-bottom:1rem;">Admin</h2>
    <!-- request()->is(...) で現在のURLを判定し、色（active）を付けます -->
    <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">ダッシュボード</a>
    <a href="{{ url('/web-hosting') }}" class="{{ request()->is('web-hosting') ? 'active' : '' }}">Webホスティング</a>
    <a href="{{ url('/mail-hosting') }}" class="{{ request()->is('mail-hosting') ? 'active' : '' }}">メールホスティング</a>
    <!-- <a href="{{ url('/file-manager') }}" class="{{ request()->is('file-manager') ? 'active' : '' }}">ファイルマネージャー</a> -->
    <a href="{{ url('/account-settings') }}" class="{{ request()->is('account-settings') ? 'active' : '' }}">アカウント設定</a>
    <br><br>
    <a href="#" id="logoutBtn" style="color: #ef4444;">ログアウト</a>
  </div>

  <!-- メインコンテンツ（各ページの中身がここに入ります） -->
  <section class="main-content">
    @yield('content')
  </section>

  <!-- 共通のログアウト処理スクリプト -->
  <script>
    document.getElementById('logoutBtn')?.addEventListener('click', async (e) => {
      e.preventDefault();
      if (!confirm('ログアウトしますか？')) return;
      try {
        const res = await fetch('{{ url('/logout') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });
        if (res.ok) window.location.href = '{{ url('/') }}';
      } catch (err) {
        console.error('通信エラー:', err);
      }
    });
  </script>
  <!-- 各ページ固有のスクリプトを読み込むための枠 -->
  @stack('scripts')
</body>
</html>
