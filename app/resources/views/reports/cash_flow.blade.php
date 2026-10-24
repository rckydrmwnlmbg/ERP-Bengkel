<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Laporan Arus Kas (BR-R4, BR-R5)
                </h2>
                <p class="text-xs text-slate-500 mt-1">Pergerakan kas masuk dari pembayaran pelanggan dan kas keluar untuk pembelian & operasional</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.export-excel', ['type' => 'cash-flow', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Ekspor Excel</span>
                </a>
                <a href="{{ route('reports.print', ['type' => 'cash-flow', 'start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank"
                   class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak / PDF</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Catatan Penyederhanaan BR-R5 Alert -->
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 shadow-2xs flex items-start space-x-3">
            <div class="w-5 h-5 text-amber-600 shrink-0 mt-0.5">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-xs uppercase tracking-wider text-amber-900">Catatan Penyederhanaan Aturan Bisnis (BR-R5)</h4>
                <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                    {{ $report['simplification_notes']['br_r5'] }}
                    Seluruh transaksi pembelian barang masuk langsung mengurangi kas pada tanggal faktur pembelian.
                </p>
            </div>
        </div>

        <!-- Date Filter Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pilihan Periode Cepat:</span>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <a href="{{ route('reports.cash-flow', ['preset' => 'today']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'today' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Hari Ini</a>
                    <a href="{{ route('reports.cash-flow', ['preset' => 'this_month']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'this_month' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Bulan Ini</a>
                    <a href="{{ route('reports.cash-flow', ['preset' => 'last_month']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'last_month' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Bulan Lalu</a>
                    <a href="{{ route('reports.cash-flow', ['preset' => 'this_year']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'this_year' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Tahun Ini</a>
                </div>
            </div>

            <form method="GET" action="{{ route('reports.cash-flow') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
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
                    <a href="{{ route('reports.cash-flow') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Cash Flow Statement Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="font-black text-base uppercase tracking-wider">Laporan Ringkasan Arus Kas</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                </div>
                <span class="px-3 py-1 bg-amber-500 text-slate-950 font-bold text-xs uppercase rounded-lg">
                    Rupiah (IDR)
                </span>
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                <!-- 1. Arus Kas Masuk -->
                <div>
                    <div class="flex items-center justify-between py-2 border-b-2 border-emerald-600 text-emerald-900 font-bold">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm uppercase tracking-wider">A. Arus Kas Masuk (Penerimaan Pembayaran)</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">BR-R4</span>
                        </div>
                        <span class="font-mono text-base font-black text-emerald-700">Rp {{ number_format($report['cash_in'], 0, ',', '.') }}</span>
                    </div>
                    <div class="divide-y divide-slate-100 pl-4 mt-2">
                        <div class="flex items-center justify-between py-1.5 text-xs text-slate-600">
                            <span>Pembayaran Tunai (Cash):</span>
                            <span class="font-mono font-medium">Rp {{ number_format($report['cash_in_by_method']['cash'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 text-xs text-slate-600">
                            <span>Pembayaran Transfer Bank:</span>
                            <span class="font-mono font-medium">Rp {{ number_format($report['cash_in_by_method']['transfer'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 text-xs text-slate-600">
                            <span>Metode Lainnya:</span>
                            <span class="font-mono font-medium">Rp {{ number_format($report['cash_in_by_method']['other'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Arus Kas Keluar -->
                <div>
                    <div class="flex items-center justify-between py-2 border-b-2 border-red-600 text-red-900 font-bold">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm uppercase tracking-wider">B. Arus Kas Keluar (Pengeluaran Kas)</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-red-100 text-red-800 font-bold">BR-R5</span>
                        </div>
                        <span class="font-mono text-base font-black text-red-700">(Rp {{ number_format($report['cash_out'], 0, ',', '.') }})</span>
                    </div>
                    <div class="divide-y divide-slate-100 pl-4 mt-2">
                        <div class="flex items-center justify-between py-1.5 text-xs text-slate-600">
                            <span>1. Pembelian Barang Masuk / Supplier (Dianggap Tunai):</span>
                            <span class="font-mono font-medium text-red-600">Rp {{ number_format($report['total_purchases'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 text-xs text-slate-600">
                            <span>2. Beban Operasional Bengkel:</span>
                            <span class="font-mono font-medium text-red-600">Rp {{ number_format($report['total_expenses'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Arus Kas Bersih -->
                <div class="p-6 rounded-2xl {{ $report['net_cash_flow'] >= 0 ? 'bg-slate-900 text-white' : 'bg-red-950 text-white' }} shadow-lg flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-amber-400 font-bold block">ARUS KAS BERSIH (NET CASH FLOW)</span>
                        <div class="text-xs text-slate-300 mt-1">Formula: Total Kas Masuk − Total Kas Keluar</div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-mono font-black {{ $report['net_cash_flow'] >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            Rp {{ number_format($report['net_cash_flow'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
