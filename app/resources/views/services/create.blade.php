<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('services.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Kembali ke Jasa
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Tambah Jasa Baru') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <form method="POST" action="{{ route('services.store') }}" class="space-y-6">
                @csrf

                <!-- Kode Jasa -->
                <div>
                    <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Kode Jasa <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code', 'SRV-' . strtoupper(Str::random(4))) }}" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Jasa -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Jasa / Servis <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Contoh: Overhaul Pompa Hidrolik Utama atau Service Berkala 500 Jam" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tarif Standar (Rp) -->
                <div>
                    <label for="default_price" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Tarif Standar (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="default_price" id="default_price" min="0" value="{{ old('default_price', 0) }}" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('default_price') border-red-500 @enderror">
                    <p class="mt-1 text-[11px] text-slate-400">Tarif default yang berlaku jika pelanggan tidak memiliki tarif khusus (BR-P1).</p>
                    @error('default_price')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    <label for="is_active" class="text-sm font-semibold text-slate-800">
                        Jasa Aktif (Dapat dipilih pada Order Servis)
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('services.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Simpan Jasa
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
