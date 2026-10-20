<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Laporan Omzet Penjualan (BR-R1)
                </h2>
                <p class="text-xs text-slate-500 mt-1">Total seluruh pendapatan tagihan invoice non-void pada periode yang dipilih</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.export-excel', ['type' => 'revenue', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    📊 Ekspor Excel
                </a>
                <a href="{{ route('reports.print', ['type' => 'revenue', 'start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank"
                   class="inline-flex items-center px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    🖨️ Cetak / PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Date Filter Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pilihan Periode Cepat:</span>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <a href="{{ route('reports.revenue', ['preset' => 'today']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'today' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Hari Ini</a>
                    <a href="{{ route('reports.revenue', ['preset' => 'this_month']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'this_month' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Bulan Ini</a>
                    <a href="{{ route('reports.revenue', ['preset' => 'last_month']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'last_month' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Bulan Lalu</a>
                    <a href="{{ route('reports.revenue', ['preset' => 'this_year']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'this_year' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Tahun Ini</a>
                </div>
            </div>

            <form method="GET" action="{{ route('reports.revenue') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Dari Tanggal (Inklusif)</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sampai Tanggal (Inklusif)</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div class="flex items-center space-x-2">
                    <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('reports.revenue') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 p-5 rounded-2xl shadow text-white">
                <span class="text-xs uppercase tracking-wider text-amber-400 font-bold block">Total Omzet Bersih</span>
                <div class="text-2xl font-black mt-1">
                    Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Σ invoices.total (BR-R1)</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Subtotal & Diskon</span>
                <div class="text-lg font-bold text-slate-900 mt-1">
                    Rp {{ number_format($report['subtotal'], 0, ',', '.') }}
                </div>
                <p class="text-xs text-red-600 mt-0.5">Diskon: -Rp {{ number_format($report['discount_total'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Omzet per Sumber</span>
                <div class="text-xs space-y-1 mt-1.5">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Order Servis:</span>
                        <span class="font-bold text-slate-900">Rp {{ number_format($report['by_source']['work_order'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Penjualan Toko:</span>
                        <span class="font-bold text-slate-900">Rp {{ number_format($report['by_source']['counter_sale'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Volume Faktur</span>
                <div class="text-2xl font-mono font-black text-slate-900 mt-1">
                    {{ $report['invoice_count'] }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Faktur terbit non-void</p>
            </div>
        </div>

        <!-- Invoices List Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">
                    Rincian Invoice Terbit Periode ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="py-3 px-4">No. Faktur</th>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Jenis</th>
                            <th class="py-3 px-4 text-right">Subtotal</th>
                            <th class="py-3 px-4 text-right">Diskon</th>
                            <th class="py-3 px-4 text-right">Pajak</th>
                            <th class="py-3 px-4 text-right">Total (Rp)</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($report['invoices'] as $inv)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-mono font-bold text-amber-600">
                                    <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-600 whitespace-nowrap">
                                    {{ $inv->issue_date->format('d/m/Y') }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $inv->customer?->name ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-xs">
                                    @if($inv->source_type === 'work_order')
                                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">Order Servis</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold">Penjualan Toko</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    {{ number_format($inv->subtotal, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-red-600">
                                    {{ $inv->discount_amount > 0 ? '-' . number_format($inv->discount_amount, 0, ',', '.') : '0' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    {{ number_format($inv->tax_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp {{ number_format($inv->total, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase
                                        @if($inv->status === 'paid') bg-emerald-100 text-emerald-800
                                        @elseif($inv->status === 'partially_paid') bg-blue-100 text-blue-800
                                        @else bg-amber-100 text-amber-800 @endif">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    Tidak ada faktur invoice pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-100 border-t-2 border-slate-300 font-bold text-slate-900">
                        <tr>
                            <td colspan="7" class="py-3 px-4 text-right uppercase tracking-wider text-xs">Total Omzet:</td>
                            <td class="py-3 px-4 text-right font-mono text-base font-black text-amber-700">
                                Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
