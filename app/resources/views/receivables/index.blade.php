<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ __('Buku & Laporan Piutang Pelanggan (Accounts Receivable)') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Pemantauan sisa tagihan pelanggan dan analisis umur piutang (Aging BR-R6, BR-R7).
                </p>
            </div>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                Lihat Semua Invoice &rarr;
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Aging Cards (BR-R7) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-4 rounded-xl bg-slate-900 text-white shadow-sm col-span-2 sm:col-span-1">
                <span class="text-[10px] uppercase font-bold text-amber-400 block tracking-wider">Total Piutang Aktif</span>
                <span class="font-mono font-black text-xl lg:text-2xl mt-1 block">
                    Rp{{ number_format($totalReceivables, 0, ',', '.') }}
                </span>
                <span class="text-[11px] text-slate-400 mt-0.5 block">{{ $unpaidInvoices->count() }} Faktur Terbuka</span>
            </div>

            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] uppercase font-bold text-emerald-600 block tracking-wider">Belum Jatuh Tempo</span>
                <span class="font-mono font-bold text-base mt-1 block text-slate-900">
                    Rp{{ number_format($aging['not_due'], 0, ',', '.') }}
                </span>
                <span class="text-[11px] text-slate-400 mt-0.5 block">&le; 0 Hari</span>
            </div>

            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] uppercase font-bold text-amber-600 block tracking-wider">1 - 30 Hari</span>
                <span class="font-mono font-bold text-base mt-1 block text-slate-900">
                    Rp{{ number_format($aging['days_1_30'], 0, ',', '.') }}
                </span>
                <span class="text-[11px] text-slate-400 mt-0.5 block">Lewat 1-30 hr</span>
            </div>

            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] uppercase font-bold text-amber-700 block tracking-wider">31 - 60 Hari</span>
                <span class="font-mono font-bold text-base mt-1 block text-slate-900">
                    Rp{{ number_format($aging['days_31_60'], 0, ',', '.') }}
                </span>
                <span class="text-[11px] text-slate-400 mt-0.5 block">Lewat 31-60 hr</span>
            </div>

            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-[10px] uppercase font-bold text-orange-600 block tracking-wider">61 - 90 Hari</span>
                <span class="font-mono font-bold text-base mt-1 block text-slate-900">
                    Rp{{ number_format($aging['days_61_90'], 0, ',', '.') }}
                </span>
                <span class="text-[11px] text-slate-400 mt-0.5 block">Lewat 61-90 hr</span>
            </div>

            <div class="p-4 rounded-xl bg-red-50 border border-red-200 shadow-sm">
                <span class="text-[10px] uppercase font-bold text-red-700 block tracking-wider">&gt; 90 Hari (Macet)</span>
                <span class="font-mono font-bold text-base mt-1 block text-red-900">
                    Rp{{ number_format($aging['days_over_90'], 0, ',', '.') }}
                </span>
                <span class="text-[11px] text-red-500 mt-0.5 block">Kritis</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 1 Col: Summary per Customer (BR-R6) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">
                        Rekap Piutang per Customer (BR-R6)
                    </h3>
                </div>

                <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
                    @forelse($customerSummary as $cs)
                        <div class="p-4 hover:bg-slate-50 transition flex items-center justify-between">
                            <div>
                                <a href="{{ route('customers.show', $cs['customer']) }}" class="text-sm font-bold text-slate-900 hover:text-amber-600 hover:underline">
                                    {{ $cs['customer']->name }}
                                </a>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ $cs['invoice_count'] }} Faktur Belum Lunas
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-black text-sm text-red-600 block">
                                    Rp{{ number_format($cs['total_receivable'], 0, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-slate-400">Termin: {{ $cs['customer']->payment_term_days }} Hari</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400 text-xs">
                            Tidak ada piutang customer aktif.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right 2 Cols: Unpaid Invoices Table -->
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">
                        Rincian Faktur Belum Lunas ({{ $unpaidInvoices->count() }})
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-600 uppercase font-bold">
                                <th class="py-3 px-3">No. Faktur</th>
                                <th class="py-3 px-3">Customer</th>
                                <th class="py-3 px-3">Jatuh Tempo</th>
                                <th class="py-3 px-3 text-center">Hari Lewat</th>
                                <th class="py-3 px-3 text-right">Total Faktur</th>
                                <th class="py-3 px-3 text-right">Sisa Piutang</th>
                                <th class="py-3 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($unpaidInvoices as $inv)
                                @php
                                    $today = \Carbon\Carbon::today();
                                    $daysOverdue = (int) $inv->due_date->diffInDays($today, false);
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-3 font-mono font-bold text-amber-700 whitespace-nowrap">
                                        <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">
                                            {{ $inv->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-3 font-semibold text-slate-900 whitespace-nowrap">
                                        {{ $inv->customer?->name }}
                                    </td>
                                    <td class="py-3 px-3 font-mono whitespace-nowrap {{ $daysOverdue > 0 ? 'text-red-600 font-bold' : 'text-slate-600' }}">
                                        {{ $inv->due_date?->format('d/m/Y') }}
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        @if($daysOverdue <= 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                Lancar ({{ abs($daysOverdue) }} hr lagi)
                                            </span>
                                        @elseif($daysOverdue <= 30)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                Lewat {{ $daysOverdue }} hr
                                            </span>
                                        @elseif($daysOverdue <= 60)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800">
                                                Lewat {{ $daysOverdue }} hr
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                                Lewat {{ $daysOverdue }} hr
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                        Rp{{ number_format($inv->total, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-red-600 whitespace-nowrap">
                                        Rp{{ number_format($inv->remainingBalance(), 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right whitespace-nowrap">
                                        <a href="{{ route('invoices.show', $inv) }}" class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                            Bayar / Detail &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        Semua invoice telah lunas. Tidak ada tagihan piutang terbuka.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
