<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Pengeluaran Operasional
                </h2>
                <p class="text-xs text-slate-500 mt-1">Pencatatan biaya operasional, listrik, sewa, upah, dan keperluan bengkel</p>
            </div>
            <a href="{{ route('expenses.create') }}" class="inline-flex items-center px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                + Tambah Pengeluaran
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filter Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Kategori</label>
                    <select name="category" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Filter
                    </button>
                    <a href="{{ route('expenses.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Total Summary Banner -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-5 rounded-2xl shadow text-white flex items-center justify-between flex-wrap gap-4">
            <div>
                <span class="text-xs uppercase tracking-wider text-amber-400 font-bold block">Total Pengeluaran Periode</span>
                <div class="text-2xl font-black mt-1">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </div>
                <p class="text-xs text-slate-300 mt-0.5">Rentang: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block">Jumlah Transaksi</span>
                <div class="text-xl font-mono font-bold text-slate-200 mt-1">{{ $expenses->total() }} Catatan</div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Keterangan</th>
                            <th class="py-3.5 px-4 text-right">Nominal (Rp)</th>
                            <th class="py-3.5 px-4">Dicatat Oleh</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-700 whitespace-nowrap">
                                    {{ $expense->expense_date->format('d/m/Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        {{ $expense->category }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    {{ $expense->description ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    {{ $expense->creator?->name ?? 'Sistem' }}
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('expenses.edit', $expense) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                                            Edit
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Belum ada data pengeluaran operasional pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($expenses->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
