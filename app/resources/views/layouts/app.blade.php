<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TRAK ERP — Bengkel & Sparepart') }}</title>

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
    <body class="font-sans antialiased bg-slate-100 text-slate-900 min-h-full flex flex-col selection:bg-amber-500 selection:text-slate-950">
        <!-- Environment Banner (Top) -->
        @if(config('app.demo', true))
            <div class="bg-slate-900 border-b border-slate-800 text-slate-300 py-1.5 px-4 text-xs font-medium z-50 sticky top-0 shadow-xs">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 uppercase tracking-wider">
                            MODE DEMO
                        </span>
                        <span class="text-slate-400 text-xs hidden sm:inline">
                            Sistem ERP Bengkel Alat Berat &amp; Toko Sparepart (Semua Data Fiktif)
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400 font-mono hidden md:inline">
                        Lingkungan Simulasi &bull; Integritas Data Aktif
                    </div>
                </div>
            </div>
        @endif

        <div class="flex-1 flex min-h-0">
            <!-- Sleek Collapsible Sidebar -->
            <x-sidebar />

            <div class="flex-1 flex flex-col min-w-0 bg-slate-100">
                <!-- Top Navbar -->
                @include('layouts.navigation')

                <!-- Page Heading (Optional) -->
                @isset($header)
                    <header class="bg-white border-b border-slate-200/80 shadow-2xs">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
                    @if (session('success'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-2xs">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
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
                        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-2xs">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    @if ($allErrors->count() === 1)
                                        <span class="font-semibold">{{ $allErrors->first() }}</span>
                                    @else
                                        <span class="font-bold text-rose-900">Perhatian pada data input:</span>
                                        <ul class="list-disc list-inside mt-1 text-xs space-y-1">
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

                <!-- Footer -->
                @if(config('app.demo', true))
                    <footer class="bg-white border-t border-slate-200/80 py-3.5 px-6 text-center text-xs text-slate-500 mt-auto">
                        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                            <span>TRAK ERP &bull; Sistem Operasional Bengkel Alat Berat &amp; Suku Cadang</span>
                            <span>Zona Waktu Server: <strong class="text-slate-600 font-mono">Asia/Jakarta</strong></span>
                        </div>
                    </footer>
                @endif
            </div>
        </div>
    </body>
</html>
