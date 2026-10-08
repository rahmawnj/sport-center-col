# Analisis Struktur Database Sport Center Sampit

> Tujuan: struktur master data + booking + transaksi yang fleksibel untuk 6 jenis olahraga
> dengan model harga yang berbeda-beda (bulanan, harian, per jam, sepuasnya, per sesi, per visit, harga beda tiap jam).

---

## 1. Kondisi Saat Ini (Temuan Penting)

Saat ini ada **dua generasi skema yang tumpang tindih dan tidak konsisten**:

### 1.1 Skema Legacy (v1) — migrations sudah DIHAPUS, models masih ada

Migrations `2026_08_19_*` dan `2026_09_07_*` / `2026_09_08_*` sudah di-`git rm`, tapi
**model-nya masih tertinggal** di `app/Models/`:

| Model | Tabel yang dirujuk | Status migration |
|---|---|---|
| `Zone`, `ZoneSpace`, `Facility`, `PricingRate` | `zones`, `zone_spaces`, `facilities`, `pricing_rates` | Dihapus |
| `Trainer`, `AddOn`, `OperationalHour` | `trainers`, `add_ons`, `operational_hours` | Dihapus |
| `MembershipPackage`, `UserSubscription` | `membership_packages`, `user_subscriptions` | Dihapus |
| `Transaction`, `TransactionDetail` | `transactions`, `transaction_details` | Dihapus (skema lama) |
| `TransactionAddOn`, `TransactionPaymentProof` | `transaction_add_ons`, `transaction_payment_proofs` | Dihapus |
| `CashTransaction`, `DailyClosing`, `Role`, `IotDevice`, `IotLog`, `GateAccessLog` | `cash_transactions`, `daily_closings`, `roles`, dst. | Dihapus |

### 1.2 Skema Baru (v2) — migrations 2026-09-15 (AKTIF)

| Tabel | Isi |
|---|---|
| `sports` | master jenis olahraga |
| `packages` | katalog paket per olahraga |
| `package_time_rules` | aturan jam/hari + harga override |
| `members` | data member |
| `transactions` | header transaksi |
| `transaction_details` | baris item transaksi (paket) |
| `member_packages` | kepemilikan paket member (entitlement + kuota) |
| `resources` | unit fisik yang bisa dibooking |
| `bookings` | reservasi slot waktu |

### 1.3 Akibat Inkonsistensi (KRITIS)

1. **Model lama akan error saat runtime.** Contoh nyata:
   - `app/Models/Transaction.php` punya `$fillable = [..., 'booking_code', 'customer_type', 'guest_name', 'payment_method', 'booking_status', 'handled_by']` — kolom-kolom itu **tidak ada** di tabel `transactions` versi baru (yang hanya punya `member_id`, `invoice_number`, `total_amount`, `payment_status`).
   - `app/Models/TransactionDetail.php` merujuk `zone_space_id`, `trainer_id`, `start_time`, `end_time`, `price_rate`, `subtotal` — tidak ada di tabel `transaction_details` versi baru (yang isinya `package_id`, `qty`, `price`, `sub_total`).
   - `app/Models/Member.php`, `MemberPackage.php`, `Booking.php`, `Resource.php` masih kosong (`//`), tidak ada relasi.
2. **Dua nama tabel transaksi berbeda**: versi lama `transaction_details` (zone-based) vs versi baru `transaction_details` (package-based) — kontrak kolomnya beda total.

> **Kesimpulan awal**: skema v2 (sports/packages) adalah arah yang benar untuk "master data fleksibel",
> tapi harus **digabung dengan konsep yang masih valid dari v1** (guest/non-member, payment method,
> trainer, harga per jam via rate) dan **membersihkan model-model mati**.

---

## 2. Analisis Skema Baru (v2) — Kelebihan & Kekurangan per Tabel

### 2.1 `sports`
```php
$table->string('uuid', 8)->unique();
$table->string('name');
$table->text('description');
$table->string('thumbnail')->nullable();
$table->integer('is_active')->default(1);
$table->integer('is_online')->default(0);
```
**Kelebihan**: master olahraga terpisah dan bersih.
**Kekurangan**:
- `uuid` 8 karakter random → risiko kolisi + tidak ada gunanya (sport bisa pakai `id` biasa).
- `is_active`/`is_online` pakai `integer` bukan `boolean`.
- Tidak ada `slug`/urutan tampil (`sort_order`) untuk UI.

### 2.2 `packages`
```php
$table->enum('package_type', ['membership', 'pass', 'addon']);
$table->decimal('price');
$table->integer('duration_value')->nullable();
$table->string('duration_unit')->nullable();
$table->integer('requires_active_membership')->default(0);
$table->integer('is_promo')->default(0);
```
**Kelebihan**:
- `duration_value` + `duration_unit` sudah dibuat nullable → sudah mengakomodir paket tanpa durasi (per visit, sepuasnya).
- `requires_active_membership` menangani kasus "Sesi Trainer wajib paket bulanan".

**Kekurangan (PENTING)**:
- **`package_type` terlalu sempit dan rancu**. Enum `membership | pass | addon` mencampur *semantik billing* dengan *jenis produk*. Kebutuhan nyata:
  - Gym: bulanan (membership), harian (daily), trainer session.
  - Ice skating: per jam, sepuasnya 1x masuk, jam tertentu.
  - Yoga/Pilates: member per sesi, member per paket, paket hari/jam tertentu.
  - Spinning: per 1x masuk ruangan.
  - Billiard: per jam, promo.
  - Padel: 1 jam, promo jam tertentu, per jam tertentu.
  
  Tidak ada representasi yang jelas untuk "sepuasnya/unlimited" vs "per visit" vs "per jam".
- **`duration_unit` free string** (bisa typo: 'month' vs 'months') — perlu enum atau konstanta.
- **`price` single value** tidak bisa menyimpan harga yang berbeda per jam/hari; ini di-handle terpisah di `package_time_rules` (hanya via `override_price`), tapi tidak ada harga untuk rentang **tanggal** (promo kalender) atau **overnight**.

### 2.3 `package_time_rules`
```php
$table->integer('day_of_week')->comment('1-6 (Minggu-Sabtu), jika 0 = unlimited');
$table->time('start_time');
$table->time('end_time');
$table->decimal('override_price')->nullable();
```
**Kelebihan**: konsep "harga beda tiap jam/hari" sudah terwakili. Ini inti fleksibilitas.
**Kekurangan**:
- `day_of_week` komentarnya salah/ambigu (1–6 Minggu–Sabtu tidak konsisten; harus 0–6 atau 1–7).
- `start_time`/`end_time` bertipe `time` → **tidak bisa rentang melewati tengah malam** (23:00–02:00).
- Tidak ada `date_start`/`date_end` → tidak bisa promo berbasis tanggal.
- Tidak ada `priority` → saat dua rule tumpang tindih, tidak jelas mana yang menang.
- Tidak ada batas `min_duration`/`max_duration` untuk paket per jam.
- `override_price` hanya mendukung "ganti harga penuh", tidak ada selisih/persen.

### 2.4 `members`
```php
$table->bigInteger('user_id')->nullable()->default(0);
$table->enum('member_status', ['prospect', 'active', 'inactive']);
```
**Kekurangan**:
- `user_id` pakai `bigInteger` + `default(0)` (magic value), **tanpa foreign key**. Harusnya `foreignId()->nullable()->constrained()`.
- Tidak ada relasi/`member_code` generasi otomatis di model.

### 2.5 `transactions`
```php
$table->foreignId('member_id')->constrained()->onDelete('cascade');
$table->string('invoice_number');
$table->decimal('total_amount');
$table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
```
**Kekurangan (KRITIS untuk kebutuhan nyata)**:
- **Hanya bisa member, tidak ada tamu (walk-in)**. Kasus "spinning per 1x masuk" atau "ice skating sepuasnya" umumnya tamu tanpa jadi member. Legacy sudah punya `customer_type` (`member`/`general`) + `guest_name`.
- `invoice_number` **tidak unique** → risiko duplikat di laporan keuangan.
- Tidak ada `payment_method`, `handled_by` (kasir), `paid_at`, `booking_status`.
- `payment_status` enum kurang: butuh `dp_paid`/`half_paid` (legacy punya `unpaid|dp_paid|fully_paid`).
- `onDelete('cascade')` pada `member_id` berbahaya → transaksi keuangan terhapus kalau member dihapus (harusnya `restrict`/`set null`).

### 2.6 `transaction_details`
```php
$table->foreignId('package_id')->constrained()->onDelete('cascade');
$table->integer('qty')->default(1);
$table->decimal('price');
$table->decimal('sub_total');
```
**Kelebihan**: `price` snapshot bagus (harga terkunci saat transaksi).
**Kekurangan**:
- Tidak ada link ke `resource_id`/`trainer_id`/jadwal pakai (`start_time`/`end_time`) → tidak bisa tahu "padel lapangan mana, jam berapa" dari transaksi saja.
- `sub_total` harusnya dihitung, tapi tidak masalah untuk snapshot.
- Tidak ada link ke `package_time_rules` yang dipakai (untuk audit harga).

### 2.7 `member_packages` (entitlement)
```php
$table->foreignId('transaction_detail_id')->constrained()->onDelete('cascade');
$table->timestamp('valid_from');
$table->timestamp('expired_at');
$table->integer('remaining_quota')->nullable();
$table->enum('status', ['active', 'used', 'expired']);
```
**Kelebihan (KONSEP BAGUS)**: memisahkan "pembelian paket" (transaksi) dari "kepemilikan/hak pakai" (entitlement) + `remaining_quota` untuk paket multi-sesi. Ini kunci untuk membership bulanan & paket multi-visit.
**Kekurangan**:
- Terikat ke `transaction_detail_id` (1:1 dengan baris detail) — kaku. Sebaiknya ke `transaction_id` saja (atau `package_id` + `transaction_id`), karena 1 baris detail = 1 entitlement.
- `status` tidak perlu `used` sebagai status tersimpan — "used" adalah derivasi dari `remaining_quota == 0` / `expired_at` lewat.
- Tidak ada `expired_at` index untuk query "member aktif".

### 2.8 `resources`
```php
$table->foreignId('sport_id')->constrained()->onDelete('cascade');
$table->integer('capacity')->nullable();
$table->enum('status', ['available', 'maintenance', 'booked']);
```
**Kelebihan**: konsep unit fisik (lapangan padel, meja billiard, ruang yoga, rink) terpisah dari `sports`. Benar.
**Kekurangan**:
- **`status = booked` adalah anti-pattern** → ketersediaan harus dihitung dari `bookings`, bukan disimpan. Kalau disimpan, pasti basi/inkonsisten.
- Kurang `is_active` (nonaktif permanen) vs `maintenance` (sementara).

### 2.9 `bookings`
```php
$table->foreignId('member_package_id')->constrained()->onDelete('cascade');
$table->foreignId('resource_id')->constrained()->onDelete('cascade');
$table->date('book_date');
$table->time('start_time');
$table->time('end_time');
$table->enum('status', ['pending_payment', 'booked', 'completed', 'cancelled']);
```
**Kekurangan**:
- **Wajib lewat `member_package_id`** → tidak bisa booking walk-in per-visit tanpa entitlement. Harusnya opsional.
- Tidak ada link langsung ke `transaction_id`/`member_id`/`participants`.
- `time` type → masalah overnight.
- Tidak ada `price` snapshot → harga booking bergantung penuh ke package (bisa berubah).
- `onDelete('cascade')` pada resource → booking historis hilang kalau resource dihapus.

---

## 3. Kelebihan & Kekurangan Skema Secara Umum

### Kelebihan (yang harus dipertahankan)
1. **Hierarki `sports → packages → package_time_rules`** adalah pendekatan master data yang tepat untuk harga fleksibel.
2. **Konsep `member_packages` (entitlement + kuota)** benar untuk membership & paket multi-sesi.
3. **Pemisahan `resources` (unit fisik) dari `sports` (jenis olahraga)** benar.
4. **Price snapshot di transaction detail** benar untuk integritas finansial.
5. `duration_value/unit` nullable sudah disadari → sudah mengarah ke "paket tanpa durasi".

### Kekurangan (yang harus diperbaiki)
1. **Dua skema coexist, model mati menumpuk** → runtime error & kebingungan. Harus direkonsiliasi.
2. **`package_type` tidak memodelkan semantik billing** (unlimited/per visit/per jam) → tidak bisa membedakan perlakuan harga.
3. **Tidak ada dukungan tamu (guest/walk-in)** → padahal mayoritas olahraga (spinning, ice skating, billiard, padel) sering non-member.
4. **Booking & transaksi terputus** → tidak ada alur "booking + bayar" untuk per-visit/per-jam.
5. **Harga fleksibel masih setengah jadi**: tidak ada rentang tanggal, overnight, priority, trainer surcharge.
6. **Enum status ditanam di DB** → susah diubah di MySQL (butuh `DB::statement` ALTER). Banyak enum: `package_type`, `member_status`, `payment_status`, `status` (resources/member_packages/bookings).
7. **Tidak ada unique constraint finansial** (`invoice_number`, `booking_code`).
8. **`decimal` tanpa presisi** → default MySQL `decimal(8,2)`, rawan overflow (legacy pakai `decimal(15,2)`).
9. **`onDelete('cascade')` dipakai sembarangan pada data keuangan/historis**.
10. **`resources.status = booked`** dan `member_packages.status = used` adalah state yang sebaiknya dihitung, bukan disimpan.

---

## 4. Saran / Rekomendasi

### 4.1 Prinsip
1. **Satu sumber kebenaran harga**: harga tidak pernah "dihitung saat ini" untuk transaksi — selalu snapshot.
2. **Pisahkan tiga domain**: *Katalog* (sports/packages/rates), *Entitlement* (members/member_packages), *Operasional* (transactions/bookings/resources).
3. **Availability dihitung, bukan disimpan.**
4. **Enum status dibuat sebagai string/konstanta di aplikasi** (atau tabel lookup), bukan DB enum, supaya mudah berkembang.

### 4.2 Struktur Target (rekomendasi)

```
sports ──< packages ──< package_rates (price matrix)
                      └─< package_trainers (jika trainer per paket)

members ──< member_packages (entitlement) ──< bookings
                     └─< transactions ──< transaction_items
                                          └─< transaction_payments
resources ──< bookings
trainers
```

#### (A) Master data

**`sports`** — jenis olahraga
```php
Schema::create('sports', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('thumbnail')->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_online')->default(false);
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
```

**`packages`** — katalog paket (produk)
```php
Schema::create('packages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sport_id')->constrained('sports');
    $table->string('name');
    $table->text('description')->nullable();

    // Semantik billing — inilah pembeda perlakuan harga:
    // membership: bulanan/harian (punya valid_from..expired_at)
    // per_visit  : 1x masuk/sesi (spinning, yoga per sesi)
    // per_hour   : bayar per jam (ice skating, billiard, padel)
    // unlimited  : sepuasnya 1x masuk (ice skating)
    // trainer_session: sesi trainer (wajib membership aktif)
    $table->string('pricing_type');            // 'membership'|'per_visit'|'per_hour'|'unlimited'|'trainer_session'

    $table->decimal('price', 15, 2)->default(0);       // harga default/base
    $table->integer('duration_value')->nullable();     // untuk membership / per_hour
    $table->string('duration_unit')->nullable();       // 'day'|'month'|'hour'|'session'
    $table->integer('session_count')->nullable();      // jumlah sesi utk paket multi-sesi (yoga per paket)
    $table->boolean('requires_active_membership')->default(false);
    $table->boolean('is_promo')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**`package_rates`** — price matrix (pengganti `package_time_rules`, lebih umum)
```php
Schema::create('package_rates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
    $table->unsignedTinyInteger('day_of_week')->nullable(); // null = semua hari (0-6, 0=Minggu)
    $table->time('start_time')->nullable();                // null = sepanjang hari
    $table->time('end_time')->nullable();                  // nullable utk dukung overnight (logika resolve di app)
    $table->date('date_start')->nullable();                // promo berbasis tanggal
    $table->date('date_end')->nullable();
    $table->decimal('price', 15, 2)->nullable();           // null = pakai packages.price
    $table->integer('priority')->default(0);               // rule lebih spesifik menang
    $table->timestamps();
});
```

#### (B) Entitlement

**`members`**
```php
Schema::create('members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('member_code')->unique();
    $table->string('member_status')->default('prospect'); // prospect|active|inactive
    $table->timestamp('joined_at')->nullable();
    $table->string('image_profile')->nullable();
    $table->timestamps();
});
```

**`member_packages`** — hak pakai yang dibeli
```php
Schema::create('member_packages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
    $table->foreignId('package_id')->constrained('packages');
    $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
    $table->timestamp('valid_from')->nullable();
    $table->timestamp('expired_at')->nullable();
    $table->integer('remaining_quota')->nullable();        // null = tak terbatas (membership)
    $table->string('status')->default('active');           // active|expired|cancelled
    $table->timestamps();
    $table->index('expired_at');
});
```

#### (C) Operasional (transaksi + booking)

**`transactions`** — header (member & tamu)
```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number')->unique();
    $table->string('customer_type')->default('member');    // member|guest
    $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
    $table->string('guest_name')->nullable();
    $table->string('payment_method')->nullable();          // cash|bank_transfer
    $table->decimal('total_amount', 15, 2)->default(0);
    $table->string('payment_status')->default('unpaid');   // unpaid|dp_paid|fully_paid|refunded
    $table->string('booking_status')->nullable();          // pending|approved|rejected|cancelled
    $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

**`transaction_items`** — baris item (snapshot harga + jadwal)
```php
Schema::create('transaction_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
    $table->foreignId('package_id')->nullable()->constrained('packages');
    $table->foreignId('package_rate_id')->nullable()->constrained('package_rates'); // audit harga
    $table->foreignId('resource_id')->nullable()->constrained('resources');
    $table->foreignId('trainer_id')->nullable()->constrained('trainers')->nullOnDelete();
    $table->integer('qty')->default(1);
    $table->decimal('unit_price', 15, 2);
    $table->decimal('subtotal', 15, 2);
    $table->dateTime('start_time')->nullable();   // jadwal pakai (per_hour / trainer)
    $table->dateTime('end_time')->nullable();
    $table->timestamps();
});
```

**`bookings`** — reservasi slot (terhubung ke item & entitlement, bukan wajib entitlement)
```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->string('booking_code')->unique()->nullable();
    $table->foreignId('transaction_item_id')->nullable()->constrained('transaction_items')->nullOnDelete();
    $table->foreignId('member_package_id')->nullable()->constrained('member_packages')->nullOnDelete();
    $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
    $table->foreignId('resource_id')->constrained('resources');
    $table->date('book_date');
    $table->time('start_time');
    $table->time('end_time');
    $table->integer('participants')->default(1);
    $table->string('status')->default('pending_payment'); // pending_payment|booked|completed|cancelled
    $table->timestamps();
});
```

**`transaction_payments`** — bukti/pembayaran (half/full)
```php
Schema::create('transaction_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
    $table->string('payment_option')->default('full_payment'); // full_payment|half_payment|pay_later
    $table->decimal('amount_paid', 15, 2)->default(0);
    $table->string('proof_path')->nullable();
    $table->timestamps();
});
```

**`resources`** — tanpa status "booked"
```php
Schema::create('resources', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sport_id')->constrained('sports');
    $table->string('name');
    $table->integer('capacity')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**`trainers`**
```php
Schema::create('trainers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('specialty')->nullable();
    $table->string('phone')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
```

---

## 5. Pemetaan Kebutuhan ke Struktur (bukti struktur cukup)

| Olahraga | Paket | `pricing_type` | `duration` | `session_count` | rate yang dipakai |
|---|---|---|---|---|---|
| Gym | Paket bulanan | `membership` | 1 / month | — | price default |
| Gym | Paket harian | `membership` | 1 / day | — | price default |
| Gym | Sesi trainer | `trainer_session` | — | — | + `requires_active_membership` |
| Gym | Jam tertentu | `membership` | — | — | `package_rates` day+time |
| Ice skating | Per jam | `per_hour` | 1 / hour | — | `package_rates` (jam tertentu) |
| Ice skating | Sepuasnya 1x masuk | `unlimited` | — | — | price default |
| Ice skating | Jam tertentu | `per_hour` | — | — | `package_rates` day+time |
| Yoga/Pilates | Member per sesi | `per_visit` | — | 1 | price default |
| Yoga/Pilates | Member per paket | `per_visit` | — | N | `session_count = N` |
| Yoga/Pilates | Hari/jam tertentu | `per_visit` | — | 1 | `package_rates` |
| Spinning | Per 1x masuk ruangan | `per_visit` | — | 1 | price default |
| Billiard | Per jam | `per_hour` | 1 / hour | — | price default |
| Billiard | Promo | `per_hour` | 1 / hour | — | `package_rates` (date range) |
| Padel | 1 jam | `per_hour` | 1 / hour | — | price default |
| Padel | Promo jam tertentu | `per_hour` | 1 / hour | — | `package_rates` |
| Padel | Per jam tertentu | `per_hour` | 1 / hour | — | `package_rates` |

### Alur resolve harga (satu fungsi di service)
1. Ambil `package` dari item.
2. Cari `package_rates` yang cocok: `day_of_week` cocok (atau null) DAN `start_time <= mulai < end_time` (atau null) DAN `date_start <= tanggal <= date_end` (atau null).
3. Urutkan `priority` desc, ambil yang pertama → `rate.price ?? package.price`.
4. Snapshot hasilnya ke `transaction_items.unit_price` + `package_rate_id`.

---

## 6. Prioritas Aksi (rekomendasi urutan)

1. **[Kritis] Bersihkan model mati**: hapus/pindahkan model legacy yang tabelnya tidak ada (`Zone`, `ZoneSpace`, `Facility`, `PricingRate`, `AddOn`, `MembershipPackage`, `UserSubscription`, `Trainer`, `OperationalHour`, `Iot*`, `CashTransaction`, `DailyClosing`, `Role`, `TransactionAddOn`, `TransactionPaymentProof`), atau buat migration-nya kembali jika memang masih dipakai UI (lihat `resources/js/pages/zones`, `pricing-rates`, dll masih ada). **Putuskan: hapus UI-nya atau hidupkan tabelnya.**
2. **Rekonsiliasi `transactions`/`transaction_details`**: satukan kolom valid dari legacy (`customer_type`, `guest_name`, `payment_method`, `booking_status`, `handled_by`, `paid_at`) ke skema baru, dan sesuaikan model `Transaction`/`TransactionDetail`.
3. **Ganti `package_type` enum → `pricing_type` string** + tambah `session_count`.
4. **Evolusi `package_time_rules` → `package_rates`** (tambah `date_start/end`, `priority`, `day_of_week` nullable).
5. **Longgarkan `bookings`**: `member_package_id` nullable, tambah `transaction_item_id` + `member_id` + `participants`.
6. **`member_packages`**: ganti `transaction_detail_id` → `transaction_id`, tambah index `expired_at`.
7. **Fix finansial**: `invoice_number` unique, `decimal(15,2)`, `onDelete` yang benar (restrict/nullOnDelete, bukan cascade).
8. **Hapus `resources.status = booked`** dan `member_packages.status = used` → hitung dari data.

---

## 7. Ringkasan Satu Paragraf

Skema v2 (`sports → packages → package_time_rules` + `member_packages` entitlement + `resources` + `bookings`)
sudah benar arahnya untuk master data yang fleksibel, tapi belum tuntas: **`package_type` tidak memodelkan
perbedaan semantik harga** (unlimited / per visit / per jam), **belum mendukung tamu (walk-in)**,
**booking & transaksi belum tersambung**, dan **harga fleksibel masih terbatas** (tanpa rentang tanggal,
overnight, prioritas). Saran utama: satukan dua skema jadi satu, ganti `package_type` dengan `pricing_type`
yang menggambarkan *cara menagih*, ubah `package_time_rules` jadi `package_rates` (price matrix) dengan
resolusi prioritas, dan longgarkan `bookings` agar tidak wajib melalui entitlement.
