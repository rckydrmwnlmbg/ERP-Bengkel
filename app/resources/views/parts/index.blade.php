<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ __('Katalog Sparepart') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Kelola data suku cadang alat berat, kategori, satuan, dan harga jual.
                </p>
            </div>
            @can('master-data.manage')
                <a href="{{ route('parts.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Sparepart Baru
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search & Filter Card -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('parts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Cari Sparepart</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan nama, SKU, atau kategori..." class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Kategori</label>
                    <select name="category" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'category']))
                        <a href="{{ route('parts.index') }}" class="px-3 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs rounded-lg transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">SKU / No. Part</th>
                            <th class="py-3.5 px-4">Nama Sparepart</th>
                            <th class="py-3.5 px-4">Kategori & Satuan</th>
                            <th class="py-3.5 px-4 text-right">Harga Jual</th>
                            @can('view-cost-price')
                                <th class="py-3.5 px-4 text-right bg-amber-50/60 text-amber-900 border-x border-amber-200/50">
                                    HPP / Avg Cost
                                </th>
                            @endcan
                            <th class="py-3.5 px-4 text-center">Stok</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($parts as $part)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-700">
                                    {{ $part->sku }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <a href="{{ route('parts.show', $part) }}" class="hover:text-amber-600 transition">
                                        {{ $part->name }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 text-xs">
                                    <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium mr-1">
                                        {{ $part->category }}
                                    </span>
                                    <span class="text-slate-400">/ {{ $part->unit }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp{{ number_format($part->sell_price, 0, ',', '.') }}
                                </td>
                                @can('view-cost-price')
                                    <td class="py-3.5 px-4 text-right font-mono text-amber-800 bg-amber-50/40 border-x border-amber-100 font-semibold text-xs">
                                        Rp{{ number_format($part->avg_cost, 0, ',', '.') }}
                                    </td>
                                @endcan
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-mono font-bold {{ $part->qty_on_hand <= $part->min_stock ? 'text-red-600' : 'text-slate-800' }}">
                                        {{ $part->qty_on_hand }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 block">(Min: {{ $part->min_stock }})</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if ($part->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-200 text-slate-600">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('parts.show', $part) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        Detail
                                    </a>
                                    @can('master-data.manage')
                                        <a href="{{ route('parts.edit', $part) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-amber-100 text-amber-800 hover:bg-amber-200 transition">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('parts.destroy', $part) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sparepart ini? (Soft Delete)');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-red-100 text-red-800 hover:bg-red-200 transition">
                                                Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->can('view-cost-price') ? 8 : 7 }}" class="py-12 text-center text-slate-400">
                                    Tidak ada data sparepart yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($parts->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $parts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
