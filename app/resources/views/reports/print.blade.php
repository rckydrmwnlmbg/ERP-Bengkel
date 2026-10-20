<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan - {{ strtoupper($type) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; font-size: 11px; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-4 sm:p-8">

    <!-- Action Bar -->
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print">
        <button onclick="window.history.back()" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali
        </button>
        <button onclick="window.print()" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg shadow transition">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-xl shadow border border-slate-200">
        <!-- Header -->
        <div class="text-center pb-4 mb-6 border-b-2 border-slate-900">
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                SISTEM INFORMASI MANAJEMEN ERP BENGKEL &amp; TOKO SPAREPART
            </span>
            <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $shopName }}</h1>
            <p class="text-xs text-slate-500">Spesialis Servis Alat Berat, Overhaul Engine, Hidrolik &amp; Suku Cadang Asli</p>
            <div class="mt-3 text-base font-black uppercase tracking-wider text-amber-700">
                @switch($type)
                    @case('revenue')
                        LAPORAN OMZET PENJUALAN (BR-R1)
                        @break
                    @case('profit-loss')
                        LAPORAN LABA RUGI SEDERHANA (BR-R2, BR-R3)
                        @break
                    @case('cash-flow')
                        LAPORAN ARUS KAS (BR-R4, BR-R5)
                        @break
                    @case('receivables')
                        LAPORAN PIUTANG &amp; AGING (BR-R6, BR-R7)
                        @break
                @endswitch
            </div>
            <p class="text-xs text-slate-600 mt-1">
                @if($asOfDate)
                    Posisi per tanggal: <strong>{{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}</strong>
                @else
                    Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</strong>
                @endif
                | Dicetak: {{ now()->translatedFormat('d/m/Y H:i') }}
            </p>
        </div>

        <!-- Content depending on type -->
        @if($type === 'revenue')
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-xs p-3 bg-slate-50 rounded border border-slate-200">
                    <div>
                        <span class="text-slate-500">Total Faktur Terbit:</span>
                        <span class="font-bold font-mono">{{ $data['invoice_count'] }} Faktur</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500">Total Omzet:</span>
                        <span class="font-bold font-mono text-sm text-slate-900">Rp {{ number_format($data['total_revenue'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <table class="w-full text-left text-xs border-collapse border border-slate-300">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300 font-bold uppercase">
                            <th class="p-2 border border-slate-300">No. Faktur</th>
                            <th class="p-2 border border-slate-300">Tanggal</th>
                            <th class="p-2 border border-slate-300">Customer</th>
                            <th class="p-2 border border-slate-300">Sumber</th>
                            <th class="p-2 border border-slate-300 text-right">Subtotal</th>
                            <th class="p-2 border border-slate-300 text-right">Diskon</th>
                            <th class="p-2 border border-slate-300 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['invoices'] as $inv)
                            <tr>
                                <td class="p-2 border border-slate-300 font-mono">{{ $inv->invoice_number }}</td>
                                <td class="p-2 border border-slate-300 font-mono">{{ $inv->issue_date->format('d/m/Y') }}</td>
                                <td class="p-2 border border-slate-300">{{ $inv->customer?->name ?? '-' }}</td>
                                <td class="p-2 border border-slate-300">{{ $inv->source_type === 'work_order' ? 'Servis' : 'Toko' }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($inv->subtotal, 0, ',', '.') }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono">{{ number_format($inv->discount_amount, 0, ',', '.') }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-bold">{{ number_format($inv->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold">
                            <td colspan="6" class="p-2 border border-slate-300 text-right uppercase">Total Omzet:</td>
                            <td class="p-2 border border-slate-300 text-right font-mono font-black text-amber-800">
                                Rp {{ number_format($data['total_revenue'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @elseif($type === 'profit-loss')
            <div class="space-y-4">
                <div class="p-3 bg-amber-50 rounded border border-amber-300 text-xs text-amber-900">
                    <strong>Catatan Penyederhanaan (BR-R2):</strong> Jasa dianggap berbiaya HPP Rp 0. HPP hanya dihitung dari pemakaian sparepart berdasarkan snapshot harga pokok saat transaksi.
                </div>

                <div class="space-y-3 text-xs border border-slate-300 rounded p-4">
                    <div class="flex justify-between py-1 border-b border-slate-200 font-bold">
                        <span>1. Pendapatan Penjualan (Omzet):</span>
                        <span class="font-mono">Rp {{ number_format($data['total_revenue'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-200">
                        <span>2. Harga Pokok Penjualan (HPP Sparepart):</span>
                        <span class="font-mono text-red-700">(Rp {{ number_format($data['total_hpp'], 0, ',', '.') }})</span>
                    </div>
                    <div class="flex justify-between py-1.5 bg-slate-50 px-2 font-bold text-slate-900">
                        <span>LABA KOTOR (Omzet − HPP):</span>
                        <span class="font-mono font-black">Rp {{ number_format($data['gross_profit'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-200">
                        <span>3. Beban Operasional:</span>
                        <span class="font-mono text-red-700">(Rp {{ number_format($data['total_expenses'], 0, ',', '.') }})</span>
                    </div>
                    <div class="flex justify-between py-2 bg-slate-100 px-2 font-black text-sm">
                        <span>LABA BERSIH SEDERHANA:</span>
                        <span class="font-mono text-amber-800">Rp {{ number_format($data['net_profit'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        @elseif($type === 'cash-flow')
            <div class="space-y-4">
                <div class="p-3 bg-amber-50 rounded border border-amber-300 text-xs text-amber-900">
                    <strong>Catatan Penyederhanaan (BR-R5):</strong> Pembelian supplier dianggap tunai di tanggal pembelian; hutang supplier di luar scope.
                </div>

                <div class="space-y-3 text-xs border border-slate-300 rounded p-4">
                    <div class="flex justify-between py-1.5 border-b border-emerald-300 font-bold text-emerald-900">
                        <span>A. Arus Kas Masuk (Penerimaan Pelanggan):</span>
                        <span class="font-mono">Rp {{ number_format($data['cash_in'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-200 pl-4">
                        <span>- Pembelian Barang Masuk Supplier (Tunai):</span>
                        <span class="font-mono text-red-700">(Rp {{ number_format($data['total_purchases'], 0, ',', '.') }})</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-200 pl-4">
                        <span>- Beban Operasional:</span>
                        <span class="font-mono text-red-700">(Rp {{ number_format($data['total_expenses'], 0, ',', '.') }})</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-red-300 font-bold text-red-900">
                        <span>B. Total Arus Kas Keluar:</span>
                        <span class="font-mono">(Rp {{ number_format($data['cash_out'], 0, ',', '.') }})</span>
                    </div>
                    <div class="flex justify-between py-2 bg-slate-100 px-2 font-black text-sm">
                        <span>ARUS KAS BERSIH:</span>
                        <span class="font-mono text-amber-800">Rp {{ number_format($data['net_cash_flow'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        @elseif($type === 'receivables')
            <div class="space-y-4">
                <div class="grid grid-cols-5 gap-2 text-[10px] text-center border border-slate-300 rounded p-2 bg-slate-50">
                    <div>
                        <div class="font-bold text-slate-500 uppercase">Belum JT</div>
                        <div class="font-mono font-bold mt-0.5">Rp {{ number_format($data['aging']['not_due'], 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="font-bold text-amber-600 uppercase">1–30 Hari</div>
                        <div class="font-mono font-bold mt-0.5">Rp {{ number_format($data['aging']['days_1_30'], 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="font-bold text-orange-600 uppercase">31–60 Hari</div>
                        <div class="font-mono font-bold mt-0.5">Rp {{ number_format($data['aging']['days_31_60'], 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="font-bold text-red-600 uppercase">61–90 Hari</div>
                        <div class="font-mono font-bold mt-0.5">Rp {{ number_format($data['aging']['days_61_90'], 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="font-bold text-red-800 uppercase">&gt; 90 Hari</div>
                        <div class="font-mono font-bold mt-0.5">Rp {{ number_format($data['aging']['days_over_90'], 0, ',', '.') }}</div>
                    </div>
                </div>

                <table class="w-full text-left text-xs border-collapse border border-slate-300">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300 font-bold uppercase">
                            <th class="p-2 border border-slate-300">Kode</th>
                            <th class="p-2 border border-slate-300">Nama Pelanggan</th>
                            <th class="p-2 border border-slate-300 text-center">Jml Faktur</th>
                            <th class="p-2 border border-slate-300 text-right">Saldo Piutang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['customer_summary'] as $row)
                            <tr>
                                <td class="p-2 border border-slate-300 font-mono">{{ $row['customer']->code }}</td>
                                <td class="p-2 border border-slate-300 font-bold">{{ $row['customer']->name }}</td>
                                <td class="p-2 border border-slate-300 text-center font-mono">{{ $row['invoice_count'] }}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-bold">
                                    Rp {{ number_format($row['total_receivable'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold">
                            <td colspan="3" class="p-2 border border-slate-300 text-right uppercase">Total Piutang:</td>
                            <td class="p-2 border border-slate-300 text-right font-mono font-black text-amber-800">
                                Rp {{ number_format($data['total_receivables'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        <!-- Signature Footer -->
        <div class="mt-12 pt-6 border-t border-slate-300 grid grid-cols-2 text-center text-xs">
            <div>
                <p class="text-slate-500">Dibuat Oleh,</p>
                <div class="h-14"></div>
                <p class="font-bold border-t border-slate-400 inline-block px-8 pt-1">Bagian Keuangan / Kasir</p>
            </div>
            <div>
                <p class="text-slate-500">Mengetahui,</p>
                <div class="h-14"></div>
                <p class="font-bold border-t border-slate-400 inline-block px-8 pt-1">Pimpinan / Pemilik Bengkel</p>
            </div>
        </div>

        @if(config('app.demo', true))
            <div class="mt-8 pt-4 border-t border-slate-200 text-center text-[10px] text-slate-400 uppercase tracking-widest font-semibold">
                ⚠️ LAPORAN DEMO PORTOFOLIO ERP — SELURUH DATA &amp; NILAI KEUANGAN FIKTIF
            </div>
        @endif
    </div>
</body>
</html>
