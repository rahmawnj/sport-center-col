# API Testing Lab (Standalone PHP)

Tool testing API lokal. Jalankan tanpa menambah Laravel routes, migration, atau tabel.

## Menjalankan
Dari root repository:

```powershell
php -S 127.0.0.1:8082 -t api-testing
```

Buka http://127.0.0.1:8082. Laravel tetap berjalan terpisah di http://127.0.0.1:8000. PHP extension cURL harus aktif.

## Member access
POST `{LARAVEL_URL}/api/device/access`
```json
{"device_id":"GATE-001","device_type":"member","member_code":"MBR-0001"}
```
device_id adalah devices.device_code atau ID device; member_code berasal dari member_profiles.member_code. Device dan membership harus aktif pada fasilitas terkait.

## QR ticket access
POST `{LARAVEL_URL}/api/device/access`
```json
{"device_id":"GATE-001","device_type":"qrcode","scan_code":"BOOKING-CODE"}
```
scan_code adalah booking_code pada QR. Tiket harus fully_paid, approved, sedang dalam jadwal, dan sesuai device bila device ditetapkan.

## FaceRec AddPersons
POST `{FACEREC_URL}/action/AddPersons`, Basic Auth, JSON UTF-8. Pilih foto JPEG di form, tool mengubahnya menjadi data URL base64.
```json
{
 "operator":"AddPersons","DeviceID":1310023,"Total":1,
 "Personinfo_0":{
  "PersonType":0,"Name":"Nama Member","Gender":0,"Nation":1,"CardType":0,"IdCard":"MBR-0001",
  "Birthday":"","Telnum":"","Native":"","Address":"","Notes":"Testing API Sport Center",
  "MjCardFrom":2,"WiegandType":1,"CardMode":0,"MjCardNo":0,"Tempvalid":0,"CustomizeID":120,
  "PersonUUID":"UUID-UNIK","isCheckSimilarity":0,"ValidBegin":"2026-11-03T00:00:00",
  "ValidEnd":"2036-11-03T23:59:59","picinfo":"data:image/jpeg;base64,BASE64_FOTO"
 }
}
```
Kredensial Basic Auth hanya dikirim ke PHP proxy saat request, tidak disimpan ke localStorage atau database.

## Simulasi FaceRec VerifyPush
POST `{LARAVEL_URL}/api/device/access`
```json
{"operator":"VerifyPush","info":{"PersonID":1,"CreateTime":"2026-11-03T09:00:00","Similarity1":91.397,"Similarity2":0,"VerifyStatus":1,"VerfyType":1,"PersonType":0,"Name":"Nama Member","IdCard":"MBR-0001","DeviceID":1310023}}
```
Controller memakai info.IdCard sebagai member_code dan info.DeviceID untuk mencari device. Similarity minimum default 80.

## Membaca response
- `allowed:true`: akses diberikan.
- `allowed:false`: API memproses request tetapi menolak akses menurut aturan bisnis.
- HTTP 4xx/5xx atau transport error: validasi, server, atau koneksi perlu diperiksa.

## Keamanan
Tool ini untuk localhost/dev saja. Base URL Laravel dibatasi ke localhost/127.0.0.1. FaceRec URL hanya isi dengan perangkat yang dipercaya. Jangan expose folder ini ke internet.
