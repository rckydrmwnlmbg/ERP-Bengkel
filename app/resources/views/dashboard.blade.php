<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <span class="text-sm text-slate-500 font-medium">
                {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Status Fase 1 Banner -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-xl p-5 border border-slate-700 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase tracking-wider">
                    ✓ Fase 1 Selesai — Fondasi &amp; RBAC
                </div>
                <h3 class="text-lg font-bold text-white">Fondasi Sistem ERP Berhasil Diterapkan</h3>
                <p class="text-xs text-slate-400 max-w-2xl">
                    17 tabel migrasi database, autentikasi 3 peran (Admin, Kasir, Mekanik), layout responsif, dan 37 tes otomatis seluruhnya telah lulus. Menu modul bisnis di sidebar menampilkan informasi fase saat diklik.
                </p>
            </div>
            <div class="shrink-0 bg-slate-950/60 border border-slate-700/60 px-4 py-2.5 rounded-xl text-center">
                <div class="text-xs text-slate-400 font-semibold uppercase">Langkah Selanjutnya</div>
                <div class="text-sm font-extrabold text-amber-400">Fase 2: Master Data</div>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
            <div class="p-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">
                            Selamat Datang, {{ Auth::user()->name }}!
                        </h3>
                        <p class="text-sm text-slate-600 mt-1">
                            Anda masuk sebagai <span class="font-semibold text-slate-900 uppercase">
                                {{ Auth::user()->role === 'admin' ? 'Administrator' : (Auth::user()->role === 'cashier' ? 'Kasir' : 'Mekanik') }}
                            </span>.
                        </p>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                            @if(Auth::user()->role === 'admin') bg-red-100 text-red-800 border border-red-200
                            @elseif(Auth::user()->role === 'cashier') bg-blue-100 text-blue-800 border border-blue-200
                            @else bg-emerald-100 text-emerald-800 border border-emerald-200 @endif">
                            Peran Aktif: {{ strtoupper(Auth::user()->role) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Permissions Matrix Card (PRD Access Summary) -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-100">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-700">
                    Ringkasan Hak Akses Anda (Berdasarkan docs/PRD.md)
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Master Data -->
                    <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Master Data</div>
                        <div class="mt-2 text-sm font-bold text-slate-800">
                            @if(Auth::user()->role === 'admin')
                                CRUD Lengkap
                            @elseif(Auth::user()->role === 'cashier')
                                Lihat &amp; Tambah Customer
                            @else
                                Lihat Saja
                            @endif
                        </div>
                    </div>

                    <!-- Order Servis -->
                    <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Order Servis</div>
                        <div class="mt-2 text-sm font-bold text-slate-800">
                            @if(Auth::user()->role === 'admin')
                                Semua Operasi
                            @elseif(Auth::user()->role === 'cashier')
                                Lihat Saja
                            @else
                                Buat, Isi Item, Ubah Status
                            @endif
                        </div>
                    </div>

                    <!-- Penjualan & Invoice -->
                    <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                        <div class="text-xs font-semibold text-slate-500 uppercase">Invoice &amp; Pembayaran</div>
                        <div class="mt-2 text-sm font-bold text-slate-800">
                            @if(Auth::user()->role === 'admin')
                                Ya, Termasuk Void
                            @elseif(Auth::user()->role === 'cashier')
                                Buat &amp; Bayar (Tanpa Void)
                            @else
                                Tidak Memiliki Akses
                            @endif
                        </div>
                    </div>

                    <!-- Harga Pokok / Laporan -->
                    <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                        <div class="text-xs font-semibold text-slate-500 uppercase">HPP, Laba &amp; Laporan</div>
                        <div class="mt-2 text-sm font-bold text-slate-800">
                            @if(Auth::user()->role === 'admin')
                                Akses Penuh (HPP, Laba, Audit)
                            @elseif(Auth::user()->role === 'cashier')
                                Laporan Piutang Saja
                            @else
                                Tidak Memiliki Akses
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roadmap Pengembangan ERP (docs/PROMPTS.md) -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h4 class="text-sm font-bold uppercase tracking-wider text-slate-700">
                    Roadmap Pengembangan Bertahap (Sesuai docs/AGENTS.md &amp; PROMPTS.md)
                </h4>
                <span class="text-xs font-medium text-slate-500">1 Fase per Permintaan</span>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <!-- Fase 1 -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/50">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-sm shrink-0">
                                1
                            </span>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Fase 1: Fondasi &amp; RBAC 3 Peran</div>
                                <div class="text-xs text-slate-600">Skeleton, 17 Migrasi, Aturan Rupiah Integer, Zona Waktu Jakarta, 37 Tes Lulus.</div>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            ✓ Selesai
                        </span>
                    </div>

                    <!-- Fase 2 -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border border-amber-300 bg-amber-50/50">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg bg-amber-500 text-slate-950 font-bold flex items-center justify-center text-sm shrink-0">
                                2
                            </span>
                            <div>
                                <div class="text-sm font-bold text-slate-900">Fase 2: Master Data &amp; Unit Alat Berat</div>
                                <div class="text-xs text-slate-600">CRUD Customer, Unit Alat Berat, Sparepart, Jasa, Supplier, Soft Delete, dan Sembunyikan HPP.</div>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-400 animate-pulse">
                            Siap Dikerjakan
                        </span>
                    </div>

                    <!-- Fase 3 -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-sm shrink-0">
                                3
                            </span>
                            <div>
                                <div class="text-sm font-bold text-slate-800">Fase 3: Mesin Stok (WAC &amp; Barang Masuk)</div>
                                <div class="text-xs text-slate-500">StockService, Weighted Average Cost, Mutasi Stok, Row Lock &amp; Transaksi DB.</div>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                            Menunggu Fase 2
                        </span>
                    </div>

                    <!-- Fase 4 -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-sm shrink-0">
                                4
                            </span>
                            <div>
                                <div class="text-sm font-bold text-slate-800">Fase 4: Order Servis (Work Order)</div>
                                <div class="text-xs text-slate-500">Alur status WO, tarif khusus customer, diagnosa, dan integrasi stok part.</div>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                            Menunggu Fase 3
                        </span>
                    </div>

                    <!-- Fase 5 & Seterusnya -->
                    <div class="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-sm shrink-0">
                                5-8
                            </span>
                            <div>
                                <div class="text-sm font-bold text-slate-800">Fase 5 - 8: Invoice, Laporan, Dummy Data &amp; Audit Log</div>
                                <div class="text-xs text-slate-500">Invoicing, Pembayaran cicilan, Laba Rugi, Aging Piutang, Seeding Demo, dan Finalisasi.</div>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                            Menunggu
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
