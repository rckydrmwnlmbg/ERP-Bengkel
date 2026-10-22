<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('work-orders.show', $workOrder) }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; {{ $workOrder->wo_number }}
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Terbitkan Faktur Invoice dari Order Servis') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200"
             x-data="{
                 subtotal: {{ $workOrder->calculateTotal() }},
                 discount: {{ (int) old('discount_amount', 0) }},
                 taxRate: {{ $taxRate }},
                 get taxAmount() {
                     const taxable = Math.max(0, this.subtotal - this.discount);
                     return Math.round(taxable * (this.taxRate / 100));
                 },
                 get total() {
                     return Math.max(0, this.subtotal - this.discount) + this.taxAmount;
                 }
             }">

            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <span class="text-xs uppercase tracking-wider text-amber-600 font-bold block">Surat Perintah Kerja #{{ $workOrder->wo_number }}</span>
                    <h3 class="text-lg font-black text-slate-900 mt-0.5">Customer: {{ $workOrder->customer?->name }}</h3>
                    <p class="text-xs text-slate-500">Unit: {{ $workOrder->equipmentUnit?->brand }} {{ $workOrder->equipmentUnit?->model }} ({{ $workOrder->equipmentUnit?->unit_code ?? '-' }})</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-semibold">Termin Bayar</span>
                    <span class="text-sm font-bold text-slate-800">{{ $workOrder->customer?->payment_term_days ?? 0 }} Hari</span>
                </div>
            </div>

            <!-- Item List Preview (BR-I4: Salin item tanpa mutasi stok baru) -->
            <div class="mb-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Item yang Akan Disalin ke Faktur ({{ $workOrder->items->count() }} Item)</h4>
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Item</th>
                                <th class="py-2.5 px-3 text-center">Tipe</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Harga</th>
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($workOrder->items as $it)
                                <tr>
                                    <td class="py-2.5 px-3 font-semibold text-slate-800">{{ $it->getItemName() }}</td>
                                    <td class="py-2.5 px-3 text-center text-slate-500 uppercase font-mono">{{ $it->item_type }}</td>
                                    <td class="py-2.5 px-3 text-center font-mono">{{ $it->qty }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono">Rp{{ number_format($it->unit_price, 0, ',', '.') }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">Rp{{ number_format($it->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200 font-bold">
                            <tr>
                                <td colspan="4" class="py-2.5 px-3 text-right">Subtotal:</td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-900">
                                    Rp{{ number_format($workOrder->calculateTotal(), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="text-[11px] text-slate-400 mt-1.5 italic">
                    * Catatan: Sesuai BR-I4, penerbitan invoice dari order servis menyalin rincian item pekerjaan dan snapshot HPP tanpa memotong stok gudang kembali.
                </p>
            </div>

            <form method="POST" action="{{ route('invoices.store-from-wo', $workOrder) }}" class="space-y-6 pt-4 border-t border-slate-100">
                @csrf

                <!-- Tanggal Terbit -->
                <div>
                    <label for="issue_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Tanggal Terbit Faktur
                    </label>
                    <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <!-- Input Diskon -->
                <div>
                    <label for="discount_amount" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Diskon Potongan (Rp)
                    </label>
                    <input type="number" name="discount_amount" id="discount_amount" x-model.number="discount" min="0" :max="subtotal"
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Diskon maksimal sebesar subtotal (Rp{{ number_format($workOrder->calculateTotal(), 0, ',', '.') }}).</p>
                </div>

                <!-- Perhitungan Pajak & Total Ringkasan -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-mono font-semibold" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(subtotal)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Potongan Diskon:</span>
                        <span class="font-mono text-red-600" x-text="'-Rp' + new Intl.NumberFormat('id-ID').format(discount || 0)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600 items-center">
                        <span>
                            Pajak (PPN):
                            <span class="text-xs text-slate-400 font-mono">({{ $taxRate }}% dari Pengaturan)</span>
                        </span>
                        <span class="font-mono" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(taxAmount)"></span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between font-black text-slate-900 text-base sm:text-lg">
                        <span>Total Tagihan:</span>
                        <span class="font-mono text-amber-600" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(total)"></span>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Terbitkan Invoice Resmi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
