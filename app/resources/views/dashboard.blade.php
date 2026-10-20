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
    </div>
</x-app-layout>
