<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faktur Penjualan - {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; font-size: 12px; }
            .print-border { border: 1px solid #94a3b8 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-4 sm:p-8">

    <!-- Floating Print Button (Hidden during print) -->
    <div class="max-w-3xl mx-auto mb-4 flex items-center justify-between no-print">
        <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali ke Detail Faktur
        </a>
        <button onclick="window.print()" class="inline-flex items-center space-x-1.5 px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg shadow transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Cetak / Simpan PDF</span>
        </button>
    </div>

    <!-- Invoice Sheet -->
    <div class="max-w-3xl mx-auto bg-white p-8 sm:p-12 rounded-xl shadow border border-slate-200">
        <!-- DEMO Banner Header -->
        <div class="text-center pb-4 mb-6 border-b border-slate-200">
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                FAKTUR RESMI SISTEM ERP BENGKEL &amp; TOKO SPAREPART
            </span>
            <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $shopName }}</h1>
            <p class="text-xs text-slate-500">Spesialis Servis Alat Berat, Overhaul Engine, Hidrolik &amp; Suku Cadang Asli</p>
            <div class="mt-2 text-sm font-bold uppercase tracking-widest text-amber-600">FAKTUR PENJUALAN / INVOICE</div>
        </div>

        <!-- Invoice & Customer Meta -->
        <div class="grid grid-cols-2 gap-6 mb-8 text-xs">
            <div>
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">Ditagihkan Kepada:</span>
                <div class="text-sm font-black text-slate-900">{{ $invoice->customer?->name }}</div>
                <div class="text-slate-600 mt-0.5">{{ $invoice->customer?->address ?? '-' }}</div>
                <div class="text-slate-600 mt-0.5">Telp: {{ $invoice->customer?->phone ?? '-' }}</div>
                @if($invoice->workOrder && $invoice->workOrder->equipmentUnit)
                    <div class="mt-2 text-slate-700 bg-slate-50 p-2 rounded border border-slate-200">
                        Unit: <strong>{{ $invoice->workOrder->equipmentUnit->brand }} {{ $invoice->workOrder->equipmentUnit->model }}</strong>
                        <span class="block text-slate-500 font-mono">No. Seri: {{ $invoice->workOrder->equipmentUnit->serial_number }} ({{ $invoice->workOrder->equipmentUnit->unit_code }})</span>
                    </div>
                @endif
            </div>

            <div class="text-right">
                <div class="text-lg font-black font-mono text-amber-600">{{ $invoice->invoice_number }}</div>
                <div class="text-slate-500 mt-1">Tanggal Terbit: <strong class="font-mono text-slate-800">{{ $invoice->issue_date?->format('d/m/Y') }}</strong></div>
                <div class="text-slate-500 mt-0.5">Jatuh Tempo: <strong class="font-mono text-slate-800">{{ $invoice->due_date?->format('d/m/Y') }}</strong></div>
                <div class="text-slate-500 mt-0.5">Termin Pembayaran: {{ $invoice->customer?->payment_term_days ?? 0 }} Hari</div>
                <div class="mt-2">
                    <span class="inline-block px-2.5 py-0.5 rounded text-xs font-bold uppercase font-mono
                          {{ $invoice->isPaid() ? 'bg-emerald-100 text-emerald-800' : ($invoice->isVoid() ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                        Status: {{ $invoice->status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Table of Items -->
        <table class="w-full text-left text-xs border-collapse mb-6">
            <thead>
                <tr class="bg-slate-100 border-y border-slate-300 text-slate-700 uppercase font-bold">
                    <th class="py-2.5 px-3">No.</th>
                    <th class="py-2.5 px-3">Uraian / Deskripsi Pekerjaan &amp; Sparepart</th>
                    <th class="py-2.5 px-3 text-center">Qty</th>
                    <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                    <th class="py-2.5 px-3 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($invoice->items as $idx => $it)
                    <tr>
                        <td class="py-2.5 px-3 font-mono text-slate-500 text-center">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-3">
                            <strong class="text-slate-900">{{ $it->getItemName() }}</strong>
                            @if($it->isPart() && $it->part)
                                <span class="block text-[11px] text-slate-400 font-mono">SKU: {{ $it->part->sku }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-center font-mono">{{ $it->qty }}</td>
                        <td class="py-2.5 px-3 text-right font-mono">Rp{{ number_format($it->unit_price, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">Rp{{ number_format($it->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Totals -->
        <div class="flex justify-end mb-8 text-xs">
            <div class="w-64 space-y-1.5 border-t border-slate-300 pt-2">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold">Rp{{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($invoice->discount_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Diskon:</span>
                        <span class="font-mono text-red-600">-Rp{{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($invoice->tax_amount > 0)
                    <div class="flex justify-between text-slate-600">
                        <span>Pajak:</span>
                        <span class="font-mono">Rp{{ number_format($invoice->tax_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-sm font-black text-slate-900 pt-1 border-t border-slate-300">
                    <span>TOTAL:</span>
                    <span class="font-mono text-amber-600">Rp{{ number_format($invoice->total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-600 pt-1">
                    <span>Sudah Dibayar:</span>
                    <span class="font-mono font-bold text-emerald-700">Rp{{ number_format($invoice->totalPaid(), 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm font-black text-slate-900 pt-1 border-t border-slate-300">
                    <span>SISA TAGIHAN:</span>
                    <span class="font-mono text-red-600">Rp{{ number_format($invoice->remainingBalance(), 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="grid grid-cols-2 gap-8 text-center text-xs mt-12 pt-8 border-t border-slate-200">
            <div>
                <span class="text-slate-500">Tanda Terima Pelanggan,</span>
                <div class="h-16"></div>
                <div class="font-bold text-slate-800 underline">({{ $invoice->customer?->name }})</div>
            </div>
            <div>
                <span class="text-slate-500">Hormat Kami,</span>
                <div class="h-16"></div>
                <div class="font-bold text-slate-800 underline">{{ $invoice->creator?->name ?? 'Kasir Bengkel' }}</div>
            </div>
        </div>

        @if(config('app.demo', true))
            <div class="mt-8 pt-4 border-t border-slate-200 text-center text-[10px] text-slate-400 uppercase tracking-widest font-semibold">
                DOKUMEN SIMULASI ERP &bull; SELURUH DATA TRANSAKSI FIKTIF
            </div>
        @endif
    </div>

</body>
</html>
