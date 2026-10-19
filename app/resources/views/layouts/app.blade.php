<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ERP Bengkel & Sparepart') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Tailwind CSS & Vite Assets -->
        <script src="https://cdn.tailwindcss.com"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            // Fail-safe Alpine.js: hanya dimuat dari CDN jika bundle Vite belum menginisialisasi window.Alpine
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
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <!-- DEMO Banner (Top) -->
        @if(env('DEMO', true))
            <div class="bg-amber-500 text-slate-950 font-bold text-center py-1.5 px-4 text-xs md:text-sm tracking-wider uppercase shadow-sm z-50 sticky top-0">
                ⚠️ MODE DEMO — Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart (Semua Data Fiktif)
            </div>
        @endif

        <div class="min-h-screen flex">
            <!-- Collapsible Sidebar with Alpine.js -->
            <x-sidebar />

            <div class="flex-1 flex flex-col min-w-0">
                <!-- Top Navbar -->
                @include('layouts.navigation')

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-b border-slate-200">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>

                <!-- DEMO Footer -->
                @if(env('DEMO', true))
                    <footer class="bg-white border-t border-slate-200 py-3 text-center text-xs text-slate-500">
                        DEMO Portofolio ERP Bengkel Alat Berat &amp; Toko Sparepart • Zona Waktu: Asia/Jakarta
                    </footer>
                @endif
            </div>
        </div>
    </body>
</html>
