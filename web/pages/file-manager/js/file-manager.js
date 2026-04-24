/* pages/file-manager/js/file-manager.js */
(() => {
  const uploadForm = document.getElementById('uploadForm');
  const fileInput = document.getElementById('fileInput');
  const fileMsg = document.getElementById('fileMsg');
  const fileList = document.getElementById('fileList');

  // ファイル一覧を取得して表示する関数
  async function loadFiles() {
    try {
      const data = await window.apiFetch('../../api/file_manager.php?action=list');
      
      if (data.success) {
        fileList.innerHTML = '';
        if (data.files.length === 0) {
          fileList.innerHTML = '<li style="color: #999;">ファイルはありません</li>';
          return;
        }
        
        data.files.forEach(file => {
          const li = document.createElement('li');
          li.style.display = 'flex';
          li.style.justifyContent = 'space-between';
          li.style.alignItems = 'center';
          li.style.padding = '0.5rem 0';
          li.style.borderBottom = '1px solid #e5e7eb';
          
          const fileInfo = document.createElement('span');
          const date = new Date(file.modified * 1000).toLocaleString();
          const size = (file.size / 1024).toFixed(2) + ' KB';
          fileInfo.textContent = `${file.name} (${size}) - ${date}`;
          
          const deleteBtn = document.createElement('button');
          deleteBtn.textContent = '削除';
          deleteBtn.className = 'btn';
          deleteBtn.style.background = '#ef4444';
          deleteBtn.style.padding = '0.25rem 0.5rem';
          deleteBtn.style.fontSize = '0.8rem';
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
      const data = await window.apiFetch('../../api/file_manager.php?action=delete', {
        method: 'DELETE',
        body: JSON.stringify({ filename })
      });
      
      if (data.success) {
        loadFiles();
      } else {
        alert(data.message || '削除に失敗しました');
      }
    } catch (e) {
      alert('通信エラーが発生しました');
    }
  }

  // ファイルアップロード時の処理
  if (uploadForm) {
    uploadForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      if (fileInput.files.length === 0) {
        fileMsg.textContent = 'ファイルを選択してください';
        fileMsg.style.color = '#e11d48';
        return;
      }

      fileMsg.textContent = 'アップロード中...';
      fileMsg.style.color = '#3b82f6';

      const formData = new FormData();
      formData.append('file', fileInput.files[0]);

      try {
        const data = await window.apiFetch('../../api/file_manager.php?action=upload', {
          method: 'POST',
          body: formData
        });
        
        fileMsg.textContent = data.message;
        fileMsg.style.color = data.success ? '#10b981' : '#e11d48';
        
        if (data.success) {
          uploadForm.reset();
          loadFiles();
        }
      } catch (error) {
        fileMsg.textContent = '通信エラーが発生しました';
        fileMsg.style.color = '#e11d48';
      }
    });
  }

  // 初期読み込み時にファイル一覧を表示
  loadFiles();
})();