<?php
declare(strict_types=1);

$apiUrl = getenv('MEMBERS_API_URL') ?: 'http://127.0.0.1:8000/api/members';
$members = [];
$error = null;
$savedNames = [];
$savedPhotos = [];
$metadataPath = __DIR__ . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . 'index.json';
if (is_file($metadataPath)) {
    $savedRecords = json_decode((string) file_get_contents($metadataPath), true);
    if (is_array($savedRecords)) {
        foreach ($savedRecords as $record) {
            $savedName = trim((string) ($record['name'] ?? ''));
            if ($savedName !== '') {
                $key = mb_strtolower($savedName, 'UTF-8');
                $savedNames[$key] = true;
                $photoName = basename((string) ($record['photo'] ?? ''));
                if ($photoName !== '') $savedPhotos[$key] = 'photos/' . $photoName;
            }
        }
    }
}

$context = stream_context_create([
    'http' => [
        'timeout' => 8,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\n",
    ],
]);
$response = @file_get_contents($apiUrl, false, $context);
if ($response === false) {
    $error = 'Tidak bisa mengambil data member. Pastikan Laravel aktif dan endpoint API bisa diakses.';
} else {
    $payload = json_decode($response, true);
    if (!is_array($payload) || empty($payload['success']) || !is_array($payload['data'] ?? null)) {
        $error = 'Respons API tidak sesuai format yang diharapkan.';
    } else {
        foreach ($payload['data'] as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name !== '') {
                $members[] = ['name' => $name];
            }
        }
    }
}
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register Face — Sport Center</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand-mark">SC</div>
      <div class="brand-copy"><strong>SPORT CENTER</strong><span>MEMBER SYSTEM</span></div>
      <div class="side-label">WORKSPACE</div>
      <button type="button" class="side-link active" id="navRegister"><span class="side-icon">◉</span> Register Face</button>
      <button type="button" class="side-link" id="navSettings"><span class="side-icon">⚙</span> Setting FaceRec</button>
      <div class="sidebar-note"><span class="status-dot"></span> Standalone local app</div>
      <div class="sidebar-bottom">Face data is stored on this PC.</div>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <div><div class="eyebrow">MEMBER MANAGEMENT / BIOMETRICS</div><h1>Register Face</h1><p class="page-subtitle">Daftarkan foto wajah member untuk kebutuhan pengenalan wajah.</p></div>
        <div class="local-badge"><span class="status-dot"></span> LOCAL STORAGE</div>
      </header>

      <section class="settings-panel panel" id="settingsPanel" hidden>
        <div class="panel-heading"><div><div class="eyebrow">DEVICE CONFIGURATION</div><h2>Setting IP FaceRec</h2></div><span class="count-pill">Disimpan di browser</span></div>
        <p class="settings-description">Masukkan alamat perangkat FaceRec. Pengaturan disimpan di localStorage browser ini, bukan di database.</p>
        <form id="faceRecSettingsForm" class="settings-form">
          <label for="faceRecIp">IP / URL perangkat FaceRec</label>
          <div class="settings-input-row"><input id="faceRecIp" name="faceRecIp" type="url" placeholder="http://192.168.18.165" autocomplete="url" required><button type="submit" class="button button-primary">Simpan Pengaturan</button></div>
          <small>Endpoint yang digunakan: <code>/action/AddPersons</code></small>
          <div id="settingsMessage" class="message" role="status" aria-live="polite"></div>
        </form>
      </section>

      <div id="registerContent">
      <section class="stats-row">
        <article class="stat-card"><div class="stat-label">TOTAL MEMBER</div><div class="stat-number"><?= count($members) ?></div><div class="stat-foot">Diambil dari API Laravel</div></article>
        <article class="stat-card"><div class="stat-label">PENYIMPANAN</div><div class="stat-number stat-word">Local PC</div><div class="stat-foot">Pengiriman ke FaceRec dilakukan manual</div></article>
        <article class="stat-card"><div class="stat-label">STATUS KAMERA</div><div class="stat-number stat-word" id="cameraStatus">Belum aktif</div><div class="stat-foot">Aktifkan saat siap memotret</div></article>
      </section>

      <?php if ($error): ?>
        <div class="notice error"><strong>API belum terhubung</strong><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span><code><?= htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8') ?></code></div>
      <?php endif; ?>

      <section class="workspace">
        <div class="panel member-panel">
          <div class="panel-heading"><div><div class="eyebrow">DIRECTORY</div><h2>Daftar Member</h2></div><span class="count-pill"><?= count($members) ?> member</span></div>
          <label class="search-box"><span>⌕</span><input type="search" id="memberSearch" placeholder="Cari nama member..." autocomplete="off"></label>
          <div class="member-list" id="memberList">
            <?php foreach ($members as $i => $member): ?>
              <button class="member-item<?= $i === 0 ? ' selected' : '' ?>" type="button" data-name="<?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?>" data-saved="<?= isset($savedNames[mb_strtolower($member['name'], 'UTF-8')]) ? 'true' : 'false' ?>" data-photo="<?= htmlspecialchars($savedPhotos[mb_strtolower($member['name'], 'UTF-8')] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <span class="avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($member['name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="member-copy"><strong><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></strong><small><?= isset($savedNames[mb_strtolower($member['name'], 'UTF-8')]) ? 'Foto sudah terdaftar' : 'Member Sport Center' ?></small></span>
                <span class="member-chevron">›</span>
              </button>
            <?php endforeach; ?>
            <?php if (!$members && !$error): ?><div class="empty-state">Belum ada member dari API.</div><?php endif; ?>
          </div>
          <div class="panel-foot">Pilih nama member sebelum mengambil foto.</div>
        </div>

        <div class="panel capture-panel">
          <div class="panel-heading"><div><div class="eyebrow">PHOTO CAPTURE</div><h2>Ambil Foto Wajah</h2></div><span class="camera-tag">● Camera</span></div>
          <div class="selected-member" id="selectedMember"><div class="selected-avatar">?</div><div><small>MEMBER DIPILIH</small><strong id="selectedName">Pilih member di sebelah kiri</strong></div></div>
          <div class="camera-stage" id="cameraStage">
            <div class="camera-placeholder" id="cameraPlaceholder"><div class="camera-symbol">◎</div><strong>Kamera belum diaktifkan</strong><span>Pastikan kamera tersedia dan izinkan akses browser.</span></div>
            <video id="cameraVideo" autoplay playsinline muted hidden></video>
            <img id="photoPreview" alt="Pratinjau foto wajah" hidden>
            <canvas id="captureCanvas" hidden></canvas>
            <div class="camera-corner top-left"></div><div class="camera-corner top-right"></div><div class="camera-corner bottom-left"></div><div class="camera-corner bottom-right"></div>
          </div>
          <div class="camera-hint"><span class="hint-dot"></span> Posisikan wajah di tengah area kamera, dengan pencahayaan yang cukup.</div>
          <div class="action-row"><button type="button" class="button button-secondary" id="cameraButton">Aktifkan Kamera</button><button type="button" class="button button-secondary" id="retakeButton" disabled>Ulangi Foto</button><button type="button" class="button button-primary" id="captureButton" disabled>Ambil Foto</button></div>
          <div class="save-row"><div><strong>Simpan ke komputer ini</strong><span>Foto disimpan di folder <code>photos/</code> aplikasi ini.</span></div><button type="button" class="button button-save" id="saveButton" disabled>Simpan Foto</button></div>
          <div id="message" class="message" role="status" aria-live="polite"></div>
        </div>
      </section>
      </div>
      <footer class="footer"><span>SPORT CENTER · REGISTER FACE</span><span>Standalone PHP · Local PC</span></footer>
    </main>
  </div>
  <script src="assets/js/app.js"></script>
</body>
</html>
