<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ __('Barang Masuk / Pembelian Supplier') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Catat pengadaan sparepart dari distributor/pemasok, menambah stok dan memperbarui HPP rata-rata (BR-S7).
                </p>
            </div>
            @can('inventory.manage')
                <a href="{{ route('purchases.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Catat Barang Masuk Baru
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search Card -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('purchases.index') }}" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan nomor referensi atau nama supplier..." class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition">
                    Cari
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('purchases.index') }}" class="px-3 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs rounded-lg transition">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Table Card -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4"># ID</th>
                            <th class="py-3.5 px-4">Tanggal Masuk</th>
                            <th class="py-3.5 px-4">No. Referensi / Surat Jalan</th>
                            <th class="py-3.5 px-4">Supplier / Pemasok</th>
                            <th class="py-3.5 px-4 text-center">Jumlah Item</th>
                            <th class="py-3.5 px-4 text-right">Total Pembelian</th>
                            <th class="py-3.5 px-4">Dicatat Oleh</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($purchases as $p)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-500 text-xs">
                                    #{{ $p->id }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-700 whitespace-nowrap">
                                    {{ $p->purchase_date?->format('d-m-Y') }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-700">
                                    {{ $p->reference ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ $p->supplier?->name ?? 'Supplier Dihapus' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-xs text-slate-600">
                                    {{ $p->items_count }} Item
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp{{ number_format($p->total, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">
                                    {{ $p->creator?->name ?? 'Admin' }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('purchases.show', $p) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    Belum ada transaksi barang masuk / pembelian yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($purchases->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
