<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERP Bengkel Alat Berat &amp; Toko Sparepart (DEMO)</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS & Vite Assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Fail-safe Alpine.js: hanya dimuat jika bundle Vite belum menginisialisasi window.Alpine
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Alpine) {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
                script.defer = true;
                document.head.appendChild(script);
            }
        });
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans antialiased bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-900">
    
    <!-- DEMO Top Banner -->
    @if(env('DEMO', true))
        <div class="bg-amber-500 text-slate-950 font-bold text-center py-2 px-4 text-xs md:text-sm tracking-wider uppercase shadow-md sticky top-0 z-50">
            ⚠️ MODE DEMO — Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart (Semua Data Fiktif)
        </div>
    @endif

    <!-- Header / Navbar -->
    <header class="max-w-7xl mx-auto w-full px-6 py-6 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="text-2xl font-black tracking-wider text-amber-400">ERP</span>
            <span class="text-sm font-semibold uppercase text-slate-400 tracking-wider">Bengkel &amp; Sparepart</span>
        </div>
        <div>
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-amber-500 text-slate-950 font-bold text-sm hover:bg-amber-400 transition shadow">
                    Buka Dashboard &rarr;
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-amber-500 text-slate-950 font-bold text-sm hover:bg-amber-400 transition shadow">
                    Masuk ke Sistem &rarr;
                </a>
            @endauth
        </div>
    </header>

    <!-- Hero Section -->
    <main class="max-w-7xl mx-auto px-6 py-12 flex-1 flex flex-col justify-center">
        <div class="text-center max-w-3xl mx-auto space-y-4">
            <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-amber-400 text-xs font-semibold uppercase tracking-wider">
                Portofolio Demo Sistem Informasi
            </div>
            <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white leading-tight">
                Sistem ERP Terpadu Bengkel Alat Berat &amp; Toko Sparepart
            </h1>
            <p class="text-slate-400 text-base md:text-lg leading-relaxed">
                Platform operasional lengkap: alur Order Servis (Work Order), Weighted Average Cost stok sparepart, penagihan invoice, pembayaran, hingga laporan laba rugi dan aging piutang.
            </p>
            <div class="pt-4 flex justify-center gap-4 flex-wrap">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-8 py-3.5 rounded-xl bg-amber-500 text-slate-950 font-extrabold text-base hover:bg-amber-400 transition shadow-lg">
                        Menuju Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-8 py-3.5 rounded-xl bg-amber-500 text-slate-950 font-extrabold text-base hover:bg-amber-400 transition shadow-lg">
                        Masuk / Login Demo
                    </a>
                @endauth
            </div>
        </div>

        <!-- Demo Accounts Grid -->
        <div class="mt-16 pt-12 border-t border-slate-800">
            <h2 class="text-center text-xs font-bold uppercase tracking-widest text-slate-400 mb-8">
                Pilih Akun Demo untuk Menguji Hak Akses (Password Semua: <code class="text-amber-400 bg-slate-800 px-2 py-0.5 rounded">password123</code>)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <!-- Admin Card -->
                <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 hover:border-amber-500/50 transition">
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-lg bg-red-950/80 border border-red-700 text-red-300 font-bold flex items-center justify-center">
                            👑
                        </span>
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-red-900/60 text-red-200 border border-red-800 uppercase">
                            Administrator
                        </span>
                    </div>
                    <h3 class="text-base font-bold text-white">Akses Penuh (Admin)</h3>
                    <p class="text-xs text-slate-400 mt-1 mb-4">Master Data, lihat HPP &amp; laba, barang masuk, penyesuaian stok, void invoice/pembayaran, audit log, dan pengaturan.</p>
                    <div class="bg-slate-900/80 rounded-lg p-2.5 font-mono text-xs text-slate-300">
                        Email: <strong class="text-amber-400">admin@example.com</strong>
                    </div>
                </div>

                <!-- Cashier Card -->
                <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 hover:border-amber-500/50 transition">
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-lg bg-blue-950/80 border border-blue-700 text-blue-300 font-bold flex items-center justify-center">
                            💼
                        </span>
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-blue-900/60 text-blue-200 border border-blue-800 uppercase">
                            Kasir
                        </span>
                    </div>
                    <h3 class="text-base font-bold text-white">Kasir &amp; Penjualan</h3>
                    <p class="text-xs text-slate-400 mt-1 mb-4">Lihat &amp; tambah customer, penjualan sparepart langsung, buat invoice, terima pembayaran cicilan, dan lihat laporan piutang.</p>
                    <div class="bg-slate-900/80 rounded-lg p-2.5 font-mono text-xs text-slate-300">
                        Email: <strong class="text-amber-400">cashier@example.com</strong>
                    </div>
                </div>

                <!-- Mechanic Card -->
                <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-6 hover:border-amber-500/50 transition">
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-lg bg-emerald-950/80 border border-emerald-700 text-emerald-300 font-bold flex items-center justify-center">
                            🔧
                        </span>
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-900/60 text-emerald-200 border border-emerald-800 uppercase">
                            Mekanik
                        </span>
                    </div>
                    <h3 class="text-base font-bold text-white">Teknisi / Mekanik</h3>
                    <p class="text-xs text-slate-400 mt-1 mb-4">Antarmuka ramah ponsel: buat order servis, isi diagnosa keluhan, pilih sparepart &amp; jasa, serta update status pengerjaan.</p>
                    <div class="bg-slate-900/80 rounded-lg p-2.5 font-mono text-xs text-slate-300">
                        Email: <strong class="text-amber-400">mechanic@example.com</strong>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
        Demo Portofolio ERP Bengkel Alat Berat &amp; Toko Sparepart • Zona Waktu: Asia/Jakarta • Laravel 12 &amp; Tailwind CSS
    </footer>

</body>
</html>
