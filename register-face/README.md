# Register Face (Standalone PHP)

Aplikasi PHP terpisah dari Laravel untuk memilih member, mengambil foto dari kamera, dan menyimpan foto ke komputer lokal. API Laravel hanya dipakai untuk mengambil nama member.

## Menjalankan di Windows

1. Pastikan Laravel berjalan dan endpoint ini bisa dibuka: `http://127.0.0.1:8000/api/members`.
2. Buka PowerShell pada root repository, lalu jalankan:

   ```powershell
   php -S 127.0.0.1:8081 -t register-face
   ```

3. Buka `http://127.0.0.1:8081` di browser.
4. Pilih member, aktifkan kamera, ambil foto, lalu klik **Simpan Foto**.

PHP harus tersedia di PATH. Bisa juga menjalankan folder ini menggunakan Apache/PHP lokal.

## Struktur

- `index.php` — tampilan dan mengambil nama member dari API Laravel melalui PHP.
- `save-photo.php` — menyimpan foto JPEG dan metadata.
- `assets/css/style.css` — tampilan.
- `assets/js/app.js` — kamera, pemilihan member, dan proses simpan.
- `photos/` — dibuat otomatis ketika foto pertama disimpan; foto dan `index.json` tersimpan di komputer yang menjalankan PHP.

## Catatan

- Foto tidak dikirim ke API Laravel.
- Browser meminta izin kamera. Akses kamera bekerja di `localhost`/HTTPS, bukan dengan membuka file PHP sebagai `file://`.
- Pastikan folder aplikasi dapat ditulis oleh proses PHP.
- Endpoint member publik tidak menggunakan autentikasi sesuai kebutuhan awal. Jangan mengekspos server ini ke internet tanpa mempertimbangkan privasi.
- URL API bisa diganti melalui environment variable `MEMBERS_API_URL`.


## Pengaturan dan integrasi FaceRec

- Buka menu **Setting FaceRec** untuk mengatur IP perangkat. Nilai awal: `http://192.168.18.165`.
- IP disimpan di `localStorage` browser pada komputer/browser yang digunakan, bukan ke database.
- Setelah mengambil foto dan menyimpannya secara lokal, klik **Kirim ke FaceRec** untuk mengirim satu orang ke endpoint `{IP}/action/AddPersons` menggunakan body JSON `operator: AddPersons`, `DeviceID: 1310023`, `Total: 1`, dan `Personinfo_0.picinfo` berisi foto JPEG dalam format data URL/base64.
- Perangkat harus dapat dijangkau dari komputer yang menjalankan browser. Jika browser memblokir permintaan karena CORS atau mixed-content, perangkat perlu mengizinkan origin aplikasi atau pengiriman perlu dilakukan melalui proxy server lokal.
- Nilai data person seperti gender, kartu akses, tanggal lahir, alamat, dan nomor telepon saat ini menggunakan nilai default/kosong karena API member hanya menyediakan nama.
