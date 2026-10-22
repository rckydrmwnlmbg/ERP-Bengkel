<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Dashboard Operasional &amp; Keuangan
                </h2>
                <p class="text-xs text-slate-500 mt-1">Ringkasan performa bengkel, omzet, piutang, stok, dan order servis aktif</p>
            </div>
            <span class="text-xs font-bold text-slate-500 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">
                {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- 4 Key Performance Indicator (KPI) Metric Cards (PRD Section 10) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Omzet Bulan Ini (BR-R1) -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 p-5 rounded-2xl shadow text-white flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs uppercase tracking-wider text-amber-400 font-bold">Omzet Bulan Ini</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono">BR-R1</span>
                    </div>
                    <div class="text-2xl font-black font-mono mt-2 text-white">
                        Rp {{ number_format($summary['month_revenue'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-700/60 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Total Tagihan Terbit</span>
                    @can('reports.view')
                        <a href="{{ route('reports.revenue') }}" class="text-amber-400 hover:underline font-bold">Lihat Laporan &rarr;</a>
                    @endcan
                </div>
            </div>

            <!-- 2. Total Piutang Berjalan (BR-R6, BR-R8) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-bold">Total Piutang Berjalan</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold">BR-R6 / R8</span>
                    </div>
                    <div class="text-2xl font-black font-mono mt-2 text-slate-900">
                        Rp {{ number_format($summary['total_receivables'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Belum JT: <strong>Rp {{ number_format($summary['not_due_receivables'], 0, ',', '.') }}</strong></span>
                    @can('reports.receivables.view')
                        <a href="{{ route('reports.receivables') }}" class="text-blue-600 hover:underline font-bold">Aging &rarr;</a>
                    @endcan
                </div>
            </div>

            <!-- 3. Piutang Jatuh Tempo (Overdue) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs uppercase tracking-wider text-red-600 font-bold">Piutang Jatuh Tempo</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-800 font-bold">BR-R7</span>
                    </div>
                    <div class="text-2xl font-black font-mono mt-2 text-red-600">
                        Rp {{ number_format($summary['overdue_receivables'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">&gt;0 hari lewat due date</span>
                    <a href="{{ route('receivables.index') }}" class="text-amber-700 hover:underline font-bold">Buku Piutang &rarr;</a>
                </div>
            </div>

            {{-- 4. Stok Menipis (qty_on_hand <= min_stock) --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs uppercase tracking-wider text-slate-400 font-bold">Peringatan Stok</span>
                        <span class="text-[10px] px-2 py-0.5 rounded {{ $summary['low_stock_count'] > 0 ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800' }} font-bold">
                            {{ $summary['low_stock_count'] }} Item
                        </span>
                    </div>
                    <div class="text-2xl font-black font-mono mt-2 {{ $summary['low_stock_count'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                        {{ $summary['low_stock_count'] }} Sparepart
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Mencapai batas minimum</span>
                    @can('inventory.manage')
                        <a href="{{ route('parts.index') }}" class="text-blue-600 hover:underline font-bold">Lihat Stok &rarr;</a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Grid 2 Kolom: Order Servis Terbaru & Alert Stok Menipis -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 5 Order Servis Terbaru (PRD Section 10) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">📋</span>
                            <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">
                                5 Order Servis Terbaru (SPK)
                            </h3>
                        </div>
                        <a href="{{ route('work-orders.index') }}" class="text-xs font-bold text-amber-600 hover:underline">
                            Semua Order &rarr;
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="py-3 px-4">No. WO</th>
                                    <th class="py-3 px-4">Customer</th>
                                    <th class="py-3 px-4">Unit Alat</th>
                                    <th class="py-3 px-4">Mekanik</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($summary['recent_work_orders'] as $wo)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3 px-4 font-mono font-bold text-amber-600">
                                            <a href="{{ route('work-orders.show', $wo) }}" class="hover:underline">
                                                {{ $wo->wo_number }}
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-slate-900">
                                            {{ $wo->customer?->name ?? '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-600">
                                            {{ $wo->equipmentUnit?->brand }} {{ $wo->equipmentUnit?->model }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-600">
                                            {{ $wo->mechanic?->name ?? 'Belum Ditugaskan' }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold uppercase
                                                @if($wo->status === 'open') bg-amber-100 text-amber-800
                                                @elseif($wo->status === 'in_progress') bg-blue-100 text-blue-800
                                                @elseif($wo->status === 'completed') bg-emerald-100 text-emerald-800
                                                @elseif($wo->status === 'invoiced') bg-purple-100 text-purple-800
                                                @else bg-slate-200 text-slate-600 @endif">
                                                {{ $wo->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-slate-400">
                                            Belum ada order servis yang dibuat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Mekanik dapat memperbarui status pengerjaan secara langsung melalui HP / Ponsel.</span>
                    <a href="{{ route('work-orders.create') }}" class="font-bold text-amber-600 hover:underline">+ Buat Order Baru</a>
                </div>
            </div>

            <!-- Daftar Stok Menipis -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <span class="text-base">⚠️</span>
                            <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">
                                Stok Sparepart Menipis
                            </h3>
                        </div>
                        <span class="text-xs text-red-600 font-bold">&le; Batas Min</span>
                    </div>

                    <div class="divide-y divide-slate-100 mt-2">
                        @forelse($summary['low_stock_parts'] as $part)
                            <div class="py-2.5 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-900">{{ $part->name }}</div>
                                    <div class="text-slate-400 font-mono text-[11px]">{{ $part->sku }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono font-bold text-red-600">{{ $part->qty_on_hand }} {{ $part->unit }}</div>
                                    <div class="text-[10px] text-slate-400">Min: {{ $part->min_stock }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-slate-400 text-xs">
                                Seluruh persediaan sparepart berada di atas batas minimum. Aman!
                            </div>
                        @endforelse
                    </div>
                </div>

                @can('inventory.manage')
                    <div class="pt-4 border-t border-slate-100">
                        <a href="{{ route('purchases.create') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition">
                            + Restok (Catat Pembelian)
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
