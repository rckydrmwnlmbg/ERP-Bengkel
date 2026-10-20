<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    Laporan Piutang &amp; Aging (BR-R6, BR-R7)
                </h2>
                <p class="text-xs text-slate-500 mt-1">Daftar saldo piutang tak terbayar dan klasifikasi umur piutang per tanggal laporan</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.export-excel', ['type' => 'receivables', 'as_of_date' => $asOfDate]) }}" 
                   class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    📊 Ekspor Excel
                </a>
                <a href="{{ route('reports.print', ['type' => 'receivables', 'as_of_date' => $asOfDate]) }}" target="_blank"
                   class="inline-flex items-center px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow transition">
                    🖨️ Cetak / PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- As Of Date Filter -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between flex-wrap gap-4">
            <form method="GET" action="{{ route('reports.receivables') }}" class="flex items-center space-x-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Posisi Piutang Per Tanggal</label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div class="pt-5">
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition">
                        Tampilkan
                    </button>
                </div>
            </form>
            <div class="text-right">
                <span class="text-xs text-slate-400 block font-bold uppercase tracking-wider">Total Piutang Berjalan (BR-R6)</span>
                <span class="text-2xl font-black font-mono text-amber-600">Rp {{ number_format($report['total_receivables'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Aging Buckets Matrix (BR-R7) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">
                    Analisis Umur Piutang (Aging Buckets - BR-R7)
                </h3>
                <span class="text-xs text-slate-500">Berdasarkan selisih hari tanggal jatuh tempo (due date)</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <!-- Belum Jatuh Tempo -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase block">Belum Jatuh Tempo (≤0)</span>
                    <div class="text-lg font-black font-mono text-slate-900 mt-1">
                        Rp {{ number_format($report['aging']['not_due'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- 1 - 30 Hari -->
                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50">
                    <span class="text-[11px] font-bold text-amber-800 uppercase block">1 – 30 Hari</span>
                    <div class="text-lg font-black font-mono text-amber-900 mt-1">
                        Rp {{ number_format($report['aging']['days_1_30'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- 31 - 60 Hari -->
                <div class="p-4 rounded-xl border border-orange-200 bg-orange-50/50">
                    <span class="text-[11px] font-bold text-orange-800 uppercase block">31 – 60 Hari</span>
                    <div class="text-lg font-black font-mono text-orange-900 mt-1">
                        Rp {{ number_format($report['aging']['days_31_60'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- 61 - 90 Hari -->
                <div class="p-4 rounded-xl border border-red-200 bg-red-50/40">
                    <span class="text-[11px] font-bold text-red-800 uppercase block">61 – 90 Hari</span>
                    <div class="text-lg font-black font-mono text-red-900 mt-1">
                        Rp {{ number_format($report['aging']['days_61_90'], 0, ',', '.') }}
                    </div>
                </div>

                <!-- > 90 Hari -->
                <div class="p-4 rounded-xl border border-red-300 bg-red-100/50">
                    <span class="text-[11px] font-bold text-red-900 uppercase block">&gt; 90 Hari (Macet)</span>
                    <div class="text-lg font-black font-mono text-red-950 mt-1">
                        Rp {{ number_format($report['aging']['days_over_90'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Rekap Piutang per Pelanggan (BR-R6, BR-R8) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">
                    Rekap Piutang per Customer (BR-R6)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Kode</th>
                            <th class="py-3 px-4">Nama Pelanggan</th>
                            <th class="py-3 px-4 text-center">Faktur Belum Lunas</th>
                            <th class="py-3 px-4 text-right">Total Piutang</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($report['customer_summary'] as $row)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4 font-mono text-xs font-bold text-slate-500">
                                    {{ $row['customer']->code }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $row['customer']->name }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-semibold">
                                    {{ $row['invoice_count'] }} faktur
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-black text-amber-700">
                                    Rp {{ number_format($row['total_receivable'], 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="{{ route('customers.show', $row['customer']) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                                        Lihat Customer &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    Tidak ada piutang pelanggan pada tanggal ini. Seluruh tagihan telah lunas!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-100 border-t-2 border-slate-300 font-bold text-slate-900">
                        <tr>
                            <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider text-xs">Total Seluruh Piutang:</td>
                            <td class="py-3 px-4 text-right font-mono text-base font-black text-amber-700">
                                Rp {{ number_format($report['total_receivables'], 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
