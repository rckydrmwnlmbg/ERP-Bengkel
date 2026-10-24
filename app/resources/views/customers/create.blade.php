<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('customers.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Kembali ke Pelanggan
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Tambah Pelanggan Baru') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <form method="POST" action="{{ route('customers.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Kode Pelanggan -->
                    <div>
                        <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Kode Pelanggan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', 'CUST-' . strtoupper(Str::random(4))) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono @error('code') border-red-500 @enderror">
                        @error('code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tipe Pelanggan -->
                    <div>
                        <label for="type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tipe Pelanggan <span class="text-red-500">*</span>
                        </label>
                        <select name="type" id="type" required
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('type') border-red-500 @enderror">
                            <option value="company" {{ old('type') === 'company' ? 'selected' : '' }}>Perusahaan / Badan Usaha</option>
                            <option value="individual" {{ old('type') === 'individual' ? 'selected' : '' }}>Perorangan / Pribadi</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Nama Pelanggan -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Lengkap Pelanggan / Perusahaan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Contoh: PT Kalimantan Tambang Makmur atau Bpk. H. Ahmad" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Telepon -->
                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            No. Telepon / WhatsApp
                        </label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="Contoh: 081234567890"
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Termin Pembayaran (Hari) -->
                    <div>
                        <label for="payment_term_days" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Termin Bayar (Hari) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" name="payment_term_days" id="payment_term_days" min="0" max="365" value="{{ old('payment_term_days', 0) }}" required
                                   class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('payment_term_days') border-red-500 @enderror">
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400 pointer-events-none">
                                Hari (0 = Tunai)
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Jatuh tempo invoice otomatis dihitung: tanggal terbit + termin bayar (BR-I7).</p>
                        @error('payment_term_days')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Alamat -->
                <div>
                    <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Alamat Operasional / Kantor
                    </label>
                    <textarea name="address" id="address" rows="3" placeholder="Alamat lengkap lokasi atau workshop pelanggan..."
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('address') border-red-500 @enderror">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Catatan Khusus
                    </label>
                    <textarea name="notes" id="notes" rows="2" placeholder="Catatan internal terkait pelanggan..."
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('customers.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Simpan Pelanggan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
