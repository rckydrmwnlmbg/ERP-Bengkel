<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight leading-tight">
                    Dashboard Operasional &amp; Keuangan
                </h2>
                <p class="text-xs text-slate-500 mt-1">Ringkasan performa bengkel, arus transaksi faktur, saldo piutang, dan status ketersediaan suku cadang</p>
            </div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600 bg-white px-3.5 py-2 rounded-xl border border-slate-200/80 shadow-2xs">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- 4 Key Performance Indicator (KPI) Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Omzet Bulan Ini -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Omzet Bulan Ini</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-mono mt-3 text-slate-900 tracking-tight">
                        Rp {{ number_format($summary['month_revenue'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Total Tagihan Terbit</span>
                    @can('reports.view')
                        <a href="{{ route('reports.revenue') }}" class="text-emerald-700 hover:text-emerald-800 font-bold flex items-center gap-1">
                            <span>Laporan Omzet</span> &rarr;
                        </a>
                    @endcan
                </div>
            </div>

            <!-- 2. Total Piutang Berjalan -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Piutang Berjalan</span>
                        <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-mono mt-3 text-slate-900 tracking-tight">
                        Rp {{ number_format($summary['total_receivables'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Belum JT: <strong class="text-slate-800">Rp {{ number_format($summary['not_due_receivables'], 0, ',', '.') }}</strong></span>
                    @can('reports.receivables.view')
                        <a href="{{ route('reports.receivables') }}" class="text-blue-700 hover:text-blue-800 font-bold flex items-center gap-1">
                            <span>Aging</span> &rarr;
                        </a>
                    @endcan
                </div>
            </div>

            <!-- 3. Piutang Jatuh Tempo -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Piutang Jatuh Tempo</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-600 border border-rose-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-mono mt-3 text-rose-600 tracking-tight">
                        Rp {{ number_format($summary['overdue_receivables'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">&gt;0 hari lewat batas bayar</span>
                    <a href="{{ route('receivables.index') }}" class="text-rose-700 hover:text-rose-800 font-bold flex items-center gap-1">
                        <span>Buku Piutang</span> &rarr;
                    </a>
                </div>
            </div>

            <!-- 4. Peringatan Stok Menipis -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Peringatan Stok</span>
                        <div class="w-8 h-8 rounded-xl {{ $summary['low_stock_count'] > 0 ? 'bg-amber-500/10 text-amber-600 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' }} flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black font-mono mt-3 {{ $summary['low_stock_count'] > 0 ? 'text-amber-600' : 'text-slate-900' }} tracking-tight">
                        {{ $summary['low_stock_count'] }} <span class="text-base font-bold text-slate-600">Sparepart</span>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Mencapai batas minimum</span>
                    @can('inventory.manage')
                        <a href="{{ route('parts.index') }}" class="text-amber-700 hover:text-amber-800 font-bold flex items-center gap-1">
                            <span>Katalog Stok</span> &rarr;
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- 2 Columns Grid: Order Servis Terbaru & Alert Stok Menipis -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 5 Order Servis Terbaru -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-slate-900 text-sm tracking-tight">
                                5 Order Servis Terbaru (SPK)
                            </h3>
                        </div>
                        <a href="{{ route('work-orders.index') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                            <span>Semua Order</span> &rarr;
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-500 uppercase font-semibold text-[11px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="py-3 px-4">No. SPK</th>
                                    <th class="py-3 px-4">Customer</th>
                                    <th class="py-3 px-4">Unit Alat</th>
                                    <th class="py-3 px-4">Mekanik</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($summary['recent_work_orders'] as $wo)
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-4 font-mono font-bold text-amber-600">
                                            <a href="{{ route('work-orders.show', $wo) }}" class="hover:underline">
                                                {{ $wo->wo_number }}
                                            </a>
                                        </td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-900">
                                            {{ $wo->customer?->name ?? '-' }}
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600">
                                            {{ $wo->equipmentUnit?->brand }} {{ $wo->equipmentUnit?->model }}
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600">
                                            {{ $wo->mechanic?->name ?? 'Belum Ditugaskan' }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                @if($wo->status === 'open') bg-amber-50 text-amber-700 border border-amber-200
                                                @elseif($wo->status === 'in_progress') bg-blue-50 text-blue-700 border border-blue-200
                                                @elseif($wo->status === 'completed') bg-emerald-50 text-emerald-700 border border-emerald-200
                                                @elseif($wo->status === 'invoiced') bg-purple-50 text-purple-700 border border-purple-200
                                                @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                                                {{ $wo->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                            Belum ada order servis yang dicatat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Mekanik lapangan dapat memperbarui tahapan pengerjaan secara langsung via perangkat bergerak.</span>
                    <a href="{{ route('work-orders.create') }}" class="font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                        <span>+ Buka SPK Baru</span>
                    </a>
                </div>
            </div>

            <!-- Daftar Stok Menipis -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <div class="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-slate-900 text-sm tracking-tight">
                                Stok Suku Cadang Menipis
                            </h3>
                        </div>
                        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">&le; Batas Min</span>
                    </div>

                    <div class="divide-y divide-slate-100 mt-2">
                        @forelse($summary['low_stock_parts'] as $part)
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-900">{{ $part->name }}</div>
                                    <div class="text-slate-400 font-mono text-[11px]">{{ $part->sku }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono font-bold text-rose-600">{{ $part->qty_on_hand }} {{ $part->unit }}</div>
                                    <div class="text-[10px] text-slate-400">Min: {{ $part->min_stock }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center text-slate-400 text-xs">
                                Seluruh persediaan suku cadang berada di atas batas minimum. Aman.
                            </div>
                        @endforelse
                    </div>
                </div>

                @can('inventory.manage')
                    <div class="pt-4 border-t border-slate-100">
                        <a href="{{ route('purchases.create') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition shadow-sm">
                            + Restok (Catat Barang Masuk)
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
