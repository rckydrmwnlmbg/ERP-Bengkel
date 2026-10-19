# Prompt Bertahap untuk Antigravity

Cara pakai: salin `AGENTS.md` ke root repo, folder `docs/` ke repo. Tempel **satu prompt per sesi**, periksa hasilnya, baru lanjut. Jangan gabungkan fase.
Jika agent mengaku selesai, jalankan sendiri tes dan buka UI-nya. Jangan percaya laporan agent tanpa bukti.

---

## Prompt 0 — Rencana (tanpa kode)
```
Baca AGENTS.md dan semua file di docs/. Jangan menulis kode.
Tugasmu: (1) ringkas pemahamanmu tentang sistem dalam 10 kalimat, (2) daftar semua ambiguitas, konflik, atau celah yang kamu temukan di dokumen, (3) usulkan struktur folder proyek dan daftar service class, (4) konfirmasi urutan fase dari docs/PROMPTS.md.
Tulis pertanyaan dan keputusan di docs/DECISIONS.md. Berhenti dan tunggu jawabanku.
```

## Fase 1 — Fondasi
```
Kerjakan Fase 1 saja. Buat proyek sesuai stack di AGENTS.md, semua migrasi sesuai docs/DATA_MODEL.md (termasuk constraint dan indeks), autentikasi dengan 3 peran sesuai tabel hak akses di docs/PRD.md, layout dasar (sidebar, banner "DEMO"), dan test harness. Tambahkan tes untuk hak akses per peran.
Jangan membuat modul bisnis. Jalankan semua tes dan tampilkan output asli. Akhiri dengan ringkasan, asumsi, dan yang belum selesai.
```

## Fase 2 — Master data
```
Kerjakan Fase 2 saja: CRUD customer (termasuk tarif jasa khusus per customer dan unit alat berat), sparepart, jasa, supplier. Ikuti hak akses PRD. Mekanik dan kasir tidak boleh melihat harga beli/avg_cost. Soft delete untuk master data yang ditandai. Validasi server lewat FormRequest, audit log untuk perubahan harga.
Tanpa pergerakan stok dulu (qty awal harus lewat modul stok di Fase 3). Tes untuk validasi dan hak akses. Jalankan semua tes, tampilkan output asli.
```

## Fase 3 — Mesin stok
```
Kerjakan Fase 3 saja: StockService dan modul barang masuk (purchases), penyesuaian stok, serta halaman kartu stok. Terapkan BR-S1 sampai BR-S8 persis.
Wajib ada tes bernama dengan ID: T-01, T-02, T-08, T-09 dari docs/BUSINESS_RULES.md, plus satu tes per BR-S. Tes T-08 harus benar-benar menguji akses paralel (row lock), bukan hanya urutan.
Beri komentar "mengapa" pada rumus avg cost dan locking. Jalankan semua tes, tampilkan output asli.
```

## Fase 4 — Order servis
```
Kerjakan Fase 4 saja: order servis dengan alur status BR-W1..W4, tambah/hapus item sparepart dan jasa, PricingService (BR-P1..P4, tarif khusus customer, snapshot harga). Sparepart ditambahkan memanggil StockService (BR-S4).
UI harus nyaman di layar ponsel untuk mekanik. Tes: T-02, T-03 (bagian harga dan stok), perubahan tarif tidak mengubah item lama, status transition yang ilegal ditolak. Jalankan semua tes, tampilkan output asli.
```

## Fase 5 — Invoice dan pembayaran
```
Kerjakan Fase 5 saja: InvoiceService dan PaymentService, penjualan langsung, void invoice dan void pembayaran, cetak/PDF invoice, halaman piutang per customer. Terapkan BR-I1..I8 dan BR-Y1..Y3.
Pajak default 0 dan dibaca dari settings; jangan hardcode tarif.
Tes wajib: T-03, T-04, T-05, T-06 dengan angka persis di docs/BUSINESS_RULES.md, plus nomor invoice tidak duplikat saat paralel. Jalankan semua tes, tampilkan output asli.
```

## Fase 6 — Laporan dan dashboard
```
Kerjakan Fase 6 saja: pengeluaran operasional, ReportService (BR-R1..R8), laporan omzet, laba rugi sederhana, arus kas, piutang dengan aging, dashboard, filter periode, ekspor Excel dan PDF. Tampilkan catatan penyederhanaan (BR-R2, BR-R5) di UI laporan.
Tes wajib: T-07 dan tes konsistensi BR-R8. Hitung nilai harapan di tes secara independen dari kode produksi (jangan memanggil ReportService untuk membuat nilai harapan). Jalankan semua tes, tampilkan output asli.
```

## Fase 7 — Data dummy
```
Kerjakan Fase 7 saja: perintah seed:demo sesuai docs/SEED_SPEC.md. Gunakan service yang sama dengan aplikasi, bukan insert langsung. Implementasikan semua invarian sebagai assertion di akhir seeding dan satu tes yang menjalankan seeding lalu memeriksa invarian. Cetak ringkasan angka di akhir (omzet, HPP, laba kotor, piutang, jumlah invoice per status).
Jalankan seeding dua kali dan buktikan hasilnya identik. Tampilkan output asli.
```

## Fase 8 — Pengerasan dan portofolio
```
Kerjakan Fase 8 saja: audit log lengkap dan halaman lihatnya, cek N+1 pada daftar utama, perbaiki pesan error agar ramah pengguna, README (cara setup, kredensial demo, skrip demo 2 menit dari PRD, daftar keputusan dari DECISIONS.md, daftar penyederhanaan yang disengaja), dan pastikan banner DEMO terlihat di semua halaman. Siapkan konfigurasi deploy sederhana.
Jalankan semua tes dan tampilkan output asli.
```

---

## Prompt peninjauan (pakai setelah tiap fase, sebelum lanjut)
```
Jelaskan kepadaku, seolah aku harus mempertahankan kode ini di depan klien: bagaimana [StockService / hitungan sisa piutang / pembuatan nomor invoice] bekerja, langkah demi langkah, dengan nama file dan fungsi. Tunjukkan di mana aturan BR-xx diterapkan. Sebutkan 3 kasus tepi yang paling mungkin menyebabkan bug dan apakah sudah ada tesnya.
```

## Prompt uji serang (pakai setelah Fase 5 dan Fase 7)
```
Bertindaklah sebagai penguji yang mencoba merusak sistem ini. Tulis tes tambahan untuk: stok minus lewat request paralel, bayar melebihi sisa, void invoice yang sudah dibayar, ubah harga master setelah invoice terbit, order dibatalkan setelah invoice, akses fitur oleh peran yang tidak berhak (mekanik melihat HPP), dan angka laporan tidak cocok dengan hitungan manual. Jalankan, laporkan mana yang gagal, dan jangan memperbaiki apa pun sebelum aku setuju.
```
