<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ __('Data Supplier / Pemasok') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Daftar distributor dan vendor pemasok sparepart alat berat.
                </p>
            </div>
            @can('master-data.manage')
                <a href="{{ route('suppliers.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Supplier Baru
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Search Card -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('suppliers.index') }}" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan nama supplier, nomor telepon, atau alamat..." class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition">
                    Cari
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('suppliers.index') }}" class="px-3 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs rounded-lg transition">
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
                            <th class="py-3.5 px-4">#</th>
                            <th class="py-3.5 px-4">Nama Supplier / Vendor</th>
                            <th class="py-3.5 px-4">Kontak Telepon</th>
                            <th class="py-3.5 px-4">Alamat Kantor / Gudang</th>
                            @can('master-data.manage')
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($suppliers as $supplier)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-400 text-xs">
                                    {{ $supplier->id }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ $supplier->name }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-700">
                                    {{ $supplier->phone ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 text-xs">
                                    {{ $supplier->address ?: '-' }}
                                </td>
                                @can('master-data.manage')
                                    <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-amber-100 text-amber-800 hover:bg-amber-200 transition">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-red-100 text-red-800 hover:bg-red-200 transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    Tidak ada data supplier yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suppliers->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
