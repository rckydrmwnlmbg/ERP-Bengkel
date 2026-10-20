<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Laporan Laba Rugi Sederhana (BR-R2, BR-R3)
                </h2>
                <p class="text-xs text-slate-500 mt-1">Perhitungan omzet, harga pokok penjualan (HPP), laba kotor, dan laba bersih operasional</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.export-excel', ['type' => 'profit-loss', 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    📊 Ekspor Excel
                </a>
                <a href="{{ route('reports.print', ['type' => 'profit-loss', 'start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank"
                   class="inline-flex items-center px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    🖨️ Cetak / PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Catatan Penyederhanaan BR-R2 Alert -->
        <div class="p-4 rounded-2xl bg-amber-50 border-2 border-amber-300 text-amber-900 shadow-sm flex items-start space-x-3">
            <span class="text-xl">💡</span>
            <div>
                <h4 class="font-bold text-xs uppercase tracking-wider text-amber-900">Catatan Penyederhanaan Aturan Bisnis (BR-R2)</h4>
                <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                    {{ $report['simplification_notes']['br_r2'] }}
                    HPP hanya dihitung dari item sparepart berdasarkan snapshot harga pokok saat transaksi dibuat.
                </p>
            </div>
        </div>

        <!-- Date Filter Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pilihan Periode Cepat:</span>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <a href="{{ route('reports.profit-loss', ['preset' => 'today']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'today' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Hari Ini</a>
                    <a href="{{ route('reports.profit-loss', ['preset' => 'this_month']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'this_month' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Bulan Ini</a>
                    <a href="{{ route('reports.profit-loss', ['preset' => 'last_month']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'last_month' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Bulan Lalu</a>
                    <a href="{{ route('reports.profit-loss', ['preset' => 'this_year']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ $preset === 'this_year' ? 'bg-amber-500 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Tahun Ini</a>
                </div>
            </div>

            <form method="GET" action="{{ route('reports.profit-loss') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
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
                    <a href="{{ route('reports.profit-loss') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Profit & Loss Statement Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="font-black text-base uppercase tracking-wider">Laporan Laba Rugi Sederhana</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                </div>
                <span class="px-3 py-1 bg-amber-500 text-slate-950 font-bold text-xs uppercase rounded-lg">
                    Rupiah (IDR)
                </span>
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                <!-- Section 1: Pendapatan -->
                <div>
                    <div class="flex items-center justify-between py-2 border-b-2 border-slate-800 text-slate-900 font-bold">
                        <span class="text-sm uppercase tracking-wider">1. Pendapatan Penjualan (Omzet)</span>
                        <span class="font-mono text-base font-black">Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Section 2: HPP -->
                <div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-200 text-slate-800">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-semibold">2. Harga Pokok Penjualan (HPP Sparepart)</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold">BR-R2</span>
                        </div>
                        <span class="font-mono text-sm font-bold text-red-600">(Rp {{ number_format($report['total_hpp'], 0, ',', '.') }})</span>
                    </div>
                    <div class="flex items-center justify-between py-1 text-xs text-slate-500 italic pl-4">
                        <span>Biaya Tenaga Kerja / Jasa Servis:</span>
                        <span>Rp 0 (Penyederhanaan Demo BR-R2)</span>
                    </div>
                </div>

                <!-- Section 3: Laba Kotor -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-slate-500 font-bold block">LABA KOTOR (Omzet − HPP)</span>
                        <span class="text-[11px] text-slate-400">BR-R3: Margin kotor sebelum beban operasional</span>
                    </div>
                    <span class="font-mono text-xl font-black {{ $report['gross_profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                        Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
                    </span>
                </div>

                <!-- Section 4: Beban Operasional -->
                <div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-200 text-slate-900 font-bold">
                        <span class="text-sm uppercase tracking-wider">3. Beban Operasional Bengkel</span>
                        <span class="font-mono text-sm font-bold text-red-600">(Rp {{ number_format($report['total_expenses'], 0, ',', '.') }})</span>
                    </div>
                    <div class="divide-y divide-slate-100 pl-4 mt-2">
                        @forelse($report['expenses_by_category'] as $catExpense)
                            <div class="flex items-center justify-between py-1.5 text-xs text-slate-600">
                                <span>{{ $catExpense['category'] }} ({{ $catExpense['count'] }} transaksi):</span>
                                <span class="font-mono font-medium">Rp {{ number_format($catExpense['total'], 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="py-2 text-xs text-slate-400 italic">Tidak ada catatan beban operasional pada periode ini.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Section 5: Laba Bersih Sederhana -->
                <div class="p-6 rounded-2xl {{ $report['net_profit'] >= 0 ? 'bg-emerald-950 text-white' : 'bg-red-950 text-white' }} shadow-lg flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-amber-400 font-bold block">LABA BERSIH SEDERHANA (BR-R3)</span>
                        <div class="text-xs text-slate-300 mt-1">Formula: Laba Kotor − Beban Operasional</div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-mono font-black {{ $report['net_profit'] >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                            Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
