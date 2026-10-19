# Log Keputusan & Catatan Klarifikasi (DECISIONS.md)

Dokumen ini mencatat keputusan arsitektural, klarifikasi ambiguitas, resolusi konflik aturan, serta pertanyaan terbuka antara Agent dan Pemilik Proyek untuk pengembangan **Demo ERP Bengkel Alat Berat & Toko Sparepart**.

---

## 1. Keputusan Awal (Telah Ditetapkan oleh Spesifikasi)

1. **Format Nilai Uang (BIGINT)**:
   - Semua kolom uang di database menggunakan tipe `BIGINT` tanpa desimal (satuan rupiah utuh).
   - Pembulatan kalkulasi (pajak, HPP rata-rata) menggunakan metode standard *half-up* ke rupiah terdekat (`round()`).
   - Format "Rp" hanya diterapkan pada layer presentasi (Blade views/UI).

2. **Strict Service Layer**:
   - Controller dirancang setipis mungkin (hanya menerima request, validasi FormRequest, delegasi ke Service, dan mengembalikan response/redirect).
   - Seluruh mutasi stok dan kalkulasi keuangan wajib melalui Service Class: [`StockService`](file:///root/ERP/app/Services/StockService.php), [`PricingService`](file:///root/ERP/app/Services/PricingService.php), [`InvoiceService`](file:///root/ERP/app/Services/InvoiceService.php), [`PaymentService`](file:///root/ERP/app/Services/PaymentService.php), dan [`ReportService`](file:///root/ERP/app/Services/ReportService.php).

3. **Integritas Stok & Immutability**:
   - `parts.qty_on_hand` murni merupakan cache pembacaan yang selalu sinkron dengan `SUM(stock_movements.qty_change)`.
   - Tabel `stock_movements` bersifat *append-only* (tidak boleh ada `UPDATE` maupun `DELETE`).
   - Setiap mutasi stok dilakukan dalam transaksi database dengan `lockForUpdate()` pada record part terkait untuk mencegah *race condition*.

4. **Integritas Dokumen Keuangan (Invoice & Payment)**:
   - Invoice yang telah terbit (`issued`, `partially_paid`, `paid`) dan pembayaran yang telah masuk tidak boleh diedit atau dihapus.
   - Koreksi kesalahan invoice dilakukan lewat mekanisme `void` dengan alasan wajib (hanya oleh Admin) dan hanya diizinkan jika belum ada pembayaran valid.
   - Koreksi pembayaran dilakukan lewat mekanisme `void` pembayaran dengan alasan wajib (Admin), yang memicu rekalkulasi otomatis atas status invoice terkait.

5. **Lingkungan Pengujian**:
   - Menggunakan SQLite in-memory untuk tes otomatis (`phpunit` / `pest`) agar eksekusi cepat dan terisolasi.
   - Setiap pengujian aturan bisnis wajib memuat kode `BR-xx` pada nama pengujiannya.

---

## 2. Daftar Ambiguitas, Konflik, dan Celah Spesifikasi yang Ditemukan

Berikut adalah hasil telaah kritis terhadap dokumen [`PRD.md`](file:///root/ERP/PRD.md), [`BUSINESS_RULES.md`](file:///root/ERP/BUSINESS_RULES.md), [`DATA_MODEL.md`](file:///root/ERP/DATA_MODEL.md), [`SEED_SPEC.md`](file:///root/ERP/SEED_SPEC.md), dan [`AGENTS.md`](file:///root/ERP/AGENTS.md):

### A. Ketiadaan Berkas `docs/PROMPTS.md`
- **Temuan**: Pada [`AGENTS.md`](file:///root/ERP/AGENTS.md) baris 27 disebutkan: *"Kerjakan satu fase per permintaan (lihat `docs/PROMPTS.md`)"*. Namun berkas `docs/PROMPTS.md` tidak ditemukan di dalam workspace.
- **Dampak**: Urutan fase resmi belum didefinisikan secara tertulis dalam repository.
- **Rekomendasi**: Mengusulkan dan menyepakati urutan 7 fase pengerjaan (lihat Bagian 4).

### B. Posisi Direktori Dokumen Spesifikasi
- **Temuan**: Seluruh dokumen spesifikasi (`PRD.md`, `BUSINESS_RULES.md`, `DATA_MODEL.md`, `SEED_SPEC.md`) saat ini berada langsung di root direktori `/root/ERP/`, sedangkan di [`AGENTS.md`](file:///root/ERP/AGENTS.md) direferensikan berada di dalam subfolder `docs/`.
- **Rekomendasi**: Memindahkan file-file markdown tersebut ke dalam folder `docs/` atau membiarkan folder `docs/` berisi dokumentasi kelanjutan dan keputusan.

### C. Alur dan Entitas Penjualan Langsung Sparepart (*Counter Sale*)
- **Temuan**:
  - `PRD.md` Modul 6 menyatakan invoice dapat berasal dari *order servis* atau *penjualan langsung*.
  - `DATA_MODEL.md` mencatat kolom `invoices.source_type` bernilai `work_order` atau `counter_sale`.
  - `BUSINESS_RULES.md` BR-S5 menyatakan: *"Penjualan langsung → `SALE_OUT` saat invoice terbit. Void invoice penjualan langsung → `SALE_RETURN`"*.
  - Namun tidak ada tabel transaksi khusus `sales` / `counter_sales` di `DATA_MODEL.md`.
- **Analisis & Asumsi**: Transaksi penjualan langsung langsung menginstansiasi record `invoices` + `invoice_items` bertipe `counter_sale`, dengan `customer_id` diarahkan ke entitas default "Pelanggan Umum" (atau customer terdaftar jika ada), tanpa melalui tabel perantara.

### D. Mekanisme Penerimaan Pembelian (*Purchases*) & Hutang Supplier
- **Temuan**: `DATA_MODEL.md` memiliki tabel `purchases` dan `purchase_items`. `PRD.md` menegaskan *"hutang supplier di luar scope"*.
- **Analisis & Asumsi**: Setiap pencatatan pembelian (`purchases`) diasumsikan tunai dan langsung memicu mutasi stok bertipe `PURCHASE_IN` seketika serta memperbarui *weighted average cost* (BR-S7), tanpa ada status penerimaan barang parsial atau pencatatan hutang/termin supplier.

### E. Batasan Relasi 1 Pembayaran Terhadap Invoice
- **Temuan**: `DATA_MODEL.md` mendefinisikan kolom `payments.invoice_id`.
- **Analisis**: Pembayaran bersifat *strictly one-to-one* terhadap invoice (1 transaksi pembayaran mencicil/melunasi 1 invoice tertentu). Tidak ada mekanisme 1 bukti pembayaran transfer yang memotong beberapa invoice secara kolektif (*batch allocation*).

### F. Efek Soft Delete pada Master Customer dan Sparepart
- **Temuan**: `DATA_MODEL.md` mencantumkan `deleted_at` hanya pada `customers` dan `parts`.
- **Celah**: Jika part atau customer di-soft-delete, transaksi historis (Work Order lama, Invoice lama, Kartu Stok, Laporan Piutang) tetap harus dapat menampilkan nama/SKU mereka tanpa memicu error relasi null.
- **Rekomendasi**: Seluruh query laporan dan kartu stok wajib menggunakan eager loading dengan `withTrashed()` untuk relasi customer dan part.

### G. Mekanisme Penomoran Berurutan Anti-Race-Condition (`INV-YYMM-####` & `WO-YYMM-####`)
- **Temuan**: Aturan BR-I1 mensyaratkan nomor invoice berurutan per bulan tanpa celah akibat *race condition*.
- **Rekomendasi**: Menggunakan tabel counter khusus (misal `document_counters` atau locking baris konfigurasi) dengan query `lockForUpdate()` dalam transaksi pembentukan nomor, agar aman saat ada dua user yang menerbitkan invoice/WO secara simultan.

### H. Alasan Override Harga Jual Lebih Rendah dari HPP (BR-P4)
- **Temuan**: BR-P4 melarang harga jual < average cost, namun admin boleh override dengan alasan yang tercatat di `audit_logs`.
- **Rekomendasi**: Form input item pada WO atau Counter Sale menyediakan prompt/kolom konfirmasi alasan jika terdeteksi `sell_price < avg_cost`, yang hanya dapat disetujui jika user memiliki role `admin`.

---

## 3. Pertanyaan Terbuka untuk Pemilik Proyek

1. **Konfirmasi Urutan Fase**: Apakah rancangan 7 fase pengerjaan modular di bawah ini sudah sesuai dengan ekspektasi Anda untuk menggantikan `docs/PROMPTS.md` yang belum ada?
2. **Struktur Folder Dokumentasi**: Apakah file `PRD.md`, `BUSINESS_RULES.md`, `DATA_MODEL.md`, dan `SEED_SPEC.md` sebaiknya kita pindahkan ke dalam folder `docs/` agar rapi dan sesuai dengan teks di `AGENTS.md`?
3. **Database Dev Lokal**: Apakah Anda ingin instalasi lokal menggunakan **SQLite** (langsung jalan via file `database.sqlite`) atau disiapkan **MySQL / PostgreSQL**? (Catatan: pengujian otomatis tetap SQLite in-memory).
4. **Library UI Interaktif**: Untuk form dinamis penambahan item sparepart & jasa pada Work Order, apakah disetujui menggunakan **Blade + Alpine.js** (ringan dan tanpa server state ekstra) atau prefer **Livewire**?

---

## 4. Usulan Urutan Fase Pengerjaan (Pengganti `docs/PROMPTS.md`)

Karena berkas `docs/PROMPTS.md` belum tersedia, berikut urutan fase terstruktur yang diusulkan untuk dieksekusi satu per satu sesuai aturan `AGENTS.md`:

- **Fase 1**: Setup Proyek Laravel, Konfigurasi Dasar (`Asia/Jakarta`, format tanggal, banner DEMO), Database Baseline, dan CRUD Master Data (`users`, `customers`, `services`, `customer_service_rates`, `suppliers`, `parts`, `equipment_units`, `settings`, `audit_logs`).
- **Fase 2**: Modul Pembelian Supplier (`purchases`) dan [`StockService`](file:///root/ERP/app/Services/StockService.php) (Kartu Stok, weighted average cost calculation BR-S7, penyesuaian stok opname BR-S6, concurrency row lock BR-S8).
- **Fase 3**: Modul Order Servis (Work Orders), Responsivitas Mobile Mekanik, dan [`PricingService`](file:///root/ERP/app/Services/PricingService.php) (Penentuan tarif khusus jasa BR-P1, snapshot harga BR-P2/P3, alokasi stok instan `SERVICE_OUT`/`SERVICE_RETURN` BR-S4).
- **Fase 4**: Modul Penagihan ([`InvoiceService`](file:///root/ERP/app/Services/InvoiceService.php)) & Pembayaran ([`PaymentService`](file:///root/ERP/app/Services/PaymentService.php)) (Penjualan langsung counter sale BR-S5, nomor urut bebas race gap BR-I1, cicilan & overpayment check BR-Y1, void invoice/pembayaran BR-I5/I6/Y2).
- **Fase 5**: Pengeluaran Operasional (`expenses`), Laporan Keuangan ([`ReportService`](file:///root/ERP/app/Services/ReportService.php)), dan Dashboard Interaktif (Omzet, HPP part, Laba Rugi sederhana, Arus Kas, Aging Piutang BR-R1 s/d BR-R8).
- **Fase 6**: Seeding Deterministik 12 Bulan (`php artisan seed:demo --fresh`) via Service Layer Resmi dan Verifikasi 6 Invarian Bisnis ([`SEED_SPEC.md`](file:///root/ERP/SEED_SPEC.md)).
- **Fase 7**: Pengujian Lengkap Skrip Demo 2 Menit, Audit Query Bebas N+1, dan Dokumentasi Akhir (`README.md`).
