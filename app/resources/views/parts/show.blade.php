<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('parts.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                    &larr; Sparepart
                </a>
                <span class="text-slate-300">/</span>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ $part->name }}
                </h2>
                <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    {{ $part->sku }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('parts.stock-card', $part) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Kartu Stok
                </a>
                @can('inventory.manage')
                    <a href="{{ route('stock-adjustments.create', ['part_id' => $part->id]) }}" class="inline-flex items-center px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Penyesuaian Stok
                    </a>
                @endcan
                @can('master-data.manage')
                    <a href="{{ route('parts.edit', $part) }}" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                        Edit Sparepart
                    </a>
                    <form method="POST" action="{{ route('parts.destroy', $part) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sparepart ini? (Soft Delete)');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-100 hover:bg-red-200 text-red-800 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                            Hapus
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">Nomor SKU / Part</span>
                    <span class="font-mono font-bold text-lg text-amber-700">{{ $part->sku }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">Kategori</span>
                    <span class="font-semibold text-slate-800 text-base">{{ $part->category }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">Satuan Unit</span>
                    <span class="font-semibold text-slate-800 text-base">{{ $part->unit }}</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 block uppercase font-bold">Harga Jual Satuan</span>
                    <span class="font-mono font-black text-2xl text-slate-900">
                        Rp{{ number_format($part->sell_price, 0, ',', '.') }}
                    </span>
                </div>

                @can('view-cost-price')
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <span class="text-xs text-amber-800 block uppercase font-bold">HPP / Avg Cost (Admin)</span>
                        <span class="font-mono font-black text-2xl text-amber-900">
                            Rp{{ number_format($part->avg_cost, 0, ',', '.') }}
                        </span>
                        @if($part->sell_price > 0 && $part->avg_cost > 0)
                            <span class="text-[11px] text-amber-700 block mt-1">
                                Margin: Rp{{ number_format($part->sell_price - $part->avg_cost, 0, ',', '.') }} 
                                ({{ round((($part->sell_price - $part->avg_cost) / $part->sell_price) * 100, 1) }}%)
                            </span>
                        @endif
                    </div>
                @endcan

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 block uppercase font-bold">Stok Fisik Tersedia</span>
                    <span class="font-mono font-black text-2xl {{ $part->qty_on_hand <= $part->min_stock ? 'text-red-600' : 'text-slate-900' }}">
                        {{ $part->qty_on_hand }} <span class="text-sm font-normal text-slate-500">{{ $part->unit }}</span>
                    </span>
                    <span class="text-[11px] text-slate-400 block mt-1">Batas Minimum: {{ $part->min_stock }} {{ $part->unit }}</span>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                <div>
                    Status: 
                    @if($part->is_active)
                        <span class="font-bold text-emerald-600">Aktif</span>
                    @else
                        <span class="font-bold text-slate-500">Nonaktif</span>
                    @endif
                </div>
                <div>
                    Dibuat: {{ $part->created_at?->translatedFormat('d F Y H:i') ?? '-' }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
