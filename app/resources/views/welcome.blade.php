<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'TRAK ERP') }} — Sistem Bengkel Alat Berat &amp; Suku Cadang</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts (Tailwind CDN + Alpine JS) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-full flex flex-col font-sans bg-slate-950 text-slate-100 selection:bg-amber-500 selection:text-slate-950">

    <!-- Top Environment Bar -->
    @if(config('app.demo', true))
        <div class="bg-slate-900/90 backdrop-blur border-b border-slate-800 text-slate-300 py-2 px-4 text-xs font-medium sticky top-0 z-50">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 tracking-wider">
                        MODE DEMO
                    </span>
                    <span class="text-slate-400 text-xs hidden sm:inline">
                        Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart (Semua Data Fiktif)
                    </span>
                </div>
                <div class="flex items-center space-x-2 text-[11px] text-slate-400">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Sistem Aktif &bull; Asia/Jakarta</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Header -->
    <header class="border-b border-slate-800/80 bg-slate-950/60 backdrop-blur sticky top-8 z-40">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/20 to-amber-600/10 border border-amber-500/30 flex items-center justify-center p-2 shadow-inner">
                    <x-application-logo class="w-full h-full text-amber-500" />
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xl font-extrabold tracking-tight text-white">TRAK<span class="text-amber-500">ERP</span></span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">Enterprise</span>
                    </div>
                    <p class="text-[11px] font-medium text-slate-400">Bengkel Alat Berat &amp; Inventaris Suku Cadang</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-bold text-sm hover:from-amber-400 hover:to-amber-500 transition shadow-lg shadow-amber-500/10">
                        <span>Buka Dashboard</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-bold text-sm hover:from-amber-400 hover:to-amber-500 transition shadow-lg shadow-amber-500/10">
                        <span>Masuk ke Sistem</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        <!-- Hero Section -->
        <section class="relative max-w-7xl mx-auto px-6 pt-16 pb-12 sm:pt-24 sm:pb-16 text-center">
            <!-- Subtle Radial Ambient Glow -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] bg-amber-500/10 blur-[120px] rounded-full pointer-events-none -z-10"></div>

            <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-amber-400 text-xs font-semibold tracking-wide mb-6 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span>Solusi Operasional Bengkel Terintegrasi</span>
            </div>

            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight max-w-4xl mx-auto leading-tight">
                Kendali Operasional Bengkel &amp; Logistik Suku Cadang Presisi
            </h1>

            <p class="mt-6 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
                Manajemen siklus hidup Surat Perintah Kerja (SPK), penilaian persediaan metode Weighted Average Cost (WAC), penagihan bertahap, analisa umur piutang, serta pelaporan keuangan terpadu.
            </p>

            <div class="mt-8 flex justify-center items-center gap-4 flex-wrap">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-7 py-3 rounded-xl bg-amber-500 text-slate-950 font-bold text-sm hover:bg-amber-400 transition shadow-lg shadow-amber-500/20">
                        Akses Dashboard Operasional &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-7 py-3 rounded-xl bg-amber-500 text-slate-950 font-bold text-sm hover:bg-amber-400 transition shadow-lg shadow-amber-500/20">
                        Masuk Portal Operasional &rarr;
                    </a>
                @endauth
                <a href="#demo-accounts" class="px-6 py-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 font-semibold text-sm hover:bg-slate-800 hover:text-white transition">
                    Simulasi Akses Peran
                </a>
            </div>
        </section>

        <!-- Demo Accounts Grid -->
        <section id="demo-accounts" class="max-w-7xl mx-auto px-6 py-12 border-t border-slate-800/80">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-xs font-bold uppercase tracking-widest text-amber-500">Simulasi Akses Multi-Peran</h2>
                <h3 class="text-2xl font-bold text-white mt-1">Pilih Peran untuk Menguji Hak Akses</h3>
                <p class="text-xs text-slate-400 mt-2">
                    Sistem dilengkapi pemisahan wewenang ketat (Role-Based Access Control). Password akun demo: <code class="text-amber-400 bg-slate-900 px-2 py-0.5 rounded font-mono border border-slate-800">password</code>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <!-- Admin Card -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-slate-700 transition flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-11 h-11 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center p-2.5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/10 text-rose-300 border border-rose-500/30 uppercase tracking-wider">
                                Administrator
                            </span>
                        </div>
                        <h4 class="text-base font-bold text-white group-hover:text-amber-400 transition">Akses Penuh Manajemen</h4>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Otorisasi master data, audit nilai HPP &amp; margin keuntungan, input barang masuk supplier, penyesuaian opname stok, void transaksi, serta laporan audit log sistem.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-mono">admin@bengkel.com</span>
                            <a href="{{ route('login') }}" class="font-bold text-rose-400 hover:text-rose-300 flex items-center gap-1">
                                <span>Pilih</span> &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Cashier Card -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-slate-700 transition flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-11 h-11 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center p-2.5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-blue-500/10 text-blue-300 border border-blue-500/30 uppercase tracking-wider">
                                Kasir
                            </span>
                        </div>
                        <h4 class="text-base font-bold text-white group-hover:text-amber-400 transition">Kasir &amp; Piutang Dagang</h4>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Penerbitan faktur penjualan dari SPK selesai, transaksi penjualan langsung sparepart, pencatatan pembayaran cicilan/tunai, serta monitoring buku piutang pelanggan.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-mono">kasir@bengkel.com</span>
                            <a href="{{ route('login') }}" class="font-bold text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                <span>Pilih</span> &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mechanic Card -->
                <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-6 hover:border-slate-700 transition flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center p-2.5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 uppercase tracking-wider">
                                Mekanik
                            </span>
                        </div>
                        <h4 class="text-base font-bold text-white group-hover:text-amber-400 transition">Teknisi &amp; Pengerjaan Unit</h4>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Antarmuka responsif tablet/ponsel untuk registrasi unit alat berat, pendataan diagnosa keluhan, pengambilan suku cadang, dan update berkala tahapan pengerjaan.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-mono">mekanik1@bengkel.com</span>
                            <a href="{{ route('login') }}" class="font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
                                <span>Pilih</span> &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core Architectural Pillars -->
        <section class="max-w-7xl mx-auto px-6 py-12 border-t border-slate-800/80">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-xs font-bold uppercase tracking-widest text-amber-500">Standar Industri Alat Berat</h2>
                <h3 class="text-2xl font-bold text-white mt-1">Dirancang untuk Presisi Operasional</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- 1 -->
                <div class="p-5 rounded-xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-white">Alur SPK End-to-End</h4>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Penerimaan unit pelanggan, pencatatan keluhan, alokasi material suku cadang, pengerjaan teknisi hingga penutupan dan terbit invoice otomatis.
                    </p>
                </div>

                <!-- 2 -->
                <div class="p-5 rounded-xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-white">Metode Stok WAC</h4>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Weighted Average Cost dinamis saat barang masuk distributor, kartu stok buku besar mutasi immutable, serta validasi batas minimum persediaan.
                    </p>
                </div>

                <!-- 3 -->
                <div class="p-5 rounded-xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-white">Penagihan &amp; Aging</h4>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Faktur dengan termin bayar terstruktur, kalkulasi otomatis jatuh tempo, dukungan pembayaran cicilan, dan klasifikasi umur piutang komprehensif.
                    </p>
                </div>

                <!-- 4 -->
                <div class="p-5 rounded-xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition">
                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-white">Integritas Transaksi</h4>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Pemberian nomor berurutan tidak dapat dipakai ulang (non-reusable sequence), isolasi void transaksi, proteksi payload HPP, dan jejak audit aktivitas sensitif.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- Professional Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-slate-400">TRAK ERP</span>
                <span>&bull;</span>
                <span>Sistem Operasional Bengkel Alat Berat &amp; Toko Suku Cadang</span>
            </div>
            <div>
                Zona Waktu Server: <span class="text-slate-400 font-mono">Asia/Jakarta</span>
            </div>
        </div>
    </footer>

</body>
</html>
