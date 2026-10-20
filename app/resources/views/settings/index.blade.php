<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 leading-tight">
                    Pengaturan Sistem
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Konfigurasi identitas bengkel dan parameter keuangan operasional (Admin only).
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                @csrf

                <!-- Shop Name -->
                <div>
                    <label for="shop_name" class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        Nama Bengkel / Usaha <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="shop_name" name="shop_name" 
                           value="{{ old('shop_name', $shopName) }}" required
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                    <p class="mt-1 text-xs text-slate-500">
                        Nama bengkel ini akan dicantumkan pada kop faktur/invoice, laporan keuangan resmi, dan halaman cetak.
                    </p>
                    @error('shop_name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tax Rate Percent -->
                <div>
                    <label for="tax_rate_percent" class="block text-xs font-bold text-slate-700 uppercase mb-1">
                        Tarif Pajak PPN (%) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative w-48">
                        <input type="number" step="0.01" min="0" max="100" id="tax_rate_percent" name="tax_rate_percent" 
                               value="{{ old('tax_rate_percent', $taxRate) }}" required
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 pr-8 font-mono">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs font-bold text-slate-400 pointer-events-none">
                            %
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Sesuai spesifikasi PRD &amp; BR-I3: Tarif pajak default adalah <strong>0%</strong> dan dibaca secara dinamis dari tabel pengaturan (tidak di-hardcode). Jika diisi > 0 (misal 11%), invoice berikutnya akan menghitung PPN secara otomatis.
                    </p>
                    @error('tax_rate_percent')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info Box -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-xs text-amber-900 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-700 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <span>Audit Log Integritas</span>
                    </div>
                    <p class="text-amber-800">
                        Setiap perubahan nama bengkel maupun tarif pajak akan dicatat secara otomatis ke dalam <strong>Audit Log</strong> beserta identitas user dan nilai sebelum/sesudah perubahan.
                    </p>
                </div>

                <!-- Action Button -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg shadow transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
