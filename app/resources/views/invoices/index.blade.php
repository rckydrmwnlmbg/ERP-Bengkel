<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ __('Faktur & Tagihan (Invoices)') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Daftar faktur penagihan servis dan penjualan langsung sparepart (BR-I1..I8).
                </p>
            </div>
            @can('direct-sales.manage')
                <div class="flex items-center space-x-2">
                    <a href="{{ route('direct-sales.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        + Penjualan Langsung
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
            @php
                $currentStatus = request('status');
                $statuses = [
                    '' => 'Semua',
                    'issued' => 'Belum Dibayar (Issued)',
                    'partially_paid' => 'Sebagian (Partially Paid)',
                    'paid' => 'Lunas (Paid)',
                    'void' => 'Dibatalkan (Void)',
                ];
            @endphp
            @foreach($statuses as $stKey => $stLabel)
                <a href="{{ route('invoices.index', array_merge(request()->except('status', 'page'), $stKey ? ['status' => $stKey] : [])) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ ($currentStatus === $stKey || ($stKey === '' && !$currentStatus)) ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    {{ $stLabel }}
                </a>
            @endforeach
        </div>

        <!-- Filter Search Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('invoices.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="sm:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari nomor invoice (mis: INV-2610-0001) atau nama customer..."
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <select name="source_type" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">-- Semua Tipe --</option>
                        <option value="work_order" {{ request('source_type') === 'work_order' ? 'selected' : '' }}>Order Servis</option>
                        <option value="counter_sale" {{ request('source_type') === 'counter_sale' ? 'selected' : '' }}>Penjualan Langsung</option>
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'source_type']))
                        <a href="{{ route('invoices.index') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table of Invoices -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">No. Invoice</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Sumber</th>
                            <th class="py-3.5 px-4">Tanggal Terbit</th>
                            <th class="py-3.5 px-4">Jatuh Tempo</th>
                            <th class="py-3.5 px-4 text-right">Total Faktur</th>
                            <th class="py-3.5 px-4 text-right">Sisa Tagihan</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($invoices as $inv)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-700 text-xs whitespace-nowrap">
                                    <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ $inv->customer?->name ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-xs whitespace-nowrap">
                                    @if($inv->source_type === 'work_order')
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            Servis ({{ $inv->workOrder?->wo_number }})
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                            Toko (Direct)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-700 whitespace-nowrap">
                                    {{ $inv->issue_date?->format('d-m-Y') }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs whitespace-nowrap {{ $inv->isOverdue() ? 'text-red-600 font-bold' : 'text-slate-600' }}">
                                    {{ $inv->due_date?->format('d-m-Y') }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp{{ number_format($inv->total, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold {{ $inv->remainingBalance() > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                    Rp{{ number_format($inv->remainingBalance(), 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @switch($inv->status)
                                        @case('issued')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">Issued</span>
                                            @break
                                        @case('partially_paid')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">Partial</span>
                                            @break
                                        @case('paid')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Paid</span>
                                            @break
                                        @case('void')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 text-slate-600 border border-slate-300">Void</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                    <a href="{{ route('invoices.show', $inv) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                        Detail
                                    </a>
                                    <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded bg-amber-100 hover:bg-amber-200 text-amber-800 transition">
                                        Cetak
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    Belum ada data faktur / invoice.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
