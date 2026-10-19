# Demo ERP Bengkel Alat Berat & Toko Sparepart

Proyek portofolio sistem informasi Enterprise Resource Planning (ERP) untuk bengkel alat berat dan toko sparepart. Semua data yang disajikan bersifat fiktif dan berada dalam mode **DEMO**.

## Stack Teknologi
- **Backend**: Laravel 12 (PHP 8.3+)
- **Database**: SQLite (default dev/test) / MySQL
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Testing**: PHPUnit / Pest Test Harness
- **Zona Waktu**: `Asia/Jakarta` | **Bahasa UI**: Bahasa Indonesia

---

## Persiapan & Menjalankan Aplikasi

1. **Masuk ke folder aplikasi Laravel:**
   ```bash
   cd app
   ```

2. **Salin file lingkungan (.env):**
   ```bash
   cp .env.example .env
   ```

3. **Generate App Key (bila belum):**
   ```bash
   php artisan key:generate
   ```

4. **Jalankan Migrasi Database dan Seeder:**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   Buka peramban di `http://127.0.0.1:8000`.

---

## Kredensial Pengguna Demo

| Peran (Role) | Email | Password | Hak Akses Utama |
|---|---|---|---|
| **Admin** | `admin@example.com` | `password123` | Akses penuh: CRUD Master Data, HPP, Penyesuaian Stok, Void Keuangan, Laporan Penuh, Audit Log, Pengaturan |
| **Kasir** | `cashier@example.com` | `password123` | Lihat & tambah customer, penjualan langsung, terbitkan invoice & terima pembayaran, lihat laporan piutang |
| **Mekanik** | `mechanic@example.com` | `password123` | Buat order servis, input diagnosa & item sparepart/jasa, ubah status order servis, lihat master data |

---

## Menjalankan Pengujian Otomatis (Test Suite)

Pengujian mencakup seluruh rute autentikasi, pengujian hak akses per peran (*Role Access Control*), dan verifikasi *Database Constraints* (check constraints, unique keys, cascade/restrict foreign keys).

```bash
cd app
./vendor/bin/phpunit --testdox
```

---

## Status Fase (Roadmap)
- [x] **Fase 1 — Fondasi**: Struktur proyek, seluruh migrasi skema tabel (`docs/DATA_MODEL.md`), autentikasi 3 peran (`admin`, `cashier`, `mechanic`), layout dasar (Alpine.js collapsible sidebar, banner DEMO), dan test harness.
- [ ] **Fase 2 — Master Data**: CRUD Customer, Sparepart, Jasa, Supplier, unit alat berat.
- [ ] **Fase 3 — Mesin Stok**: `StockService`, mutasi stok, weighted average cost, kartu stok.
- [ ] **Fase 4 — Order Servis**: Alur status order servis, snapshot harga, integrasi stok.
- [ ] **Fase 5 — Invoice & Pembayaran**: Penagihan, pencicilan, mekanisme void.
- [ ] **Fase 6 — Laporan & Dashboard**: Omzet, HPP, laba rugi sederhana, aging piutang.
- [ ] **Fase 7 — Data Dummy**: Seeding deterministik 12 bulan dan verifikasi invarian.
- [ ] **Fase 8 — Pengerasan & Portofolio**: Audit query bebas N+1, skrip demo 2 menit.
