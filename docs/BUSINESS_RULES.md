# Aturan Bisnis (BR)

Semua uang integer rupiah. Pembulatan: half-up ke rupiah terdekat. Setiap BR wajib punya tes.

## Stok (BR-S)
- **BR-S1** `parts.qty_on_hand` adalah cache. Nilainya harus selalu sama dengan `SUM(stock_movements.qty_change)` per part.
- **BR-S2** Stok tidak boleh minus. Permintaan yang membuat stok < 0 ditolak dengan pesan jelas.
- **BR-S3** Jenis pergerakan: `PURCHASE_IN`, `SERVICE_OUT`, `SERVICE_RETURN`, `SALE_OUT`, `SALE_RETURN`, `ADJUSTMENT_IN`, `ADJUSTMENT_OUT`. `stock_movements` tidak boleh diubah atau dihapus.
- **BR-S4** Sparepart ditambahkan ke order servis → `SERVICE_OUT` saat itu juga. Dihapus dari order atau order dibatalkan → `SERVICE_RETURN`.
- **BR-S5** Penjualan langsung → `SALE_OUT` saat invoice terbit. Void invoice penjualan langsung → `SALE_RETURN`.
- **BR-S6** Penyesuaian stok wajib alasan dan hanya admin.
- **BR-S7** Harga pokok rata-rata tertimbang diperbarui hanya saat stok bertambah:
  `avg_baru = round((qty_lama × avg_lama + qty_masuk × biaya_masuk) / (qty_lama + qty_masuk))`.
  Stok keluar tidak mengubah avg. Retur masuk memakai `unit_cost_snapshot` pergerakan asalnya sebagai biaya_masuk.
- **BR-S8** Setiap pergerakan keluar menyimpan `unit_cost_snapshot` = avg saat itu. Dua pengguna yang mengambil stok terakhir bersamaan: salah satu harus ditolak (gunakan row lock).

## Harga (BR-P)
- **BR-P1** Harga jasa pada order = tarif khusus customer (`customer_service_rates`) bila ada, jika tidak `services.default_price`.
- **BR-P2** Harga ditulis ke baris item sebagai snapshot (`unit_price`). Perubahan harga master atau tarif **tidak** mengubah item yang sudah ada.
- **BR-P3** Harga sparepart = `parts.sell_price` saat item dibuat (snapshot). Tidak ada harga khusus customer untuk sparepart.
- **BR-P4** Harga jual tidak boleh lebih rendah dari harga pokok rata-rata saat dibuat; admin boleh override dengan alasan (tercatat di audit log).

## Order servis (BR-W)
- **BR-W1** Alur status: `open → in_progress → completed → invoiced`. `cancelled` hanya dari `open`/`in_progress`/`completed` selama belum `invoiced`.
- **BR-W2** Item hanya bisa ditambah/diubah/dihapus saat status `open` atau `in_progress`.
- **BR-W3** Order `invoiced` tidak bisa diubah. Satu order hanya boleh punya satu invoice aktif (non-void).
- **BR-W4** Order tanpa item tidak bisa diselesaikan.

## Invoice (BR-I)
- **BR-I1** Nomor `INV-YYMM-####`, berurutan per bulan, unik, dibuat dalam transaction tanpa celah akibat race.
- **BR-I2** `subtotal = Σ line_total`; `line_total = qty × unit_price`; `total = subtotal − discount_amount + tax_amount`.
- **BR-I3** `0 ≤ discount_amount ≤ subtotal`. Pajak: `tax_amount = round((subtotal − discount) × tax_rate%)`, default tarif 0. Tarif pajak adalah pengaturan, jangan di-hardcode, dan perlu diverifikasi terhadap aturan pajak yang berlaku sebelum diaktifkan.
- **BR-I4** Invoice dari order servis menyalin item order (termasuk `unit_cost_snapshot`) tanpa membuat pergerakan stok baru.
- **BR-I5** Invoice terbit tidak bisa diedit. Koreksi = void (alasan wajib, hanya admin).
- **BR-I6** Void hanya jika tidak ada pembayaran valid. Void invoice order servis → order kembali `completed`. Void penjualan langsung → BR-S5.
- **BR-I7** `due_date = issue_date + customer.payment_term_days`.
- **BR-I8** Status turunan dari pembayaran valid: 0 → `issued`; 0 < dibayar < total → `partially_paid`; dibayar = total → `paid`. Invoice `total = 0` tidak diperbolehkan.

## Pembayaran (BR-Y)
- **BR-Y1** `amount > 0` dan `amount ≤ sisa tagihan`. Kelebihan bayar ditolak.
- **BR-Y2** Pembayaran tidak diedit/dihapus. Admin dapat void pembayaran dengan alasan; status invoice dihitung ulang.
- **BR-Y3** Tidak ada pembayaran untuk invoice `void`.

## Laporan (BR-R)
Rentang tanggal inklusif. Invoice `void` dan pembayaran `void` dikecualikan dari semua laporan.
- **BR-R1 Omzet** = Σ `invoices.total` dengan `issue_date` dalam periode.
- **BR-R2 HPP** = Σ (`qty × unit_cost_snapshot`) pada item bertipe `part` dari invoice dalam periode. Jasa dianggap biaya 0 (penyederhanaan demo, tulis di UI laporan).
- **BR-R3 Laba kotor** = Omzet − HPP. **Laba bersih sederhana** = Laba kotor − Σ `expenses.amount` dalam periode.
- **BR-R4 Arus kas masuk** = Σ pembayaran valid dengan `paid_at` dalam periode.
- **BR-R5 Arus kas keluar** = Σ `purchases.total` (dianggap tunai di tanggal pembelian) + Σ `expenses.amount` dalam periode. Hutang supplier di luar scope; tulis penyederhanaan ini di UI.
- **BR-R6 Piutang** per customer = Σ (`total` − pembayaran valid) untuk invoice non-void dengan sisa > 0, per tanggal laporan.
- **BR-R7 Aging** berdasarkan `hari_lewat = tanggal_laporan − due_date`: belum jatuh tempo (≤0), 1–30, 31–60, 61–90, >90 hari.
- **BR-R8** Total di dashboard, laporan, dan halaman customer untuk periode/customer yang sama harus identik.

## Contoh hitungan untuk tes (nilai pasti)

**T-01 (BR-S7)** Part "Filter Oli": stok 10, avg Rp100.000. Barang masuk 10 @ Rp120.000 → stok 20, avg Rp110.000. Barang masuk 1 @ Rp95.000 → avg = round((20×110.000 + 95.000)/21) = Rp109.286.

**T-02 (BR-S2)** Stok 3, tambahkan 4 ke order → ditolak, stok tetap 3, tidak ada movement baru.

**T-03 (BR-P1, P2, I2, R1-R3)** Stok Filter Oli 10, avg Rp100.000, harga jual Rp150.000. Jasa "Ganti Oli" standar Rp200.000, tarif PT Maju Rp175.000.
Order PT Maju: 2 × Filter Oli + 1 × Ganti Oli.
- Item: 2×150.000 = 300.000; jasa 175.000 → subtotal = total = **475.000** (diskon 0, pajak 0).
- Stok setelah order = **8**. HPP = 2×100.000 = **200.000**. Laba kotor = **275.000**.
- Ubah tarif Ganti Oli PT Maju jadi 190.000 setelah invoice → invoice tetap 475.000 (BR-P2).

**T-04 (BR-Y1, I8)** Lanjutan T-03: bayar 300.000 → `partially_paid`, sisa **175.000**. Bayar 200.000 → ditolak (melebihi sisa). Bayar 175.000 → `paid`, sisa 0.

**T-05 (BR-I6, Y2)** Void invoice yang punya pembayaran valid → ditolak. Void pembayaran 300.000 (admin, alasan) → invoice kembali `partially_paid`/`issued` sesuai sisa.

**T-06 (BR-S5)** Penjualan langsung 3 × Filter Oli @150.000 dengan diskon 20.000 → total **430.000**, stok −3, HPP 3×avg. Void → stok +3 (`SALE_RETURN`).

**T-07 (BR-R7)** Invoice jatuh tempo 40 hari lalu, sisa 500.000 → masuk bucket 31–60.

**T-08 (BR-S8)** Dua request paralel mengambil stok terakhir (stok 1): satu sukses, satu ditolak, stok akhir 0.

**T-09 (BR-S1)** Setelah rangkaian operasi acak 200 langkah (masuk, keluar, retur), `qty_on_hand` = Σ movements untuk setiap part.

**T-10 (BR-R8)** Setelah seed, total piutang di dashboard = total di laporan piutang = Σ piutang per customer.
