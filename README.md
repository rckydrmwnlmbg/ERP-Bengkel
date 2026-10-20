# ERP Bengkel Alat Berat & Toko Sparepart (Demo Portofolio)

Sistem Informasi *Enterprise Resource Planning* (ERP) terpadu berbasis web yang dirancang khusus untuk operasional **bengkel alat berat** (excavator, bulldozer, dump truck tambang) dan **toko sparepart suku cadang**. Aplikasi ini mencakup manajemen master data pelanggan & unit alat berat, kartu stok *weighted average cost*, order servis (SPK), penagihan faktur berurutan, pembayaran cicilan piutang, laporan keuangan komprehensif, dan jejak audit (*audit log*).

> **Catatan Portofolio:** Seluruh data yang disajikan dalam repositori ini bersifat fiktif dan diberi penanda **MODE DEMO** pada antarmuka pengguna (*top banner* dan *footer*).

---

## Daftar Isi
- [Fitur Utama & Modul](#fitur-utama--modul)
- [Matriks Hak Akses Peran (RBAC)](#matriks-hak-akses-peran-rbac)
- [Kredensial Akun Demo](#kredensial-akun-demo)
- [Panduan Setup & Instalasi](#panduan-setup--instalasi)
  - [Opsi A: Instalasi Cepat via Docker Compose (Rekomendasi)](#opsi-a-instalasi-cepat-via-docker-compose-rekomendasi)
  - [Opsi B: Instalasi Manual (Lokal)](#opsi-b-instalasi-manual-lokal)
- [Skrip Demo 2 Menit (Alur PRD)](#skrip-demo-2-menit-alur-prd)
- [Log Keputusan Arsitektural (DECISIONS.md)](#log-keputusan-arsitektural-decisionsmd)
- [Daftar Penyederhanaan yang Disengaja](#daftar-penyederhanaan-yang-disengaja)
- [Konfigurasi Deployment Sederhana](#konfigurasi-deployment-sederhana)
- [Pengujian Otomatis (Test Suite)](#pengujian-otomatis-test-suite)

---

## Fitur Utama & Modul

1. **Master Data Pelanggan & Unit Alat Berat**:
   - Pencatatan pelanggan perorangan dan perusahaan kontraktor/tambang.
   - Manajemen unit alat berat milik pelanggan (Merk, Model, Nomor Seri, Kode Unit).
   - **Tarif Jasa Khusus per Pelanggan**: Penyesuaian tarif jasa negosiasi per klien.
   - Termin pembayaran faktur (*payment terms* dalam hari).
2. **Katalog Sparepart & Kartu Stok Dinamis**:
   - SKU unik, kategori (Engine, Hydraulic, Undercarriage, Electrical, Filters, dll.), dan satuan.
   - Perhitungan **Weighted Average Cost (HPP Rata-Rata Bergerak)** otomatis setiap barang masuk.
   - Kartu stok (*stock movements*) yang bersifat *append-only* dengan saldo terverifikasi.
   - Notifikasi dan peringatan stok menipis (*qty_on_hand <= min_stock*).
3. **Barang Masuk (Purchases) & Penyesuaian Stok (Opname)**:
   - Pencatatan penerimaan suku cadang dari pemasok (*supplier*).
   - Penyesuaian stok opname (*ADJUSTMENT_IN* / *ADJUSTMENT_OUT*) dengan alasan wajib.
4. **Order Servis / SPK (Work Orders)**:
   - Alur status resmi: `open` &rarr; `in_progress` &rarr; `completed` &rarr; `invoiced` (atau `cancelled`).
   - Alokasi sparepart seketika mengurangi stok dengan penguncian baris database (*row lock*).
   - **Antarmuka Mobile-Friendly**: Didesain nyaman digunakan teknisi/mekanik di layar ponsel (*touch-friendly*, kartu ringkas).
5. **Penagihan (Invoicing) & Penjualan Langsung**:
   - Penerbitan faktur dari Order Servis yang selesai atau Penjualan Langsung Toko (*Counter Sale*).
   - Penomoran faktur bulanan anti-loncat & anti-*race condition* (`INV-YYMM-####`).
   - Perhitungan diskon dan pajak dinamis (default 0% dan dibaca dari tabel pengaturan).
   - Cetak Faktur resmi dan ekspor siap cetak/PDF dengan watermark DEMO.
   - Mekanisme pembatalan (*void*) faktur dengan alasan wajib yang mengembalikan stok dan status WO.
6. **Pembayaran & Pengelolaan Piutang**:
   - Pembayaran tunai / transfer bank yang mendukung cicilan bertahap (*partially_paid* &rarr; *paid*).
   - Validasi ketat pencegahan *overpayment* (pembayaran melebihi sisa tagihan ditolak).
   - Halaman piutang komprehensif per pelanggan dengan klasifikasi umur piutang (*aging*).
7. **Pengeluaran Operasional & Laporan Keuangan**:
   - Pencatatan biaya operasional bengkel (gaji teknisi, listrik, bahan bakar, sewa workshop, dll.).
   - Laporan Omzet Penjualan (Harian/Bulanan).
   - Laporan Laba Rugi Sederhana (Omzet dikurangi HPP Sparepart dan Beban Operasional).
   - Laporan Arus Kas Operasional Riil (Penerimaan kas vs Pengeluaran kas).
   - Laporan Piutang & Aging (*Belum Jatuh Tempo*, *1-30 Hari*, *31-60 Hari*, *61-90 Hari*, *>90 Hari*).
   - Ekspor laporan ke format Microsoft Excel (.xlsx / .csv) dan tampilan cetak PDF.
8. **Dashboard Interaktif**:
   - Ringkasan metrik eksekutif bulan berjalan yang tersinkronisasi presisi dengan ReportService.
9. **Audit Log & Pengaturan**:
   - Perekaman otomatis setiap mutasi harga, perubahan status WO, void dokumen, dan stok.
   - Halaman visual audit log dengan filter aksi, entitas, pengguna, dan rentang tanggal.

---

## Matriks Hak Akses Peran (RBAC)

Aplikasi menerapkan kontrol hak akses berbasis peran (*Role-Based Access Control*) secara ketat di tingkat server:

| Fitur / Modul | Administrator | Kasir | Mekanik |
|---|:---:|:---:|:---:|
| **Master Data** (Customer, Part, Jasa, Supplier) | CRUD Lengkap | Lihat & Tambah Customer | Lihat Katalog |
| **Harga Beli / HPP / Laba Kotor** | **Dapat Melihat** | **Disembunyikan** | **Disembunyikan** |
| **Order Servis (SPK)** | Semua Aksi | Lihat Saja | Buat, Tambah Item, Update Status |
| **Penjualan Sparepart Langsung** | Ya | Ya | Tidak |
| **Penerbitan Invoice & Pembayaran** | Ya | Ya | Tidak |
| **Pembatalan (Void) Invoice & Pembayaran** | **Ya (Wajib Alasan)** | **Tidak** | **Tidak** |
| **Barang Masuk & Penyesuaian Stok** | Ya | Tidak | Tidak |
| **Pengeluaran Operasional** | Ya | Tidak | Tidak |
| **Laporan Finansial (Omzet, Laba Rugi, Kas)** | Ya | Tidak | Tidak |
| **Laporan Piutang & Aging Pelanggan** | Ya | Ya | Tidak |
| **Audit Log & Pengaturan Sistem** | Ya | Tidak | Tidak |

---

## Kredensial Akun Demo

Database telah disiapkan dengan data demonstrasi 12 bulan deterministik. Seluruh akun menggunakan password yang sama:

| Peran | Email Login | Password | Tujuan Pengujian |
|---|---|---|---|
| **Administrator** | `admin@bengkel.com` | `password` | Menguji akses penuh, margin laba kotor, HPP, barang masuk, audit log, dan void transaksi |
| **Kasir** | `kasir@bengkel.com` | `password` | Menguji alur penjualan toko, penerbitan faktur servis, pembayaran cicilan, dan laporan piutang |
| **Mekanik 1** | `mekanik1@bengkel.com` | `password` | Menguji alur teknisi di lapangan: membuat SPK alat berat, mengisi diagnosa, dan memasukkan part |
| **Mekanik 2** | `mekanik2@bengkel.com` | `password` | Menguji alur penugasan teknisi kedua |

---

## Panduan Setup & Instalasi

### Opsi A: Instalasi Cepat via Docker Compose (Rekomendasi)

Jika Anda memiliki Docker terpasang:
```bash
# 1. Clone repository
git clone https://github.com/rckydrmwnlmbg/ERP-Bengkel.git
cd ERP-Bengkel

# 2. Jalankan container
docker compose up -d
```
Aplikasi langsung aktif di: **`http://localhost:8000`**. Data demo 12 bulan langsung ter-seed secara otomatis.

---

### Opsi B: Instalasi Manual (Lokal)

#### Prasyarat:
- PHP >= 8.2 (ekstensi: `pdo`, `pdo_sqlite` atau `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `zip`)
- Composer >= 2.0
- Node.js >= 18.0 & NPM

#### Langkah Pemasangan:
```bash
# 1. Masuk ke direktori aplikasi
cd ERP-Bengkel/app

# 2. Pasang dependensi PHP dan Node
composer install
npm install

# 3. Konfigurasi Environment File
cp .env.example .env
php artisan key:generate

# 4. Compile asset frontend
npm run build

# 5. Jalankan Seeding Data Demonstrasi Deterministik
# (Perintah ini membersihkan database dan mengisi 11 customer, 45 sparepart, 110 order servis, 166 invoice, 158 pembayaran, dan kartu stok)
php artisan seed:demo

# 6. Jalankan Server Pengembangan
php artisan serve
```
Buka browser pada alamat: **`http://127.0.0.1:8000`**.

---

## Skrip Demo 2 Menit (Alur PRD)

Untuk mempresentasikan alur operasional bisnis bengkel secara mulus dalam waktu ± 2 menit:

1. **Langkah 1 — Tinjau Dashboard Eksekutif**:
   - Login sebagai **Admin** (`admin@bengkel.com` / `password`).
   - Tunjukkan metrik *Dashboard*: Omzet bulan ini, total piutang berjalan, peringatan sparepart yang berada di bawah batas minimum (*low stock*), dan grafik pergerakan kas.
2. **Langkah 2 — Buat Order Servis Pelanggan Khusus**:
   - Masuk ke menu **Order Servis (SPK)** &rarr; **Buat Order Servis Baru**.
   - Pilih pelanggan korporat yang memiliki tarif khusus (misal: **PT Mahakam Mineral Mandiri** atau **PT Borneo Prima Coal**).
   - Pilih unit alat berat (misal: *Excavator PC200-8*). Isi keluhan: *"Engine overheat dan oli hidrolik bocor"*. Klik Simpan.
3. **Langkah 3 — Tambah Sparepart & Jasa Servis**:
   - Pada halaman detail SPK, buka tab **Tambah Sparepart**: pilih filter oli atau seal ring (stok fisik langsung berkurang di kartu stok).
   - Buka tab **Tambah Jasa**: pilih *Overhaul Engine* atau *Servis Rutin*. Perhatikan bahwa harga jasa **otomatis menerapkan tarif khusus pelanggan** (bukan harga standar publik).
4. **Langkah 4 — Selesaikan Pekerjaan & Terbitkan Faktur**:
   - Ubah status order dari `open` &rarr; `in_progress` &rarr; `completed`.
   - Klik tombol **Terbitkan Invoice**: sistem membuat nomor faktur unik bulanan (`INV-2610-####`) dan status WO terkunci menjadi `invoiced`.
5. **Langkah 5 — Catat Pembayaran Cicilan**:
   - Buka faktur yang baru dibuat &rarr; Klik **Catat Pembayaran**.
   - Masukkan pembayaran parsial (misal 50% dari total tagihan).
   - Status faktur seketika berubah menjadi **Dibayar Sebagian (Partial)** dan sisa tagihan terhitung otomatis.
6. **Langkah 6 — Verifikasi Integritas Kartu Stok**:
   - Masuk ke menu **Sparepart** &rarr; klik ikon **Kartu Stok** pada part yang digunakan tadi.
   - Amati baris mutasi `SERVICE_OUT` yang mencatat nomor WO terkait, jumlah pengurangan, dan saldo sisa yang cocok 100% (*Integritas Valid*).
7. **Langkah 7 — Tinjau Laporan Keuangan & Umur Piutang**:
   - Buka menu **Laba Rugi**: amati pengakuan omzet dan HPP sparepart yang dihitung berdasarkan harga pokok historis (*snapshot*).
   - Buka menu **Laporan Piutang & Aging**: sisa tagihan faktur tadi langsung masuk ke kelompok umur piutang *Belum Jatuh Tempo*.

---

## Log Keputusan Arsitektural (DECISIONS.md)

Arsitektur aplikasi dibangun dengan prinsip kehati-hatian (*defensive programming*) untuk mencegah anomali data keuangan:

1. **Format Nilai Uang (BIGINT Tanpa Desimal)**:
   - Seluruh nominal finansial (harga, biaya, subtotal, pajak, diskon, saldo) disimpan dalam kolom integer `BIGINT` satuan Rupiah penuh. Tidak ada floating-point rounding error. Pembulatan pajak mengikuti standar *half-up* (`round()`).
2. **Strict Service Layer**:
   - Controller dirancang sangat tipis (hanya validasi `FormRequest` dan delegasi).
   - Seluruh mutasi database wajib melewati Service Class resmi:
     - `StockService`: Mutasi stok barang masuk, alokasi servis, penjualan, dan penyesuaian opname.
     - `PricingService`: Logika penentuan tarif khusus pelanggan vs standar publik, snapshot harga.
     - `WorkOrderService`: State machine status order servis dan validasi kelengkapan item.
     - `InvoiceService`: Penerbitan faktur dari WO / penjualan langsung, kalkulasi diskon & pajak, pembatalan (*void*).
     - `PaymentService`: Penerimaan pembayaran, pencegahan overpayment, pembatalan pembayaran.
     - `ReportService`: Perhitungan omzet, HPP, arus kas, laba rugi, dan umur piutang (*aging*).
     - `AuditService`: Pencatatan log historis mutasi penting ke database.
3. **Immutabilitas Kartu Stok & Row-Level Locking**:
   - Tabel `stock_movements` bersifat *strictly append-only* (tidak ada operasi `UPDATE` maupun `DELETE`).
   - Setiap transaksi mutasi stok menerapkan query database `lockForUpdate()` pada baris `parts` yang bersangkutan untuk menjamin integritas kuantitas di bawah beban permintaan paralel tinggi (*anti-race condition*).
4. **Immutabilitas Dokumen Faktur & Pembayaran**:
   - Faktur yang telah terbit tidak boleh diubah angkanya secara langsung.
   - Koreksi dilakukan melalui mekanisme `void` dengan alasan wajib (hanya oleh Admin) dan hanya diizinkan apabila faktur belum memiliki riwayat pembayaran valid.
   - Pembatalan faktur penjualan langsung mengembalikan kuantitas stok melalui mutasi `SALE_RETURN`.
5. **Penomoran Berurutan Anti-Race Gap**:
   - Format `INV-YYMM-####` dan `WO-YYMM-####` dibentuk secara berurutan per bulan menggunakan transaksi database terisolasi.
6. **Optimasi Bebas N+1 Query**:
   - Seluruh relasi pada daftar utama (Order Servis, Faktur, Pembelian, Customer, Pengeluaran, Audit Log) dimuat via eager loading (`with(...)`).
   - Model `Invoice::totalPaid()` secara otomatis memanfaatkan relasi koleksi di memori jika `validPayments` telah ter-load, sehingga tidak mengeksekusi kueri `SUM` SQL berulang pada perulangan tabel.

---

## Daftar Penyederhanaan yang Disengaja

Sesuai arahan spesifikasi PRD untuk memfokuskan demo portofolio pada kualitas logika bisnis inti:

1. **Satu Lokasi Bengkel (Single-Workshop)**: Tidak mendukung multi-cabang (*multi-branch*) maupun multi-gudang (*multi-warehouse*).
2. **Hutang Supplier Ditiadakan**: Pembelian sparepart (*purchases*) diasumsikan tunai. Mutasi barang masuk langsung menambah kuantitas dan memperbarui HPP rata-rata tanpa mencatat buku hutang dagang.
3. **Akuntansi Single-Entry Sederhana**: Sistem tidak menyelenggarakan pembukuan *double-entry* (jurnal debet/kredit dan buku besar akun). Laporan keuangan disajikan secara pragmatis berbasis arus kas operasional dan laba rugi operasional kas/faktur sederhana.
4. **Weighted Average Costing (Bukan FIFO Batch)**: Penghitungan HPP menggunakan rumus biaya rata-rata tertimbang (*weighted average cost*) yang diperbarui setiap barang masuk, bukan pelacakan batch kedaluwarsa FIFO/LIFO.
5. **Pembayaran 1-ke-1**: Satu transaksi pembayaran mencicil atau melunasi tepat satu faktur. Tidak ada alokasi pembayaran sekaligus untuk banyak faktur (*batch multi-invoice allocation*).
6. **Perpajakan Sederhana**: Tarif pajak PPN dibaca secara dinamis dari tabel pengaturan (default 0%) tanpa integrasi API e-Faktur Direktorat Jenderal Pajak (DJP).
7. **Di Luar Scope Lainnya**: Penggajian (*payroll*), integrasi payment gateway / QRIS, notifikasi WhatsApp Gateway, serta retur barang ke pemasok sengaja ditiadakan.

---

## Panduan Setup Produksi & Deployment

### 1. Deployment ke Vercel (Serverless + Database Eksternal)

Aplikasi telah dioptimalkan agar berjalan secara *stateless* di lingkungan serverless Vercel:
- **Proxy Dipercaya (`TrustProxies`)**: Seluruh request HTTPS melalui reverse proxy Vercel dikenali dengan benar di `app/bootstrap/app.php` (`trustProxies(at: '*')`).
- **Session & Cache Bebas Disk Lokal**: Menggunakan `SESSION_DRIVER=cookie` (terenkripsi) dan `CACHE_STORE=array` agar tidak bergantung pada filesystem ephemeral.
- **Koneksi Database Eksternal**: Serverless instances berbagi database terkelola yang sama secara persisten.

#### Langkah Setup Produksi di Vercel:
1. **Siapkan Database Eksternal Terkelola**:
   - Buat database cloud gratis (disarankan **PostgreSQL** di [Neon.tech](https://neon.tech), [Supabase](https://supabase.com), atau MySQL di [Aiven](https://aiven.io)).
2. **Atur Environment Variables di Dashboard Vercel**:
   Buka **Settings &rarr; Environment Variables** di proyek Vercel Anda, lalu tambahkan:
   ```env
   APP_NAME="ERP Bengkel Alat Berat"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://<nama-proyek-anda>.vercel.app
   APP_KEY=base64:6YG6xCrWI4bO2wQWxgJupu51Byfn+7ZHWDmSRccjTug=
   
   DB_CONNECTION=pgsql
   DB_HOST=<host-database-cloud>
   DB_PORT=5432
   DB_DATABASE=<nama-database>
   DB_USERNAME=<user-database>
   DB_PASSWORD=<password-database>
   DB_SSLMODE=require

   SESSION_DRIVER=cookie
   CACHE_STORE=array
   QUEUE_CONNECTION=sync
   DEMO=true
   ```
3. **Jalankan Migrasi & Reset Data Demo (Langkah Terpisah dari Deploy)**:
   > ⚠️ **PENTING**: Di serverless Vercel, migrasi **TIDAK DIJALANKAN OTOMATIS** saat cold-start untuk mencegah *request timeout* dan *race condition*.
   
   Untuk melakukan inisialisasi awal atau mereset data demo ke database cloud produksi, arahkan file `.env` di komputer lokal Anda ke database cloud tersebut, lalu jalankan satu perintah:
   ```bash
   cd app
   php artisan seed:demo --fresh
   ```
   Perintah ini akan membersihkan database, menjalankan seluruh migrasi, mengisi data realistis 12 bulan, dan memverifikasi seluruh 6 invarian bisnis.

---

### 2. Keterbatasan Runtime Vercel & Rekomendasi Pindah Hosting

Meskipun Vercel dapat digunakan dengan database eksternal terkelola, runtime serverless memiliki karakteristik inheren:
- **Connection Exhaustion**: Setiap instance serverless Lambda membuka koneksi mandiri. Tanpa connection pooler (seperti Supabase Pooler port 6543 atau Neon Connection Pooling), traffic tinggi dapat menghabiskan kuota *max connections*.
- **Ketiadaan Background Worker**: Vercel tidak mendukung proses daemon (`php artisan queue:work` atau cron `schedule:run`).
- **Timeout 10-15 Detik**: Pembuatan laporan finansial kompleks atau ekspor berisiko mengalami *504 Gateway Timeout*.

#### Opsi Hosting Alternatif yang Lebih Ideal (Container):
1. **Render.com** (Konfigurasi [`render.yaml`](file:///root/ERP/render.yaml) & [`app/Dockerfile`](file:///root/ERP/app/Dockerfile) telah tersedia):
   - Mendukung container Docker native, free tier managed PostgreSQL, dan terminal SSH langsung di web dashboard untuk menjalankan `php artisan seed:demo --fresh`.
2. **Railway / Fly.io**:
   - Mendukung container Laravel lengkap dengan worker background antrian dan database terintegrasi.
3. **VPS Tradisional (Coolify / Laravel Forge / Docker Compose)**:
   - File [`docker-compose.yml`](file:///root/ERP/docker-compose.yml) siap pakai untuk deploy mandiri dengan satu perintah `docker compose up -d`.

---

### 3. Pengujian Otomatis (Test Suite)

Seluruh fungsionalitas diuji secara ketat menggunakan **PHPUnit** dengan database in-memory SQLite:

```bash
cd app
./vendor/bin/phpunit --testdox
```

### Cakupan Pengujian:
- **`RoleAccessTest`**: Pengujian autentikasi tamu, admin, kasir, mekanik, akun nonaktif, dan banner DEMO.
- **`DatabaseConstraintTest`**: Verifikasi skema database, foreign key restrict, check constraint nilai non-negatif.
- **`CustomerCrudTest`, `PartCrudTest`, `ServiceCrudTest`, `SupplierCrudTest`**: Pengujian validasi FormRequest, RBAC, soft-deletes, dan audit log perubahan harga.
- **`StockServiceTest` & Pengujian Modul Stok**:
  - `T-01`: Rekam barang masuk dan pembaruan weighted average cost (BR-S7).
  - `T-02`: Pengurangan stok saat sparepart ditambahkan ke order servis (BR-S4).
  - `T-08`: Penguncian baris konkuren (*row-level locking*) mencegah *race condition* stok minus (BR-S8).
  - `T-09`: Pengujian integritas kartu stok terhadap saldo aktual (BR-S1).
- **`WorkOrderServiceTest` & `WorkOrderControllerTest`**:
  - State machine transisi status (BR-W1 s/d BR-W4), pembatalan ilegal, dan snapshot tarif negosiasi customer.
- **`InvoiceServiceTest` & `InvoiceControllerTest`**:
  - `T-03`: Pembuatan invoice dari order servis selesai, snapshot harga item, penomoran berurutan unik.
  - `T-04`: Penjualan langsung (*counter sale*) memotong stok seketika (BR-S5).
  - `T-05`: Pembayaran cicilan bertahap, status *partially_paid*, penolakan overpayment.
  - `T-06`: Pembatalan (*void*) invoice dan retur stok barang (*SALE_RETURN*).
- **`ReportServiceTest` & `ReportControllerTest`**:
  - `T-07`: Konsistensi laporan omzet, HPP, laba kotor, arus kas riil, dan aging piutang.
  - Uji konsistensi independen BR-R8 (Total piutang ReportService == Total saldo faktur aktif == Total breakdown umur piutang).
- **`SeedDemoTest`**:
  - Eksekusi `php artisan seed:demo` dengan verifikasi 6 invarian bisnis.
- **`AuditLogAndHardeningTest`**:
  - Pengujian halaman visual Audit Log, pengaturan dinamis sistem, verifikasi bebas query N+1, dan banner DEMO.
