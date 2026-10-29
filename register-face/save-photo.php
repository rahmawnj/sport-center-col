<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalizedName(string $name): string {
    return mb_strtolower(trim($name), 'UTF-8');
}

function filenameSlug(string $name): string {
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $slug = strtolower((string) $slug);
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    return $slug !== '' ? $slug : 'member';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}

$name = trim((string) ($_POST['name'] ?? ''));
$replace = (string) ($_POST['replace'] ?? '') === '1';
if ($name === '' || !isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    respond(422, ['success' => false, 'message' => 'Nama member dan foto wajib diisi.']);
}

$tmp = $_FILES['photo']['tmp_name'];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
if ($mime !== 'image/jpeg') {
    respond(422, ['success' => false, 'message' => 'Format foto harus JPEG.']);
}
if ((int) $_FILES['photo']['size'] > 10 * 1024 * 1024) {
    respond(413, ['success' => false, 'message' => 'Ukuran foto maksimal 10 MB.']);
}

$slug = filenameSlug($name);
$photosDir = __DIR__ . DIRECTORY_SEPARATOR . 'photos';
if (!is_dir($photosDir) && !mkdir($photosDir, 0755, true) && !is_dir($photosDir)) {
    respond(500, ['success' => false, 'message' => 'Folder foto tidak bisa dibuat.']);
}

$metadataPath = $photosDir . DIRECTORY_SEPARATOR . 'index.json';
$lockHandle = fopen($photosDir . DIRECTORY_SEPARATOR . '.save.lock', 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX)) {
    if (is_resource($lockHandle)) fclose($lockHandle);
    respond(500, ['success' => false, 'message' => 'Penyimpanan sedang bermasalah. Coba lagi.']);
}

$records = [];
if (is_file($metadataPath)) {
    $decoded = json_decode((string) file_get_contents($metadataPath), true);
    if (is_array($decoded)) $records = $decoded;
}

$matchingIndexes = [];
$oldFiles = [];
foreach ($records as $index => $record) {
    if (normalizedName((string) ($record['name'] ?? '')) === normalizedName($name)) {
        $matchingIndexes[] = $index;
        $oldPhoto = basename((string) ($record['photo'] ?? ''));
        if ($oldPhoto !== '' && $oldPhoto !== '.' && $oldPhoto !== '..') $oldFiles[] = $oldPhoto;
    }
}

$destinationName = $slug . '.jpg';
$destination = $photosDir . DIRECTORY_SEPARATOR . $destinationName;
$hasExisting = count($matchingIndexes) > 0 || is_file($destination);

// Backward compatibility for older timestamp/random filenames created by previous versions.
$legacyPattern = $photosDir . DIRECTORY_SEPARATOR . $slug . '-*.jpg';
foreach (glob($legacyPattern) ?: [] as $legacyPath) {
    $base = basename($legacyPath);
    if (preg_match('/^' . preg_quote($slug, '/') . '-\d{8}-\d{6}-[a-f0-9]+\.jpg$/i', $base)) {
        $oldFiles[] = $base;
        $hasExisting = true;
    }
}
$oldFiles = array_values(array_unique($oldFiles));

if ($hasExisting && !$replace) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(409, [
        'success' => false,
        'message' => 'Foto ' . $name . ' sudah tersimpan. Pilih mode edit/ganti foto untuk memperbaruinya.',
        'path' => 'photos/' . ($oldFiles[0] ?? $destinationName),
        'already_saved' => true,
    ]);
}

// Avoid overwriting a file belonging to another member with the same normalized filename.
foreach ($records as $record) {
    $recordName = (string) ($record['name'] ?? '');
    $recordPhoto = basename((string) ($record['photo'] ?? ''));
    if ($recordPhoto === $destinationName && normalizedName($recordName) !== normalizedName($name)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        respond(409, ['success' => false, 'message' => 'Nama file bentrok dengan member lain. Ubah nama member agar file tidak tertimpa.']);
    }
}

if ($replace && !$hasExisting) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(404, ['success' => false, 'message' => 'Foto lama tidak ditemukan. Muat ulang halaman sebelum menyimpan foto baru.']);
}

// Write new upload to a temporary file first so a failed upload won't destroy the old photo.
$tempName = '.upload-' . bin2hex(random_bytes(8)) . '.tmp';
$tempPath = $photosDir . DIRECTORY_SEPARATOR . $tempName;
if (!move_uploaded_file($tmp, $tempPath)) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(500, ['success' => false, 'message' => 'Foto gagal ditulis ke penyimpanan lokal.']);
}

if (is_file($destination) && !in_array($destinationName, $oldFiles, true) && !$matchingIndexes) {
    @unlink($tempPath);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(409, ['success' => false, 'message' => 'File tujuan sudah ada dan tidak dikenali sebagai foto member ini. Foto lama tidak dihapus.']);
}

if (is_file($destination) && !@unlink($destination)) {
    @unlink($tempPath);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(500, ['success' => false, 'message' => 'Foto lama tidak bisa diganti. Periksa izin folder photos/.']);
}

if (!@rename($tempPath, $destination)) {
    @unlink($tempPath);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(500, ['success' => false, 'message' => 'Foto baru gagal dipasang. Foto lama selain file tujuan tetap dipertahankan.']);
}

// Once the new photo is safely in place, remove the old filenames and update metadata.
foreach ($oldFiles as $oldFile) {
    if ($oldFile !== $destinationName) {
        $oldPath = $photosDir . DIRECTORY_SEPARATOR . $oldFile;
        if (is_file($oldPath)) @unlink($oldPath);
    }
}

$records = array_values(array_filter($records, fn ($record) =>
    normalizedName((string) ($record['name'] ?? '')) !== normalizedName($name)
));
$records[] = [
    'name' => $name,
    'photo' => $destinationName,
    'saved_at' => date(DATE_ATOM),
];

if (file_put_contents($metadataPath, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(500, ['success' => false, 'message' => 'Foto tersimpan, tetapi metadata gagal diperbarui. Periksa photos/index.json.']);
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);
respond(201, [
    'success' => true,
    'message' => $replace ? 'Foto lama diganti dengan foto baru.' : 'Foto berhasil disimpan.',
    'path' => 'photos/' . $destinationName,
    'replaced' => $replace,
]);
