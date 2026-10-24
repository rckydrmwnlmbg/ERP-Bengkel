<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Terbatas | TRAK ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
</head>
<body class="bg-slate-100 font-sans text-slate-800 min-h-screen flex flex-col justify-between">
    <!-- Top Environment Bar -->
    @if(config('app.demo', true))
        <div class="bg-slate-900 border-b border-slate-800 text-slate-300 py-1.5 px-4 text-xs font-medium sticky top-0 z-50 shadow-xs">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 uppercase tracking-wider">
                        MODE DEMO
                    </span>
                    <span class="text-slate-400 text-xs hidden sm:inline">
                        Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart (Semua Data Fiktif)
                    </span>
                </div>
            </div>
        </div>
    @endif

    <div class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200/80 p-8 text-center space-y-5">
            <div class="w-14 h-14 mx-auto bg-rose-50 border border-rose-200 text-rose-600 rounded-2xl flex items-center justify-center p-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 mb-2">
                    403 &bull; Akses Terbatas
                </span>
                <h1 class="text-2xl font-black text-slate-900">Hak Akses Dibatasi</h1>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                    {{ $exception->getMessage() ?: 'Peran akun Anda saat ini tidak memiliki izin untuk melihat atau mengelola halaman/fitur ini sesuai matriks hak akses.' }}
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
        TRAK ERP &bull; Bengkel Alat Berat &amp; Suku Cadang &bull; Zona Waktu: Asia/Jakarta
    </footer>
</body>
</html>
