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
        <p style="color: #e11d48;">SFTP情報が見つかりません（コンテナ未作成）</p>
      @endif
    </div>
    
    <div class="card">
      <h3 style="margin-bottom:1rem;">ご利用状況</h3>
       <p><strong>登録メールアカウント:</strong> <span>{{ $mailCount }} 件</span></p>
    </div>
  </div>
@endsection
