# PRD — Demo ERP Bengkel Alat Berat & Toko Sparepart

## Tujuan
Demo portofolio untuk satu bengkel alat berat yang juga punya toko sparepart. Semua data fiktif dan harus ditandai "DEMO" di UI (banner + footer).
Kriteria sukses: satu alur dari servis sampai invoice, pembayaran, stok, dan laporan, dengan angka yang konsisten dan teruji.

## Peran dan hak akses
| Fitur | Admin | Kasir | Mekanik |
|---|---|---|---|
| Master data (customer, part, jasa, supplier) | CRUD | Lihat, tambah customer | Lihat |
| Harga beli / HPP / laba | Lihat | Tidak | Tidak |
| Order servis | Semua | Lihat | Buat, isi item, ubah status |
| Penjualan sparepart langsung | Ya | Ya | Tidak |
| Invoice dan pembayaran | Ya, termasuk void | Buat, bayar | Tidak |
| Barang masuk, penyesuaian stok | Ya | Tidak | Tidak |
| Pengeluaran | Ya | Tidak | Tidak |
| Laporan, audit log, pengaturan | Ya | Piutang saja | Tidak |

## Modul (in scope)
1. **Customer**: data, tarif jasa khusus per customer, termin bayar (hari), unit alat berat milik customer, riwayat transaksi, total piutang.
2. **Sparepart**: SKU, kategori, satuan, harga jual, harga pokok rata-rata, stok, stok minimum, kartu stok (riwayat pergerakan).
3. **Jasa**: nama, harga standar.
4. **Supplier dan barang masuk**: catat pembelian, menambah stok dan memperbarui harga pokok rata-rata.
5. **Order servis**: unit, keluhan, diagnosa, item sparepart dan jasa, status (`open`, `in_progress`, `completed`, `invoiced`, `cancelled`).
6. **Invoice**: dari order servis atau penjualan langsung; nomor berurutan; diskon opsional; cetak/PDF.
7. **Pembayaran**: tunai/transfer, bisa dicicil, riwayat per invoice dan per customer.
8. **Pengeluaran operasional**: tanggal, kategori, jumlah.
9. **Laporan**: omzet, laba rugi sederhana, arus kas, piutang dengan aging; filter periode; ekspor Excel/PDF.
10. **Dashboard**: omzet bulan ini, piutang berjalan dan jatuh tempo, stok menipis, 5 order servis terbaru.
11. **Audit log** dan **pengaturan** (nama bengkel, tarif pajak default 0).

## Di luar scope (jangan dibuat)
Multi-cabang, hutang supplier, akuntansi double-entry/jurnal, payroll, integrasi pajak (e-Faktur), integrasi WhatsApp/pembayaran online, aplikasi mobile, approval berlapis, retur ke supplier, multi-gudang, multi-mata uang.

## Skrip demo (alur yang harus mulus, ±2 menit)
1. Login admin, lihat dashboard.
2. Buat order servis untuk customer bertarif khusus: pilih unit, isi keluhan.
3. Tambah sparepart (stok berkurang) dan jasa (harga mengikuti tarif customer).
4. Selesaikan order, terbitkan invoice.
5. Bayar sebagian, lihat status "dibayar sebagian" dan sisa piutang.
6. Buka kartu stok part tadi, lihat pergerakan.
7. Buka laporan piutang dan laba rugi bulan ini.

## Non-fungsional
- Halaman utama < 2 detik dengan data seed.
- Responsif di layar ponsel untuk alur mekanik (order servis).
- Tidak ada N+1 query pada daftar utama.
