@extends('layouts.app')

@section('title', 'ファイルマネージャー – 管理パネル')

@section('content')
  <h1 style="margin-bottom:1.5rem;">ファイルマネージャー</h1>
  <div class="card">
      <p style="margin-bottom:1rem;color:#4b5563;">ここでファイルをアップロード、削除できます。</p>
      
      <form id="uploadForm" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
          <input type="file" id="fileInput" name="file" required>
          <button type="submit" class="btn">アップロード</button>
      </form>
      <p id="fileMsg" style="margin-bottom:1rem;font-weight:bold;"></p>
      
      <h3 style="margin-bottom:1rem;">ファイル一覧</h3>
      <ul id="fileList" style="list-style: none; padding: 0;">
          <li style="color: #999;">読み込み中...</li>
      </ul>
  </div>
@endsection

@push('scripts')
<script>
  const uploadForm = document.getElementById('uploadForm');
  const fileInput = document.getElementById('fileInput');
  const fileMsg = document.getElementById('fileMsg');
  const fileList = document.getElementById('fileList');

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

  // ファイル一覧を取得して表示
  async function loadFiles() {
    try {
      const data = await fetchApi('{{ url('/api/file-manager/list') }}');
      
      if (data.success) {
        fileList.innerHTML = '';
        if (data.files.length === 0) {
          fileList.innerHTML = '<li style="color: #999;">ファイルはありません</li>';
          return;
        }
        
        data.files.forEach(file => {
          const li = document.createElement('li');
          li.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;';
          
          const fileInfo = document.createElement('span');
          const date = new Date(file.modified * 1000).toLocaleString();
          const size = (file.size / 1024).toFixed(2) + ' KB';
          fileInfo.textContent = `${file.name} (${size}) - ${date}`;
          
          const deleteBtn = document.createElement('button');
          deleteBtn.textContent = '削除';
          deleteBtn.className = 'btn';
          deleteBtn.style.cssText = 'background: #ef4444; padding: 0.25rem 0.5rem; font-size: 0.8rem;';
          deleteBtn.onclick = () => deleteFile(file.name);
          
          li.appendChild(fileInfo);
          li.appendChild(deleteBtn);
          fileList.appendChild(li);
        });
      } else {
        fileList.innerHTML = `<li style="color: #e11d48;">${data.message}</li>`;
      }
    } catch (e) {
      fileList.innerHTML = '<li style="color: #e11d48;">読み込みに失敗しました</li>';
    }
  }

  // ファイル削除関数
  async function deleteFile(filename) {
    if (!confirm(`本当に ${filename} を削除しますか？`)) return;
    try {
      const data = await fetchApi('{{ url('/api/file-manager/delete') }}', { method: 'DELETE', body: JSON.stringify({ filename }) });
      if (data.success) loadFiles();
      else alert(data.message || '削除に失敗しました');
    } catch (e) { alert('通信エラーが発生しました'); }
  }

  // ファイルアップロード処理
  if (uploadForm) {
    uploadForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (fileInput.files.length === 0) return showMessage(fileMsg, 'ファイルを選択してください', 'error');
      showMessage(fileMsg, 'アップロード中...', 'info');
      const formData = new FormData();
      formData.append('file', fileInput.files[0]);
      try {
        const data = await fetchApi('{{ url('/api/file-manager/upload') }}', { method: 'POST', body: formData });
        showMessage(fileMsg, data.message, data.success ? 'success' : 'error');
        if (data.success) { uploadForm.reset(); loadFiles(); }
      } catch (error) { showMessage(fileMsg, '通信エラーが発生しました', 'error'); }
    });
  }

  // 初期読み込み
  loadFiles();
</script>
@endpush
