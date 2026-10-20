<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('parts.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Kembali ke Sparepart
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Tambah Sparepart Baru') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <div class="mb-6 p-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-900 text-xs">
                ℹ️ <strong>Ketentuan Modul Stok (BR-S1):</strong> Saldo stok awal sparepart otomatis <strong>0</strong>. Penambahan stok fisik dan penetapan HPP awal hanya boleh dilakukan melalui pencatatan Barang Masuk / Pembelian pada modul stok (Fase 3).
            </div>

            <form method="POST" action="{{ route('parts.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SKU / Part Number -->
                    <div>
                        <label for="sku" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            SKU / Part Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', 'SP-' . strtoupper(Str::random(5))) }}" required
                               placeholder="Contoh: SP-FL-001 atau 6732-71-6112"
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
                        <input type="text" name="category" id="category" value="{{ old('category') }}" required
                               placeholder="Contoh: Filter, Oli & Pelumas, Hidrolik, Undercarriage"
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
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Contoh: Filter Oli Mesin PC200-8 (Komatsu Genuine)" required
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
                        <input type="text" name="unit" id="unit" value="{{ old('unit', 'Pcs') }}" placeholder="Pcs, Set, Liter, Roll" required
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
                        <input type="number" name="sell_price" id="sell_price" min="0" value="{{ old('sell_price') }}" required
                               placeholder="Contoh: 150000"
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
                        <input type="number" name="min_stock" id="min_stock" min="0" value="{{ old('min_stock', 5) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('min_stock') border-red-500 @enderror">
                        <p class="mt-1 text-[11px] text-slate-400">Peringatan saat stok menipis di dashboard.</p>
                        @error('min_stock')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    <label for="is_active" class="text-sm font-semibold text-slate-800">
                        Status Sparepart Aktif (Dapat dipilih dalam order servis & penjualan langsung)
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('parts.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Simpan Sparepart
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
