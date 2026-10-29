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
  const navRegister = document.getElementById('navRegister');
  const navSettings = document.getElementById('navSettings');
  const registerContent = document.getElementById('registerContent');
  const settingsPanel = document.getElementById('settingsPanel');
  const settingsForm = document.getElementById('faceRecSettingsForm');
  const faceRecIpInput = document.getElementById('faceRecIp');
  const settingsMessage = document.getElementById('settingsMessage');
  const FACE_REC_IP_KEY = 'sportCenter.faceRecIp';
  const FACE_REC_DEVICE_ID_KEY = 'sportCenter.faceRecDeviceId';
  let selectedMember = '';
  let stream = null;
  let capturedBlob = null;
  let lastSavedDataUrl = null;

  function normalizeDeviceUrl(value) {
    let url = String(value || '').trim().replace(/\/+$/, '');
    if (!url) return '';
    if (!/^https?:\/\//i.test(url)) url = 'http://' + url;
    return url;
  }
  function showView(view) {
    const settings = view === 'settings';
    registerContent.hidden = settings;
    settingsPanel.hidden = !settings;
    navRegister.classList.toggle('active', !settings);
    navSettings.classList.toggle('active', settings);
  }
  navRegister?.addEventListener('click', () => showView('register'));
  navSettings?.addEventListener('click', () => showView('settings'));
  if (faceRecIpInput) faceRecIpInput.value = localStorage.getItem(FACE_REC_IP_KEY) || 'http://192.168.18.165';
  settingsForm?.addEventListener('submit', event => {
    event.preventDefault();
    const url = normalizeDeviceUrl(faceRecIpInput.value);
    try {
      const parsed = new URL(url);
      if (!['http:', 'https:'].includes(parsed.protocol)) throw new Error('Gunakan URL HTTP atau HTTPS.');
      localStorage.setItem(FACE_REC_IP_KEY, parsed.origin);
      faceRecIpInput.value = parsed.origin;
      settingsMessage.textContent = 'Pengaturan FaceRec tersimpan di localStorage: ' + parsed.origin;
      settingsMessage.className = 'message success';
    } catch (error) {
      settingsMessage.textContent = error.message || 'Alamat IP / URL tidak valid.';
      settingsMessage.className = 'message error';
    }
  });

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
    capturedBlob = null;
    const alreadySaved = button.dataset.saved === 'true';
    captureButton.disabled = !stream || !selectedMember;
    saveButton.disabled = true;
    saveButton.textContent = alreadySaved ? 'Simpan Perubahan' : 'Simpan Foto';
    retakeButton.disabled = true;
    if (alreadySaved && button.dataset.photo) {
      preview.src = button.dataset.photo + '?v=' + Date.now();
      preview.hidden = false;
      video.hidden = true;
      placeholder.hidden = true;
      notify('Foto lama ' + selectedMember + ' ditampilkan. Aktifkan kamera dan ambil foto baru untuk menggantinya.', 'success');
    } else {
      preview.hidden = true;
      if (!stream) placeholder.hidden = false;
      else video.hidden = false;
      notify('');
    }
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
    if (stream) {
      stopCamera();
      placeholder.hidden = false;
      cameraButton.textContent = 'Aktifkan Kamera';
      captureButton.disabled = true;
      notify('Kamera dimatikan.');
      return;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
      notify('Browser tidak mendukung kamera. Buka aplikasi melalui localhost atau HTTPS.', 'error');
      return;
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
      video.srcObject = stream;
      video.hidden = false;
      preview.hidden = true;
      placeholder.hidden = true;
      await video.play();
      cameraStatus.textContent = 'Aktif';
      cameraButton.textContent = 'Matikan Kamera';
      const selectedButton = list.querySelector('.member-item.selected');
      captureButton.disabled = !selectedMember;
      saveButton.disabled = true;
      saveButton.textContent = selectedButton?.dataset.saved === 'true' ? 'Simpan Perubahan' : 'Simpan Foto';
      capturedBlob = null;
      notify('Kamera aktif. Posisikan wajah lalu ambil foto.', 'success');
    } catch (error) {
      stopCamera();
      cameraButton.textContent = 'Aktifkan Kamera';
      notify('Akses kamera gagal. Periksa izin kamera browser dan pastikan kamera tidak dipakai aplikasi lain.', 'error');
    }
  });

  captureButton.addEventListener('click', async () => {
    const selectedButton = list.querySelector('.member-item.selected');
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
    saveButton.textContent = selectedButton?.dataset.saved === 'true' ? 'Simpan Perubahan' : 'Simpan Foto';
    notify(selectedButton?.dataset.saved === 'true' ? 'Foto baru siap. Simpan perubahan untuk mengganti foto lama.' : 'Foto berhasil diambil. Periksa pratinjau sebelum menyimpan.', 'success');
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
    const selectedButton = list.querySelector('.member-item.selected');
    if (!capturedBlob || !selectedMember) return;
    saveButton.disabled = true;
    saveButton.textContent = 'Menyimpan...';
    try {
      const form = new FormData();
      form.append('name', selectedMember);
      form.append('replace', selectedButton?.dataset.saved === 'true' ? '1' : '0');
      form.append('photo', capturedBlob, 'face.jpg');
      const response = await fetch('save-photo.php', { method: 'POST', body: form });
      const result = await response.json();
      if (response.status === 409 && selectedButton) {
        selectedButton.dataset.saved = 'true';
        const detail = selectedButton.querySelector('.member-copy small');
        if (detail) detail.textContent = 'Foto sudah terdaftar';
      }
      if (!response.ok || !result.success) throw new Error(result.message || 'Foto gagal disimpan.');
      if (selectedButton) {
        selectedButton.dataset.saved = 'true';
        selectedButton.dataset.photo = result.path;
        const detail = selectedButton.querySelector('.member-copy small');
        if (detail) detail.textContent = 'Foto sudah terdaftar';
      }
      preview.src = result.path + '?v=' + Date.now();
      preview.hidden = false;
      video.hidden = true;
      lastSavedDataUrl = await blobToDataUrl(capturedBlob);
      notify(result.replaced ? 'Foto lama berhasil dihapus dan diganti: ' + result.path + '. Foto lokal siap dikirim ke FaceRec.' : 'Foto tersimpan di ' + result.path + '. Foto lokal siap dikirim ke FaceRec.', 'success');
      showSendToFaceRecButton();
      retakeButton.disabled = true;
      captureButton.disabled = true;
    } catch (error) {
      notify(error.message || 'Gagal menyimpan foto. Periksa izin tulis folder aplikasi.', 'error');
    } finally {
      const currentButton = list.querySelector('.member-item.selected');
      saveButton.textContent = currentButton?.dataset.saved === 'true' ? 'Simpan Perubahan' : 'Simpan Foto';
      saveButton.disabled = !capturedBlob || !selectedMember;
    }
  });

  function blobToDataUrl(blob) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result));
      reader.onerror = () => reject(new Error('Foto gagal dibaca untuk dikirim ke FaceRec.'));
      reader.readAsDataURL(blob);
    });
  }

  function showSendToFaceRecButton() {
    let button = document.getElementById('sendFaceRecButton');
    if (!button) {
      button = document.createElement('button');
      button.type = 'button';
      button.id = 'sendFaceRecButton';
      button.className = 'button button-primary';
      button.textContent = 'Kirim ke FaceRec';
      document.querySelector('.save-row')?.appendChild(button);
      button.addEventListener('click', sendToFaceRec);
    }
    button.hidden = false;
  }

  async function sendToFaceRec() {
    const button = document.getElementById('sendFaceRecButton');
    if (!selectedMember || !lastSavedDataUrl) {
      notify('Simpan foto lokal terlebih dahulu.', 'error');
      return;
    }
    const baseUrl = normalizeDeviceUrl(localStorage.getItem(FACE_REC_IP_KEY) || 'http://192.168.18.165');
    if (!baseUrl) {
      notify('Atur IP FaceRec terlebih dahulu di menu Setting FaceRec.', 'error');
      showView('settings');
      return;
    }
    button.disabled = true;
    button.textContent = 'Mengirim...';
    const now = new Date();
    const dateTime = value => value.toISOString().slice(0, 19);
    const validBegin = dateTime(now);
    const validEndDate = new Date(now);
    validEndDate.setFullYear(validEndDate.getFullYear() + 10);
    const person = {
      PersonType: 0,
      Name: selectedMember,
      Gender: 0,
      Nation: 1,
      CardType: 0,
      IdCard: '',
      Birthday: '',
      Telnum: '',
      Native: '',
      Address: '',
      Notes: 'Sport Center member',
      MjCardFrom: 2,
      WiegandType: 1,
      CardMode: 0,
      MjCardNo: 0,
      Tempvalid: 0,
      CustomizeID: 120,
      PersonUUID: (typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => { const r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16); })),
      isCheckSimilarity: 0,
      ValidBegin: validBegin,
      ValidEnd: dateTime(validEndDate),
      picinfo: lastSavedDataUrl
    };
    const payload = {
      operator: 'AddPersons',
      DeviceID: Number(localStorage.getItem(FACE_REC_DEVICE_ID_KEY) || 1310023),
      Total: 1,
      Personinfo_0: person
    };
    try {
      const response = await fetch(baseUrl + '/action/AddPersons', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json, text/plain, */*' },
        body: JSON.stringify(payload)
      });
      const responseText = await response.text();
      if (!response.ok) throw new Error('FaceRec merespons HTTP ' + response.status + (responseText ? ': ' + responseText.slice(0, 250) : ''));
      notify('Permintaan pendaftaran wajah terkirim ke FaceRec. Respons perangkat: ' + (responseText || '(kosong)'), 'success');
    } catch (error) {
      notify('Foto sudah tersimpan lokal, tetapi pengiriman ke FaceRec gagal. Periksa IP, koneksi perangkat, dan dukungan CORS browser. Detail: ' + (error.message || 'koneksi gagal'), 'error');
    } finally {
      button.disabled = false;
      button.textContent = 'Kirim ke FaceRec';
    }
  }

  window.addEventListener('beforeunload', stopCamera);
})();
