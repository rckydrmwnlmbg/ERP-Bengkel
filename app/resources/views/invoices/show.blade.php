<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('invoices.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                    &larr; Faktur & Tagihan
                </a>
                <span class="text-slate-300">/</span>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ $invoice->invoice_number }}
                </h2>
                @switch($invoice->status)
                    @case('issued')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            Issued (Belum Bayar)
                        </span>
                        @break
                    @case('partially_paid')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">
                            Partially Paid
                        </span>
                        @break
                    @case('paid')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            Lunas (Paid)
                        </span>
                        @break
                    @case('void')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 text-slate-600 border border-slate-300">
                            Dibatalkan (Void)
                        </span>
                        @break
                @endswitch
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('invoices.print', $invoice) }}" target="_blank"
                   class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Faktur (PDF)
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($invoice->isVoid())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs">
                ⚠️ <strong>FAKTUR DIBATALKAN (VOID):</strong> Invoice ini telah dibatalkan pada {{ $invoice->voided_at?->format('d-m-Y H:i') }} oleh {{ $invoice->voidedBy?->name ?? 'Admin' }}.
                <br>Alasan: <em>"{{ $invoice->void_reason }}"</em>
            </div>
        @endif

        <!-- Customer & Due Date Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-400 block uppercase font-bold">Customer</span>
                <span class="font-bold text-slate-900 text-base block mt-1">{{ $invoice->customer?->name }}</span>
                <span class="text-xs text-slate-500">{{ $invoice->customer?->phone ?? '-' }}</span>
            </div>

            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-400 block uppercase font-bold">Tanggal & Jatuh Tempo</span>
                <span class="text-xs text-slate-600 block mt-1">Terbit: <strong class="font-mono">{{ $invoice->issue_date?->format('d-m-Y') }}</strong></span>
                <span class="text-xs text-slate-600 block mt-0.5">Jatuh Tempo: <strong class="font-mono {{ $invoice->isOverdue() ? 'text-red-600' : '' }}">{{ $invoice->due_date?->format('d-m-Y') }}</strong></span>
                <span class="text-[11px] text-slate-400 block mt-1">Termin: {{ $invoice->customer?->payment_term_days ?? 0 }} Hari</span>
            </div>

            <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-400 block uppercase font-bold">Sumber Transaksi</span>
                @if($invoice->source_type === 'work_order')
                    <span class="font-semibold text-slate-800 text-sm block mt-1">Order Servis (SPK)</span>
                    <a href="{{ route('work-orders.show', $invoice->work_order_id) }}" class="text-xs text-amber-600 hover:underline font-mono">
                        {{ $invoice->workOrder?->wo_number }} &rarr;
                    </a>
                @else
                    <span class="font-semibold text-slate-800 text-sm block mt-1">Penjualan Langsung Toko</span>
                    <span class="text-xs text-slate-400 block mt-0.5">Counter Sale</span>
                @endif
            </div>

            <div class="p-5 rounded-xl bg-amber-50 border border-amber-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs text-amber-800 block uppercase font-bold">Sisa Tagihan (Piutang)</span>
                    <span class="font-mono font-black text-2xl {{ $invoice->remainingBalance() > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                        Rp{{ number_format($invoice->remainingBalance(), 0, ',', '.') }}
                    </span>
                </div>
                <span class="text-[11px] text-amber-700 font-semibold mt-1">Total: Rp{{ number_format($invoice->total, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Table of Items -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">
                    Rincian Tagihan Barang & Jasa
                </h3>
                <span class="text-xs font-semibold text-slate-600">
                    {{ $invoice->items->count() }} Item
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Deskripsi Item</th>
                            <th class="py-3 px-4 text-center">Tipe</th>
                            <th class="py-3 px-4 text-center">Qty</th>
                            <th class="py-3 px-4 text-right">Harga Satuan</th>
                            @can('view-cost-price')
                                <th class="py-3 px-4 text-right bg-amber-50/50 text-amber-900 text-xs">Snapshot HPP</th>
                            @endcan
                            <th class="py-3 px-4 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoice->items as $it)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $it->getItemName() }}
                                    @if($it->part)
                                        <span class="block text-xs font-mono text-slate-400 font-normal">SKU: {{ $it->part->sku }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-xs uppercase text-slate-500">
                                    {{ $it->item_type }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-medium">
                                    {{ $it->qty }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    Rp{{ number_format($it->unit_price, 0, ',', '.') }}
                                </td>
                                @can('view-cost-price')
                                    <td class="py-3 px-4 text-right font-mono text-xs text-amber-900 bg-amber-50/30">
                                        {{ $it->unit_cost_snapshot > 0 ? 'Rp' . number_format($it->unit_cost_snapshot, 0, ',', '.') : '-' }}
                                    </td>
                                @endcan
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp{{ number_format($it->line_total, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Rekapitulasi Pembayaran & Tagihan -->
            <div class="p-6 bg-slate-50 border-t border-slate-200">
                <div class="max-w-md ml-auto space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Item:</span>
                        <span class="font-mono font-semibold">Rp{{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($invoice->discount_amount > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Potongan Diskon:</span>
                            <span class="font-mono text-red-600">-Rp{{ number_format($invoice->discount_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($invoice->tax_amount > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Pajak (PPN):</span>
                            <span class="font-mono">Rp{{ number_format($invoice->tax_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="pt-2 border-t border-slate-300 flex justify-between font-black text-slate-900 text-base">
                        <span>Total Faktur:</span>
                        <span class="font-mono text-amber-600">Rp{{ number_format($invoice->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600 pt-1">
                        <span>Pembayaran Diterima:</span>
                        <span class="font-mono font-bold text-emerald-700">Rp{{ number_format($invoice->totalPaid(), 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-300 flex justify-between font-black text-base {{ $invoice->remainingBalance() > 0 ? 'text-red-600' : 'text-slate-800' }}">
                        <span>Sisa Tagihan:</span>
                        <span class="font-mono">Rp{{ number_format($invoice->remainingBalance(), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION: Riwayat Pembayaran & Form Input Bayar (BR-Y1, BR-Y2) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Payment History (Left 2 Columns) -->
            <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="font-bold text-sm text-slate-800 uppercase tracking-wider">
                        Riwayat Pembayaran Diterima (BR-Y1..Y3)
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100/60 text-slate-600 font-bold border-b border-slate-200 uppercase">
                            <tr>
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3">Metode</th>
                                <th class="py-2.5 px-3">Referensi</th>
                                <th class="py-2.5 px-3 text-right">Nominal</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                                @can('void-financial')
                                    <th class="py-2.5 px-3 text-center">Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($invoice->payments as $p)
                                <tr class="hover:bg-slate-50/80 transition {{ $p->isVoid() ? 'opacity-40 bg-slate-50' : '' }}">
                                    <td class="py-2.5 px-3 font-mono">{{ $p->paid_at?->format('d-m-Y') }}</td>
                                    <td class="py-2.5 px-3 capitalize font-semibold">{{ $p->method }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ $p->reference ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                        Rp{{ number_format($p->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        @if($p->isValid())
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Valid</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800" title="{{ $p->void_reason }}">Void</span>
                                        @endif
                                    </td>
                                    @can('void-financial')
                                        <td class="py-2.5 px-3 text-center">
                                            @if($p->isValid())
                                                <button type="button" onclick="const r = prompt('Alasan pembatalan (void) pembayaran ini (BR-Y2):'); if(r) { document.getElementById('void-payment-{{ $p->id }}-reason').value = r; document.getElementById('void-payment-{{ $p->id }}-form').submit(); }"
                                                        class="text-red-500 hover:text-red-700 font-semibold text-[11px]">
                                                    Void
                                                </button>
                                                <form id="void-payment-{{ $p->id }}-form" method="POST" action="{{ route('payments.void', $p) }}" class="hidden">
                                                    @csrf
                                                    <input type="hidden" name="reason" id="void-payment-{{ $p->id }}-reason">
                                                </form>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" class="py-8 text-center text-slate-400">
                                        Belum ada pembayaran yang dicatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Record Payment Form (Right 1 Column) -->
            <div class="space-y-4">
                @can('payments.manage')
                    @if(!$invoice->isPaid() && !$invoice->isVoid())
                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                            <h3 class="font-bold text-sm text-slate-800 mb-3 flex items-center space-x-1.5">
                                <span>💳 Catat Pembayaran</span>
                            </h3>

                            <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="space-y-4">
                                @csrf

                                <div>
                                    <label for="amount" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Jumlah Bayar (Rp) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="amount" id="amount" value="{{ old('amount', $invoice->remainingBalance()) }}"
                                           min="1" max="{{ $invoice->remainingBalance() }}" required
                                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                                    <p class="text-[11px] text-slate-400 mt-1">Maksimal: Rp{{ number_format($invoice->remainingBalance(), 0, ',', '.') }}</p>
                                </div>

                                <div>
                                    <label for="method" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Metode Pembayaran <span class="text-red-500">*</span>
                                    </label>
                                    <select name="method" id="method" required class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                        <option value="cash">Tunai (Cash)</option>
                                        <option value="transfer">Transfer Bank</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="paid_at" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Tanggal Bayar <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="paid_at" id="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" required
                                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                </div>

                                <div>
                                    <label for="reference" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        No. Bukti / Referensi (Opsional)
                                    </label>
                                    <input type="text" name="reference" id="reference" placeholder="Contoh: Bukti Transfer BCA 1234..."
                                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                </div>

                                <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider rounded-lg shadow transition">
                                    Simpan Pembayaran
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan

                <!-- Void Invoice Panel (Admin Only, BR-I5, BR-I6) -->
                @can('void-financial')
                    @if(!$invoice->isVoid())
                        <div class="bg-white p-5 rounded-xl border border-red-200 shadow-sm">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-red-600 mb-2">Batalkan (Void) Invoice</h4>
                            <p class="text-xs text-slate-500 mb-3">
                                Koreksi invoice hanya bisa dilakukan dengan void (BR-I5). Invoice tidak dapat di-void jika masih memiliki pembayaran valid (BR-I6).
                            </p>

                            <button type="button" onclick="const r = prompt('Masukkan alasan pembatalan (void) invoice ini (wajib, BR-I5):'); if(r) { document.getElementById('void-invoice-reason').value = r; document.getElementById('void-invoice-form').submit(); }"
                                    class="w-full py-2 px-3 bg-red-100 hover:bg-red-200 text-red-800 font-bold text-xs uppercase tracking-wider rounded-lg transition border border-red-300">
                                Batalkan (Void) Faktur
                            </button>
                            <form id="void-invoice-form" method="POST" action="{{ route('invoices.void', $invoice) }}" class="hidden">
                                @csrf
                                <input type="hidden" name="reason" id="void-invoice-reason">
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
