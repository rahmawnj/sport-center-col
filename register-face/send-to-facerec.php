<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['success' => false, 'message' => 'Metode tidak diizinkan.']);
}

if (!function_exists('curl_init')) {
    respond(500, ['success' => false, 'message' => 'Ekstensi PHP cURL belum aktif. Aktifkan extension=curl.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'Body request harus berupa JSON.']);
}

$deviceUrl = rtrim(trim((string) ($input['device_url'] ?? '')), '/');
$username = (string) ($input['username'] ?? '');
$password = (string) ($input['password'] ?? '');
$payload = $input['payload'] ?? null;

if ($deviceUrl === '' || $username === '' || $password === '' || !is_array($payload)) {
    respond(422, ['success' => false, 'message' => 'IP perangkat, username, password, dan payload wajib diisi.']);
}

$parsed = parse_url($deviceUrl);
if (!is_array($parsed) || !in_array(strtolower((string) ($parsed['scheme'] ?? '')), ['http', 'https'], true)
    || empty($parsed['host']) || isset($parsed['user']) || isset($parsed['pass'])
    || isset($parsed['query']) || isset($parsed['fragment'])) {
    respond(422, ['success' => false, 'message' => 'URL perangkat tidak valid. Gunakan alamat IP/host HTTP(S) tanpa kredensial.']);
}

if (($payload['operator'] ?? null) !== 'AddPersons'
    || !isset($payload['DeviceID'], $payload['Total'], $payload['Personinfo_0'])
    || (int) $payload['Total'] !== 1
    || !is_array($payload['Personinfo_0'])
    || empty($payload['Personinfo_0']['Name'])
    || empty($payload['Personinfo_0']['picinfo'])) {
    respond(422, ['success' => false, 'message' => 'Format payload AddPersons tidak lengkap.']);
}

$photoData = (string) $payload['Personinfo_0']['picinfo'];
if (!preg_match('#^data:image/jpeg;base64,#i', $photoData)) {
    respond(422, ['success' => false, 'message' => 'Foto harus berupa JPEG base64 data URL.']);
}
$encodedPhoto = substr($photoData, strpos($photoData, ',') + 1);
$photoBytes = base64_decode($encodedPhoto, true);
if ($photoBytes === false || strlen($photoBytes) > 10 * 1024 * 1024 || @getimagesizefromstring($photoBytes) === false) {
    respond(422, ['success' => false, 'message' => 'Data foto JPEG tidak valid atau melebihi 10 MB.']);
}

$payload['Personinfo_0']['picinfo'] = $photoData;
$jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonBody === false) {
    respond(422, ['success' => false, 'message' => 'Payload gagal dikonversi ke JSON.']);
}

$targetUrl = $deviceUrl . '/action/AddPersons';
$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $username . ':' . $password,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json; charset=utf-8',
        'Accept: application/json, text/plain, */*',
    ],
    CURLOPT_POSTFIELDS => $jsonBody,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false) {
    respond(502, ['success' => false, 'message' => 'Tidak bisa terhubung ke perangkat FaceRec: ' . $curlError]);
}

if ($httpStatus < 200 || $httpStatus >= 300) {
    respond(502, [
        'success' => false,
        'message' => 'Perangkat FaceRec menolak request (HTTP ' . $httpStatus . ').',
        'device_response' => mb_substr((string) $responseBody, 0, 1000, 'UTF-8'),
        'device_http_status' => $httpStatus,
    ]);
}

respond(200, [
    'success' => true,
    'message' => 'Request AddPersons berhasil diteruskan ke perangkat.',
    'device_http_status' => $httpStatus,
    'device_response' => mb_substr((string) $responseBody, 0, 1000, 'UTF-8'),
]);
