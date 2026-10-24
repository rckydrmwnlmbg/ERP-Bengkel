<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TRAK ERP') }} — Portal Masuk Sistem</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts (Vite + Tailwind/Alpine CDN Fallback) -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        }
                    }
                }
            }
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-100 antialiased min-h-full flex flex-col justify-between bg-slate-950 selection:bg-amber-500 selection:text-slate-950">
        <!-- Environment Status Bar -->
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

        <div class="flex-1 flex flex-col justify-center items-center px-4 py-12 relative overflow-hidden">
            <!-- Subtle Radial Ambient Glow -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[300px] bg-amber-500/10 blur-[120px] rounded-full pointer-events-none -z-10"></div>

            <div class="w-full max-w-md">
                <!-- Branding Header -->
                <div class="text-center mb-8">
                    <a href="/" class="inline-flex flex-col items-center group">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500/20 to-amber-600/10 border border-amber-500/30 flex items-center justify-center p-3 shadow-lg shadow-amber-500/5 group-hover:border-amber-500/50 transition">
                            <x-application-logo class="w-full h-full text-amber-500" />
                        </div>
                        <div class="mt-3 flex items-center space-x-1.5">
                            <span class="text-2xl font-extrabold tracking-tight text-white">TRAK<span class="text-amber-500">ERP</span></span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 font-medium">Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart</p>
                    </a>
                </div>

                <!-- Form Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-slate-800/80 bg-slate-950 py-4 text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>TRAK ERP &bull; Sistem Operasional Bengkel &amp; Logistik Suku Cadang</span>
                <span>Zona Waktu: <strong class="text-slate-400 font-mono">Asia/Jakarta</strong></span>
            </div>
        </footer>
    </body>
</html>
