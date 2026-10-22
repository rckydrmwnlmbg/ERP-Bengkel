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

        <!-- Scripts (Vite + Tailwind/Alpine CDN Fallback) -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <!-- DEMO Banner (Top) -->
        @if(config('app.demo', true))
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
                    @if (session('success'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between shadow-sm">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @php
                        $allErrors = collect();
                        if (session('error')) {
                            $allErrors->push(session('error'));
                        }
                        if ($errors->any()) {
                            foreach ($errors->all() as $err) {
                                $allErrors->push($err);
                            }
                        }
                        $allErrors = $allErrors->unique()->values();
                    @endphp

                    @if ($allErrors->isNotEmpty())
                        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm shadow-sm">
                            <div class="flex items-start space-x-2">
                                <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <div class="flex-1">
                                    @if ($allErrors->count() === 1)
                                        <span class="font-semibold">{{ $allErrors->first() }}</span>
                                    @else
                                        <span class="font-bold">Terjadi kendala pada input:</span>
                                        <ul class="list-disc list-inside mt-1 text-xs space-y-0.5">
                                            @foreach ($allErrors as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                </main>

                <!-- DEMO Footer -->
                @if(config('app.demo', true))
                    <footer class="bg-white border-t border-slate-200 py-3 text-center text-xs text-slate-500">
                        DEMO Portofolio ERP Bengkel Alat Berat &amp; Toko Sparepart • Zona Waktu: Asia/Jakarta
                    </footer>
                @endif
            </div>
        </div>
    </body>
</html>
