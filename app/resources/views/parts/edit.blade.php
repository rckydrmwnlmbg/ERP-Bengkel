<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('parts.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Kembali ke Sparepart
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Edit Sparepart: ') }} {{ $part->name }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <!-- Cost Price Info Box for Admin -->
            <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-950 text-xs flex items-center justify-between">
                <div>
                    <span class="font-bold block">Harga Pokok Rata-rata (HPP / Avg Cost):</span>
                    <span class="font-mono text-base font-extrabold text-amber-900">
                        Rp{{ number_format($part->avg_cost, 0, ',', '.') }}
                    </span>
                    <span class="text-slate-500 block text-[11px] mt-0.5">Sesuai BR-P4: Harga jual tidak boleh lebih rendah dari HPP kecuali menyertakan alasan override.</span>
                </div>
                <div class="text-right">
                    <span class="font-bold block">Stok Saat Ini:</span>
                    <span class="font-mono text-base font-extrabold text-slate-900">
                        {{ $part->qty_on_hand }} {{ $part->unit }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('parts.update', $part) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SKU / Part Number -->
                    <div>
                        <label for="sku" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            SKU / Part Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $part->sku) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('sku') border-red-500 @enderror">
                        @error('sku')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label for="category" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Kategori Sparepart <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="category" id="category" value="{{ old('category', $part->category) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('category') border-red-500 @enderror">
                        @error('category')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Nama Sparepart -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Lengkap Sparepart <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $part->name) }}" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Satuan Unit -->
                    <div>
                        <label for="unit" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Satuan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="unit" id="unit" value="{{ old('unit', $part->unit) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('unit') border-red-500 @enderror">
                        @error('unit')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Harga Jual -->
                    <div>
                        <label for="sell_price" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Harga Jual Satuan (Rp) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="sell_price" id="sell_price" min="0" value="{{ old('sell_price', $part->sell_price) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('sell_price') border-red-500 @enderror">
                        @error('sell_price')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Stok Minimum -->
                    <div>
                        <label for="min_stock" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Batas Stok Minimum <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="min_stock" id="min_stock" min="0" value="{{ old('min_stock', $part->min_stock) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('min_stock') border-red-500 @enderror">
                        @error('min_stock')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Alasan Override BR-P4 jika harga jual di bawah HPP -->
                <div>
                    <label for="override_reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Alasan Override Harga Jual (Wajib Diisi Jika Harga Jual &lt; HPP)
                    </label>
                    <input type="text" name="override_reason" id="override_reason" value="{{ old('override_reason') }}"
                           placeholder="Contoh: Cuci gudang barang lama / diskon paket kontrak (tercatat di audit log)"
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('override_reason') border-red-500 @enderror">
                    <p class="mt-1 text-[11px] text-slate-400">Jika harga jual diubah, riwayat perubahan akan otomatis tercatat ke Audit Log.</p>
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $part->is_active) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    <label for="is_active" class="text-sm font-semibold text-slate-800">
                        Status Sparepart Aktif
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('parts.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Perbarui Sparepart
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
