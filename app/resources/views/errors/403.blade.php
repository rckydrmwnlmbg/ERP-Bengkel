<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Hak Akses Dibatasi | ERP Bengkel &amp; Sparepart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen flex flex-col justify-between">
    <!-- Top DEMO Banner -->
    @if(config('app.demo', true))
        <div class="bg-amber-500 text-slate-950 font-bold text-center py-2 px-4 text-xs md:text-sm tracking-wider uppercase shadow-md">
            ⚠️ MODE DEMO — Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart (Semua Data Fiktif)
        </div>
    @endif

    <div class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 p-8 text-center space-y-5">
            <div class="w-16 h-16 mx-auto bg-amber-100 text-amber-700 rounded-full flex items-center justify-center text-3xl font-black shadow-inner">
                🔒
            </div>
            <div>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-100 text-rose-800 mb-2">
                    Kode 403: Forbidden
                </span>
                <h1 class="text-2xl font-black text-slate-900">Hak Akses Dibatasi</h1>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                    {{ $exception->getMessage() ?: 'Peran akun Anda saat ini tidak memiliki izin untuk melihat atau mengelola halaman/fitur ini sesuai matriks hak akses PRD.' }}
                </p>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                    &larr; Kembali ke Dashboard
                </a>
                <button onclick="window.history.back()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                    Halaman Sebelumnya
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 text-center text-xs text-slate-500">
        Sistem Informasi ERP Bengkel Alat Berat &amp; Toko Sparepart • Zona Waktu: Asia/Jakarta
    </footer>
</body>
</html>
