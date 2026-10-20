<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts (Vite + Tailwind/Alpine CDN Fallback) -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <!-- DEMO Banner -->
        @if(env('DEMO', true))
            <div class="bg-amber-500 text-slate-950 font-bold text-center py-1.5 px-4 text-xs md:text-sm tracking-wider uppercase shadow-sm">
                ⚠️ MODE DEMO — Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart (Semua Data Fiktif)
            </div>
        @endif

        <div class="min-h-screen flex flex-col justify-between bg-gray-100">
            <div class="flex-1 flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
                <div>
                    <a href="/">
                        <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                    </a>
                </div>

                <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>
            </div>

            <!-- DEMO Footer -->
            @if(env('DEMO', true))
                <footer class="bg-white border-t border-slate-200 py-3 text-center text-xs text-slate-500">
                    DEMO Portofolio ERP Bengkel Alat Berat &amp; Toko Sparepart • Zona Waktu: Asia/Jakarta
                </footer>
            @endif
        </div>
    </body>
</html>
