<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('purchases.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                    &larr; Barang Masuk
                </a>
                <span class="text-slate-300">/</span>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Faktur Pembelian #{{ $purchase->id }}
                </h2>
                @if($purchase->reference)
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        {{ $purchase->reference }}
                    </span>
                @endif
            </div>
            <a href="{{ route('purchases.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Purchase Header Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">Ringkasan Penerimaan Barang Masuk</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">Supplier / Pemasok</span>
                    <span class="font-bold text-sm text-slate-800 block mt-0.5">{{ $purchase->supplier?->name ?? '-' }}</span>
                    <span class="text-xs text-slate-500">{{ $purchase->supplier?->phone }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">Tanggal Pembelian</span>
                    <span class="font-bold text-sm text-slate-800 font-mono block mt-0.5">{{ $purchase->purchase_date?->format('d-m-Y') }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">No. Referensi</span>
                    <span class="font-bold text-sm text-slate-800 font-mono block mt-0.5">{{ $purchase->reference ?: '-' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold">Total Pembelian</span>
                    <span class="font-mono font-black text-xl text-amber-700 block mt-0.5">
                        Rp{{ number_format($purchase->total, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Items Table Card -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <div class="p-4 border-b border-slate-200 bg-slate-50">
                <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700">Daftar Item Sparepart Diterima</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">SKU & Sparepart</th>
                            <th class="py-3 px-4 text-center">Kuantitas</th>
                            <th class="py-3 px-4 text-right">Harga Beli Satuan</th>
                            <th class="py-3 px-4 text-right">Total Biaya</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($purchase->items as $idx => $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ $item->part?->name }}</div>
                                    <div class="text-[11px] font-mono text-amber-700 font-semibold">{{ $item->part?->sku }} • {{ $item->part?->category }}</div>
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-900">
                                    {{ $item->qty }} <span class="text-[10px] text-slate-400 font-normal">{{ $item->part?->unit }}</span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-800">
                                    Rp{{ number_format($item->unit_cost, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp{{ number_format($item->qty * $item->unit_cost, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('parts.stock-card', $item->part_id) }}" class="inline-flex items-center px-2 py-1 text-[11px] font-semibold rounded bg-blue-100 text-blue-800 hover:bg-blue-200 transition">
                                        Kartu Stok &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 border-t border-slate-200">
                            <td colspan="4" class="py-3 px-4 font-bold text-slate-700 text-right uppercase text-xs">
                                Total Keseluruhan:
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-slate-950 text-base">
                                Rp{{ number_format($purchase->total, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
