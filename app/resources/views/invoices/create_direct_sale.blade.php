<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('invoices.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Faktur & Tagihan
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Penjualan Langsung Toko (Counter Sale)') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @if($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200"
             x-data="{
                 partsList: {{ Js::from($parts) }},
                 rows: [
                     { part_id: '', qty: 1, unit_price: 0, stock: 0 }
                 ],
                 discount: {{ (int) old('discount_amount', 0) }},
                 taxRate: {{ $taxRate }},
                 updatePart(index) {
                     const row = this.rows[index];
                     const selected = this.partsList.find(p => p.id == row.part_id);
                     if (selected) {
                         row.unit_price = selected.sell_price;
                         row.stock = selected.qty_on_hand;
                     } else {
                         row.unit_price = 0;
                         row.stock = 0;
                     }
                 },
                 addRow() {
                     this.rows.push({ part_id: '', qty: 1, unit_price: 0, stock: 0 });
                 },
                 removeRow(index) {
                     if (this.rows.length > 1) {
                         this.rows.splice(index, 1);
                     }
                 },
                 get subtotal() {
                     return this.rows.reduce((sum, r) => sum + (Number(r.qty || 0) * Number(r.unit_price || 0)), 0);
                 },
                 get taxAmount() {
                     const taxable = Math.max(0, this.subtotal - this.discount);
                     return Math.round(taxable * (this.taxRate / 100));
                 },
                 get total() {
                     return Math.max(0, this.subtotal - this.discount) + this.taxAmount;
                 }
             }">

            <div class="mb-6 pb-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Transaksi Penjualan Sparepart di Kasir</h3>
                <p class="text-xs text-slate-500 mt-0.5">Penjualan langsung memotong stok gudang seketika (SALE_OUT, BR-S5) saat faktur diterbitkan.</p>
            </div>

            <form method="POST" action="{{ route('direct-sales.store') }}" class="space-y-6">
                @csrf

                <!-- Header: Customer & Tanggal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Customer / Pembeli <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" id="customer_id" required
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ (string) old('customer_id', $defaultCustomer?->id) === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} (Termin: {{ $c->payment_term_days }} Hari)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="issue_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tanggal Transaksi <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                    </div>
                </div>

                <!-- Dynamic Item Rows -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Daftar Sparepart yang Dibeli <span class="text-red-500">*</span>
                        </label>
                        <button type="button" @click="addRow()" class="inline-flex items-center px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-lg transition shadow-sm">
                            + Tambah Item
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <div class="sm:col-span-6">
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Pilih Sparepart <span x-text="'#' + (index + 1)"></span>
                                    </label>
                                    <select :name="'items[' + index + '][part_id]'" x-model="row.part_id" @change="updatePart(index)" required
                                            class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                        <option value="">-- Pilih Sparepart --</option>
                                        @foreach($parts as $p)
                                            <option value="{{ $p->id }}">
                                                [{{ $p->sku }}] {{ $p->name }} — Stok: {{ $p->qty_on_hand }} {{ $p->unit }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Qty (Stok: <span x-text="row.stock" class="font-mono text-slate-700"></span>)
                                    </label>
                                    <input type="number" :name="'items[' + index + '][qty]'" x-model.number="row.qty" min="1" :max="row.stock || 1" required
                                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">
                                        Subtotal Item
                                    </label>
                                    <div class="py-2 px-3 bg-white border border-slate-200 rounded-lg text-sm font-mono font-bold text-slate-900 text-right"
                                         x-text="'Rp' + new Intl.NumberFormat('id-ID').format((row.qty || 0) * (row.unit_price || 0))">
                                    </div>
                                </div>

                                <div class="sm:col-span-1 text-center">
                                    <button type="button" @click="removeRow(index)" :disabled="rows.length === 1"
                                            class="p-2 text-slate-400 hover:text-red-600 disabled:opacity-30 disabled:hover:text-slate-400 transition" title="Hapus Baris">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Diskon & Ringkasan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                    <div>
                        <label for="discount_amount" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Potongan Diskon Penjualan (Rp)
                        </label>
                        <input type="number" name="discount_amount" id="discount_amount" x-model.number="discount" min="0" :max="subtotal"
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                        <p class="text-[11px] text-slate-400 mt-1">Diskon maksimal sebesar subtotal penjualan.</p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal Belanja:</span>
                            <span class="font-mono font-semibold" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(subtotal)"></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Potongan Diskon:</span>
                            <span class="font-mono text-red-600" x-text="'-Rp' + new Intl.NumberFormat('id-ID').format(discount || 0)"></span>
                        </div>
                        <div class="flex justify-between text-slate-600 items-center">
                            <span>
                                Pajak (PPN):
                                <span class="text-xs text-slate-400 font-mono">({{ $taxRate }}%)</span>
                            </span>
                            <span class="font-mono" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(taxAmount)"></span>
                        </div>
                        <div class="pt-2 border-t border-slate-200 flex justify-between font-black text-slate-900 text-lg">
                            <span>Total Penjualan:</span>
                            <span class="font-mono text-amber-600" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(total)"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('invoices.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Proses Penjualan & Terbitkan Faktur
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
