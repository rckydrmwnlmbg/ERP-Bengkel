<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('suppliers.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Kembali ke Supplier
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Edit Supplier: ') }} {{ $supplier->name }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Nama Supplier -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Supplier / Distributor <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $supplier->name) }}" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kontak Telepon -->
                <div>
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nomor Telepon / WhatsApp
                    </label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $supplier->phone) }}"
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alamat -->
                <div>
                    <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Alamat Kantor / Gudang Pemasok
                    </label>
                    <textarea name="address" id="address" rows="3"
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('address') border-red-500 @enderror">{{ old('address', $supplier->address) }}</textarea>
                    @error('address')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('suppliers.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Perbarui Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
