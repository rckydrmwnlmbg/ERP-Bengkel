# Spesifikasi Data Dummy

Tujuan: data yang membuat laporan terlihat hidup **dan** konsisten. Semua fiktif; jangan memakai nama perusahaan nyata untuk customer/supplier. Merek alat berat (Komatsu, Hitachi, Caterpillar, dll.) boleh dipakai sebagai nama merek unit. Jangan mengklaim nomor part OEM asli; pakai SKU internal `SP-0001` dst.

## Mekanisme
- Perintah: `php artisan seed:demo` (atau padanan di stack). Bisa dijalankan ulang; selalu mulai dari database bersih (`--fresh`).
- **Deterministik**: random seed tetap, hasil sama setiap kali.
- Rentang tanggal: 12 bulan terakhir dari tanggal jalan, dengan tren omzet naik bertahap dan sedikit musiman.
- Semua data dibuat lewat service yang sama dengan aplikasi (`StockService`, `InvoiceService`, `PaymentService`), **bukan insert langsung**, agar semua aturan BR ikut berlaku.

## Volume
- Users: 1 admin, 1 kasir, 2 mekanik (kredensial demo di README, kata sandi sederhana khusus demo).
- Customer: 10 (+ "Pelanggan Umum"): 5 perusahaan kontraktor/tambang/logistik fiktif, 3 pemilik unit perorangan, 2 lain. Termin bayar bervariasi: 0, 14, 30, 45 hari. **3 customer punya tarif jasa khusus** untuk 3-5 jasa.
- Unit alat berat: ±18 (excavator, bulldozer, wheel loader, dump truck, grader) dengan merek/model realistis dan hour meter masuk akal.
- Jasa: ±14 (ganti oli mesin, ganti filter, service berkala 250/500/1000 jam, perbaikan hidrolik, overhaul pompa, las bucket, ganti track, kalibrasi, jasa kunjungan lapangan, dll.) dengan harga standar Rp150.000 – Rp15.000.000 sesuai kompleksitas.
- Sparepart: ±45 di kategori Filter, Oli & Pelumas, Hidrolik, Undercarriage, Engine, Kelistrikan, Ground Engaging Tools, Seal & Gasket. Harga jual Rp25.000 – Rp8.000.000.
- Supplier: 5 fiktif.
- Order servis: ±110, penjualan langsung: ±60, pengeluaran: ±40 (sewa, listrik, gaji harian, transport, dll.).

## Invarian (wajib, di-assert di akhir seeding; gagal = seeding gagal)
1. Tidak ada stok minus; `qty_on_hand` = Σ movements untuk setiap part (BR-S1).
2. `harga_jual > avg_cost` untuk semua part dengan margin acak 15% – 45%.
3. Setiap invoice: total = Σ item − diskon + pajak; Σ pembayaran valid ≤ total.
4. Tidak ada pembayaran pada invoice void; tidak ada invoice total ≤ 0.
5. Total piutang di semua cara hitung identik (BR-R8).
6. Tanggal pembayaran ≥ tanggal invoice; tanggal invoice ≥ tanggal order dibuka.

## Variasi kasus yang harus ada (untuk menguji laporan)
- Invoice: ±70% lunas, ±15% dibayar sebagian, ±10% belum dibayar, 3 void.
- Piutang tersebar di semua bucket aging; **1 customer dengan tunggakan besar** (>90 hari), 2 customer dengan piutang jatuh tempo ringan.
- Order servis: 2 `open`, 2 `in_progress`, 3 `completed` (belum diinvoice), 2 `cancelled`; sisanya `invoiced`.
- Minimal 4 part dengan stok ≤ stok minimum (muncul di "stok menipis"), 1 part stok 0.
- Barang masuk beberapa kali dengan harga beli berbeda untuk ≥ 8 part (agar avg cost non-trivial).
- ≥ 3 invoice memakai diskon; ≥ 5 pembayaran cicilan (2-3 kali bayar) pada satu invoice.
- ≥ 2 penyesuaian stok dengan alasan (opname selisih).
- ≥ 1 order servis dengan jasa memakai tarif khusus customer, dan ≥ 1 customer lain dengan tarif standar untuk jasa yang sama (untuk perbandingan).
- Audit log terisi dari aksi-aksi di atas.

## Keluaran seeding
Cetak ringkasan: jumlah per tabel, omzet 12 bulan, HPP, laba kotor, total pengeluaran, total piutang, jumlah invoice per status. Angka ini dipakai sebagai acuan pengecekan manual terhadap layar laporan.
