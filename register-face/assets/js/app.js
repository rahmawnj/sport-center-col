(() => {
  const list = document.getElementById('memberList');
  const search = document.getElementById('memberSearch');
  const video = document.getElementById('cameraVideo');
  const placeholder = document.getElementById('cameraPlaceholder');
  const preview = document.getElementById('photoPreview');
  const canvas = document.getElementById('captureCanvas');
  const cameraButton = document.getElementById('cameraButton');
  const captureButton = document.getElementById('captureButton');
  const retakeButton = document.getElementById('retakeButton');
  const saveButton = document.getElementById('saveButton');
  const cameraStatus = document.getElementById('cameraStatus');
  const selectedName = document.getElementById('selectedName');
  const selectedAvatar = document.querySelector('.selected-avatar');
  const message = document.getElementById('message');
  let selectedMember = '';
  let stream = null;
  let capturedBlob = null;

  function notify(text, type = '') {
    message.textContent = text;
    message.className = 'message' + (type ? ' ' + type : '');
  }
  function stopCamera() {
    if (stream) stream.getTracks().forEach(track => track.stop());
    stream = null;
    video.srcObject = null;
    video.hidden = true;
    cameraStatus.textContent = 'Belum aktif';
  }
  function selectMember(button) {
    document.querySelectorAll('.member-item').forEach(item => item.classList.toggle('selected', item === button));
    selectedMember = button.dataset.name || '';
    selectedName.textContent = selectedMember || 'Pilih member di sebelah kiri';
    selectedAvatar.textContent = selectedMember ? Array.from(selectedMember)[0].toUpperCase() : '?';
    captureButton.disabled = !stream || !selectedMember;
    saveButton.disabled = !capturedBlob || !selectedMember;
    notify('');
  }
  list?.querySelectorAll('.member-item').forEach(button => button.addEventListener('click', () => selectMember(button)));
  const firstMember = list?.querySelector('.member-item');
  if (firstMember) selectMember(firstMember);

  search?.addEventListener('input', () => {
    const query = search.value.trim().toLocaleLowerCase('id');
    list.querySelectorAll('.member-item').forEach(item => {
      item.hidden = !item.dataset.name.toLocaleLowerCase('id').includes(query);
    });
  });

  cameraButton.addEventListener('click', async () => {
    if (!navigator.mediaDevices?.getUserMedia) {
      notify('Browser tidak mendukung kamera. Buka aplikasi melalui localhost atau HTTPS.', 'error');
      return;
    }
    try {
      stopCamera();
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
      video.srcObject = stream;
      video.hidden = false;
      preview.hidden = true;
      placeholder.hidden = true;
      await video.play();
      cameraStatus.textContent = 'Aktif';
      cameraButton.textContent = 'Matikan Kamera';
      captureButton.disabled = !selectedMember;
      saveButton.disabled = true;
      capturedBlob = null;
      notify('Kamera aktif. Posisikan wajah lalu ambil foto.', 'success');
      cameraButton.onclick = () => {
        stopCamera();
        placeholder.hidden = false;
        cameraButton.textContent = 'Aktifkan Kamera';
        captureButton.disabled = true;
        notify('Kamera dimatikan.');
        cameraButton.onclick = null;
      };
    } catch (error) {
      notify('Akses kamera gagal. Periksa izin kamera browser dan pastikan kamera tidak dipakai aplikasi lain.', 'error');
    }
  });

  captureButton.addEventListener('click', async () => {
    if (!stream || !selectedMember || !video.videoWidth) return;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    capturedBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.92));
    if (!capturedBlob) {
      notify('Foto gagal diproses. Coba ambil ulang.', 'error');
      return;
    }
    preview.src = URL.createObjectURL(capturedBlob);
    preview.hidden = false;
    video.hidden = true;
    retakeButton.disabled = false;
    saveButton.disabled = false;
    notify('Foto berhasil diambil. Periksa pratinjau sebelum menyimpan.', 'success');
  });

  retakeButton.addEventListener('click', () => {
    if (!stream) {
      notify('Aktifkan kamera kembali untuk mengambil foto ulang.', 'error');
      return;
    }
    capturedBlob = null;
    preview.hidden = true;
    video.hidden = false;
    saveButton.disabled = true;
    retakeButton.disabled = true;
    notify('');
  });

  saveButton.addEventListener('click', async () => {
    if (!capturedBlob || !selectedMember) return;
    saveButton.disabled = true;
    saveButton.textContent = 'Menyimpan...';
    try {
      const form = new FormData();
      form.append('name', selectedMember);
      form.append('photo', capturedBlob, 'face.jpg');
      const response = await fetch('save-photo.php', { method: 'POST', body: form });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Foto gagal disimpan.');
      notify('Foto tersimpan di ' + result.path, 'success');
      retakeButton.disabled = false;
    } catch (error) {
      notify(error.message || 'Gagal menyimpan foto. Periksa izin tulis folder aplikasi.', 'error');
    } finally {
      saveButton.textContent = 'Simpan Foto';
      saveButton.disabled = !capturedBlob;
    }
  });

  window.addEventListener('beforeunload', stopCamera);
})();
