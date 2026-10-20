<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ __('Penyesuaian Stok (Stok Opname)') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Koreksi selisih fisik gudang dengan catatan sistem (BR-S6: Alasan wajib dicatat dan hanya dapat dilakukan oleh Admin).
                </p>
            </div>
            @can('inventory.manage')
                <a href="{{ route('stock-adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Penyesuaian Baru
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Table Card -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">Waktu</th>
                            <th class="py-3.5 px-4">Sparepart</th>
                            <th class="py-3.5 px-4 text-center">Tipe Koreksi</th>
                            <th class="py-3.5 px-4 text-center">Mutasi (+ / -)</th>
                            <th class="py-3.5 px-4 text-center">Saldo Akhir</th>
                            <th class="py-3.5 px-4">Alasan Penyesuaian (BR-S6)</th>
                            <th class="py-3.5 px-4 text-right">Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($adjustments as $adj)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-500 whitespace-nowrap">
                                    {{ $adj->created_at?->format('d-m-Y H:i') ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-900">{{ $adj->part?->name }}</div>
                                    <div class="text-xs font-mono text-amber-700 font-bold">{{ $adj->part?->sku }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @if($adj->type === 'ADJUSTMENT_IN')
                                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                            Tambah Stok (+)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Kurangi Stok (-)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-black text-sm whitespace-nowrap">
                                    @if($adj->qty_change > 0)
                                        <span class="text-teal-600">+{{ $adj->qty_change }}</span>
                                    @else
                                        <span class="text-amber-600">{{ $adj->qty_change }}</span>
                                    @endif
                                    <span class="text-[11px] text-slate-400 font-normal">{{ $adj->part?->unit }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                                    {{ $adj->balance_after }} {{ $adj->part?->unit }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-700">
                                    {{ $adj->reason }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-xs text-slate-600 whitespace-nowrap">
                                    {{ $adj->creator?->name ?? 'Admin' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Belum ada catatan penyesuaian stok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($adjustments->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $adjustments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
