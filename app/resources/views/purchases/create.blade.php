<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('purchases.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Barang Masuk
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Catat Barang Masuk / Pembelian') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto" x-data="purchaseForm()">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <div class="mb-6 p-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-900 text-xs">
                ℹ️ <strong>Ketentuan Stok & HPP (BR-S3 & BR-S7):</strong> Setiap item sparepart yang dicatat di form ini akan otomatis menambah saldo stok fisik (`qty_on_hand`) dan memperbarui harga pokok rata-rata tertimbang (`avg_cost`) sesuai rumus WAC.
            </div>

            <form method="POST" action="{{ route('purchases.store') }}" class="space-y-6">
                @csrf

                <!-- Header Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="supplier_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Pemasok / Supplier <span class="text-red-500">*</span>
                        </label>
                        <select name="supplier_id" id="supplier_id" required
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('supplier_id') border-red-500 @enderror">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="purchase_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tanggal Pembelian / Masuk <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('purchase_date') border-red-500 @enderror">
                        @error('purchase_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            No. Surat Jalan / Faktur Supplier
                        </label>
                        <input type="text" name="reference" id="reference" value="{{ old('reference') }}" placeholder="Contoh: SJ-2026/089 atau INV-SUP-01"
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('reference') border-red-500 @enderror">
                        @error('reference')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Daftar Item Barang Masuk -->
                <div class="pt-6 border-t border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Daftar Item Sparepart</h3>
                        <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg transition">
                            + Tambah Baris
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                                    <th class="py-2.5 px-3">Sparepart</th>
                                    <th class="py-2.5 px-3 w-28 text-center">Kuantitas</th>
                                    <th class="py-2.5 px-3 w-40 text-right">Harga Beli Satuan (Rp)</th>
                                    <th class="py-2.5 px-3 w-40 text-right">Subtotal (Rp)</th>
                                    <th class="py-2.5 px-3 w-16 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2.5">
                                            <select :name="'items[' + index + '][part_id]'" x-model="item.part_id" required
                                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                                <option value="">-- Pilih Sparepart --</option>
                                                @foreach($parts as $part)
                                                    <option value="{{ $part->id }}">
                                                        [{{ $part->sku }}] {{ $part->name }} (Stok saat ini: {{ $part->qty_on_hand }} {{ $part->unit }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="p-2.5">
                                            <input type="number" :name="'items[' + index + '][qty]'" x-model.number="item.qty" min="1" required
                                                   class="w-full text-xs text-center rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                                        </td>
                                        <td class="p-2.5">
                                            <input type="number" :name="'items[' + index + '][unit_cost]'" x-model.number="item.unit_cost" min="0" required
                                                   placeholder="0"
                                                   class="w-full text-xs text-right rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                                        </td>
                                        <td class="p-2.5 text-right font-mono font-bold text-slate-800">
                                            <span x-text="formatRupiah(item.qty * item.unit_cost)"></span>
                                        </td>
                                        <td class="p-2.5 text-center">
                                            <button type="button" @click="removeItem(index)" :disabled="items.length <= 1"
                                                    class="p-1 rounded text-red-500 hover:bg-red-50 disabled:opacity-30 disabled:pointer-events-none">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 border-t border-slate-200">
                                    <td colspan="3" class="py-3 px-4 font-bold text-slate-700 text-right uppercase text-xs">
                                        Total Nilai Pembelian:
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-black text-slate-950 text-sm">
                                        <span x-text="formatRupiah(calculateGrandTotal())"></span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('purchases.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Simpan &amp; Masukkan ke Stok
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function purchaseForm() {
            return {
                items: [
                    { part_id: '', qty: 1, unit_cost: 0 }
                ],
                addItem() {
                    this.items.push({ part_id: '', qty: 1, unit_cost: 0 });
                },
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },
                calculateGrandTotal() {
                    return this.items.reduce((total, item) => {
                        return total + ((Number(item.qty) || 0) * (Number(item.unit_cost) || 0));
                    }, 0);
                },
                formatRupiah(number) {
                    return 'Rp' + Number(number || 0).toLocaleString('id-ID');
                }
            }
        }
    </script>
</x-app-layout>
