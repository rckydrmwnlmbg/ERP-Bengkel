<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('parts.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                    &larr; Sparepart
                </a>
                <span class="text-slate-300">/</span>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Kartu Stok: {{ $part->name }}
                </h2>
                <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    {{ $part->sku }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                @can('inventory.manage')
                    <a href="{{ route('stock-adjustments.create', ['part_id' => $part->id]) }}" class="inline-flex items-center px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                        + Penyesuaian Stok
                    </a>
                @endcan
                <a href="{{ route('parts.show', $part) }}" class="inline-flex items-center px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                    Lihat Detail Part
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Part Summary & Invariant Integrity Card -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-400 block uppercase font-bold">Saldo Stok Saat Ini</span>
                <span class="font-mono font-black text-3xl {{ $part->qty_on_hand <= $part->min_stock ? 'text-red-600' : 'text-slate-900' }}">
                    {{ $part->qty_on_hand }}
                </span>
                <span class="text-xs text-slate-500 font-semibold">{{ $part->unit }} (Min: {{ $part->min_stock }})</span>
            </div>

            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-400 block uppercase font-bold">Harga Jual Satuan</span>
                <span class="font-mono font-black text-2xl text-slate-900">
                    Rp{{ number_format($part->sell_price, 0, ',', '.') }}
                </span>
                <span class="text-xs text-slate-400 block mt-1">Kategori: {{ $part->category }}</span>
            </div>

            @can('view-cost-price')
                <div class="p-5 rounded-xl bg-amber-50 border border-amber-200 shadow-sm">
                    <span class="text-xs text-amber-800 block uppercase font-bold">HPP Rata-rata (WAC)</span>
                    <span class="font-mono font-black text-2xl text-amber-900">
                        Rp{{ number_format($part->avg_cost, 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-amber-700 block mt-1">Weighted Average Cost (BR-S7)</span>
                </div>
            @endcan

            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">Status Integritas Stok (BR-S1)</span>
                    @if($isIntegrityValid)
                        <div class="inline-flex items-center space-x-1.5 mt-2 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-200">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Integritas Valid: {{ $totalSumChange }} {{ $part->unit }}</span>
                        </div>
                    @else
                        <div class="inline-flex items-center space-x-1.5 mt-2 px-2.5 py-1 rounded-full bg-red-100 text-red-800 text-xs font-bold border border-red-200">
                            <span>Integritas Gagal (Selisih terdeteksi)</span>
                        </div>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400 mt-2">qty_on_hand identik dengan total akumulasi pergerakan.</p>
            </div>
        </div>

        <!-- Stock Movements Table (Kartu Stok) -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">
                        Riwayat Pergerakan Stok (Kartu Stok)
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Catatan pergerakan mutasi fisik sparepart bersifat immutable (append-only, BR-S3).</p>
                </div>
                <span class="text-xs font-semibold text-slate-600">
                    Total: {{ $movements->total() }} Transaksi Mutasi
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Waktu & Tanggal</th>
                            <th class="py-3 px-4">Tipe Pergerakan</th>
                            <th class="py-3 px-4 text-center">Mutasi (+ / -)</th>
                            <th class="py-3 px-4 text-center">Saldo Akhir</th>
                            @can('view-cost-price')
                                <th class="py-3 px-4 text-right bg-amber-50/50 text-amber-900 border-x border-amber-200/40">
                                    Snapshot HPP (BR-S8)
                                </th>
                            @endcan
                            <th class="py-3 px-4">Referensi / Alasan</th>
                            <th class="py-3 px-4 text-right">Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($movements as $m)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                    {{ $m->created_at?->format('d-m-Y H:i:s') ?? '-' }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @switch($m->type)
                                        @case('PURCHASE_IN')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Barang Masuk (Beli)
                                            </span>
                                            @break
                                        @case('SERVICE_OUT')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                Pemakaian Servis
                                            </span>
                                            @break
                                        @case('SERVICE_RETURN')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                Retur Servis
                                            </span>
                                            @break
                                        @case('SALE_OUT')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                Penjualan Toko
                                            </span>
                                            @break
                                        @case('SALE_RETURN')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                                                Retur Penjualan
                                            </span>
                                            @break
                                        @case('ADJUSTMENT_IN')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                                Opname (+)
                                            </span>
                                            @break
                                        @case('ADJUSTMENT_OUT')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                Opname (-)
                                            </span>
                                            @break
                                        @default
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $m->type }}
                                            </span>
                                    @endswitch
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-black text-sm whitespace-nowrap">
                                    @if($m->qty_change > 0)
                                        <span class="text-emerald-600">+{{ $m->qty_change }}</span>
                                    @else
                                        <span class="text-red-600">{{ $m->qty_change }}</span>
                                    @endif
                                    <span class="text-[10px] text-slate-400 font-normal">{{ $part->unit }}</span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800 text-sm whitespace-nowrap">
                                    {{ $m->balance_after }} <span class="text-[10px] text-slate-400 font-normal">{{ $part->unit }}</span>
                                </td>
                                @can('view-cost-price')
                                    <td class="py-3 px-4 text-right font-mono font-semibold text-amber-900 bg-amber-50/30 border-x border-amber-100 whitespace-nowrap">
                                        Rp{{ number_format($m->unit_cost_snapshot, 0, ',', '.') }}
                                    </td>
                                @endcan
                                <td class="py-3 px-4 text-slate-700">
                                    <div>{{ $m->reason ?: '-' }}</div>
                                    @if($m->ref_type && $m->ref_id)
                                        <span class="text-[10px] text-slate-400 font-mono">Ref: {{ $m->ref_type }} #{{ $m->ref_id }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-slate-600 whitespace-nowrap">
                                    {{ $m->creator?->name ?? 'Sistem' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->can('view-cost-price') ? 7 : 6 }}" class="py-12 text-center text-slate-400">
                                    Belum ada pergerakan stok untuk sparepart ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
