# AGENTS.md — Aturan untuk agent

Proyek: **demo ERP Bengkel Alat Berat & Toko Sparepart** (portofolio, semua data fiktif).

## Sumber kebenaran
Baca sebelum menulis kode: `docs/PRD.md`, `docs/BUSINESS_RULES.md`, `docs/DATA_MODEL.md`, `docs/SEED_SPEC.md`.
Jika dokumen ambigu, saling bertentangan, atau menurutmu salah: **jangan menebak**. Catat di `docs/DECISIONS.md` dan tanyakan.

## Stack (default, ubah di sini bila perlu)
- Laravel versi stabil terbaru (PHP), MySQL atau PostgreSQL untuk jalan normal, SQLite in-memory untuk tes.
- Blade + Tailwind, JavaScript seminimal mungkin (Alpine/Livewire boleh).
- Tes: Pest atau PHPUnit.

## Aturan kode
1. **Uang = integer rupiah (BIGINT).** Dilarang float/decimal untuk uang. Format "Rp" hanya di tampilan.
2. **Logika bisnis hanya di service class** (`app/Services`): `StockService`, `PricingService`, `InvoiceService`, `PaymentService`, `ReportService`. Controller tipis.
3. **Stok hanya boleh berubah lewat `StockService`**, yang menulis `stock_movements`. Tidak ada `UPDATE parts SET qty_on_hand` di tempat lain.
4. Operasi stok dan keuangan di dalam DB transaction dengan `lockForUpdate()` pada baris terkait.
5. Invoice yang terbit dan pembayaran **tidak boleh diedit atau dihapus**. Koreksi lewat void + alasan wajib.
6. Setiap perubahan penting (stok, harga, invoice, pembayaran, void, pengeluaran) menulis `audit_logs`.
7. Validasi di server (FormRequest). Pasang FK, unique, dan check constraint di database, jangan hanya di aplikasi.
8. UI Bahasa Indonesia, tanggal `dd-mm-yyyy`, zona waktu `Asia/Jakarta`. Nama variabel/tabel/kelas Bahasa Inggris.
9. Komentar kode menjelaskan **mengapa**, terutama di service stok dan keuangan, supaya pemilik proyek bisa memahaminya.
10. Tanpa dependency baru kecuali ada alasan tertulis di `docs/DECISIONS.md`. Tanpa kredensial di repo (`.env.example` saja).

## Cara kerja
- Kerjakan **satu fase per permintaan** (lihat `docs/PROMPTS.md`). Jangan mengerjakan fase berikutnya sendiri.
- Sebelum coding: tulis rencana singkat dan daftar file yang akan dibuat/diubah.
- Setiap aturan `BR-xx` harus punya tes yang memuat ID-nya di nama tes.
- Setelah selesai: jalankan seluruh tes dan tampilkan **output asli**. Jangan menyatakan lulus tanpa menjalankannya.
- Akhiri dengan: ringkasan perubahan, asumsi yang kamu ambil, dan hal yang belum selesai.
- Jangan menambah fitur di luar PRD (lihat bagian "Di luar scope").

## Definition of done per fase
- Migrasi, kode, dan tes selesai; semua tes lulus.
- Tidak ada aturan di `AGENTS.md` yang dilanggar.
- Fitur bisa dicoba lewat UI untuk fase yang punya UI.
- `README.md` diperbarui jika ada langkah setup baru.
