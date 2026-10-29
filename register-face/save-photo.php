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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}

$name = trim((string) ($_POST['name'] ?? ''));
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

$slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
$slug = strtolower((string) $slug);
$slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
if ($slug === '') $slug = 'member';

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

foreach ($records as $record) {
    if (normalizedName((string) ($record['name'] ?? '')) === normalizedName($name)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        respond(409, [
            'success' => false,
            'message' => 'Foto untuk member ' . $name . ' sudah tersimpan. Satu member hanya boleh memiliki satu foto.',
            'path' => 'photos/' . (string) ($record['photo'] ?? ($slug . '.jpg')),
        ]);
    }
}

// Use the member name as the entire filename, without timestamp or random suffix.
$filename = $slug . '.jpg';
$destination = $photosDir . DIRECTORY_SEPARATOR . $filename;
if (is_file($destination)) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(409, [
        'success' => false,
        'message' => 'File ' . $filename . ' sudah ada. Foto tidak dibuat duplikat.',
        'path' => 'photos/' . $filename,
    ]);
}

if (!move_uploaded_file($tmp, $destination)) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(500, ['success' => false, 'message' => 'Foto gagal ditulis ke penyimpanan lokal.']);
}

$records[] = [
    'name' => $name,
    'photo' => $filename,
    'saved_at' => date(DATE_ATOM),
];
if (file_put_contents($metadataPath, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
    @unlink($destination);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    respond(500, ['success' => false, 'message' => 'Metadata foto gagal disimpan.']);
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);
respond(201, ['success' => true, 'message' => 'Foto berhasil disimpan.', 'path' => 'photos/' . $filename]);
