<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('stock-adjustments.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Penyesuaian Stok
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Form Penyesuaian Stok (Stok Opname)') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-950 text-xs">
                ⚠️ <strong>Aturan Bisnis (BR-S6):</strong> Penyesuaian stok opname fisik <strong>wajib menyertakan alasan</strong> yang jelas (misal: "Selisih opname fisik akhir bulan", "Barang rusak di gudang", atau "Koreksi salah hitung penerimaan") dan seluruh riwayat dicatat di audit log.
            </div>

            <form method="POST" action="{{ route('stock-adjustments.store') }}" class="space-y-6">
                @csrf

                <!-- Pilih Sparepart -->
                <div>
                    <label for="part_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Pilih Sparepart <span class="text-red-500">*</span>
                    </label>
                    <select name="part_id" id="part_id" required
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('part_id') border-red-500 @enderror">
                        <option value="">-- Pilih Sparepart --</option>
                        @foreach($parts as $part)
                            <option value="{{ $part->id }}" {{ old('part_id', $selectedPartId) == $part->id ? 'selected' : '' }}>
                                [{{ $part->sku }}] {{ $part->name }} (Saldo Saat Ini: {{ $part->qty_on_hand }} {{ $part->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('part_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tipe Penyesuaian -->
                    <div>
                        <label for="type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Aksi Koreksi <span class="text-red-500">*</span>
                        </label>
                        <select name="type" id="type" required
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('type') border-red-500 @enderror">
                            <option value="ADJUSTMENT_IN" {{ old('type') === 'ADJUSTMENT_IN' ? 'selected' : '' }}>
                                ➕ Tambah Stok (Fisik Lebih Banyak)
                            </option>
                            <option value="ADJUSTMENT_OUT" {{ old('type') === 'ADJUSTMENT_OUT' ? 'selected' : '' }}>
                                ➖ Kurangi Stok (Fisik Lebih Sedikit / Rusak)
                            </option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kuantitas Penyesuaian -->
                    <div>
                        <label for="qty" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Jumlah Selisih (Unit) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="qty" id="qty" min="1" value="{{ old('qty', 1) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('qty') border-red-500 @enderror">
                        @error('qty')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Alasan Penyesuaian (Wajib BR-S6) -->
                <div>
                    <label for="reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Alasan Penyesuaian Stok (Wajib) <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" id="reason" rows="3" required placeholder="Jelaskan alasan penyesuaian (misal: Hasil opname fisik tanggal 31, ditemukan 2 pcs rusak / tidak tercatat)..."
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('reason') border-red-500 @enderror">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('stock-adjustments.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Simpan Penyesuaian Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
