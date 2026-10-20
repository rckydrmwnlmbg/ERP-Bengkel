<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('customers.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                    &larr; Pelanggan
                </a>
                <span class="text-slate-300">/</span>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ $customer->name }}
                </h2>
                <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    {{ $customer->code }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                @can('master-data.manage')
                    <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                        Edit Pelanggan
                    </a>
                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini? (Soft Delete)');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-100 hover:bg-red-200 text-red-800 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                            Hapus
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">
        <!-- Customer Details Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-4">Informasi Profil Pelanggan</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <span class="text-xs text-slate-400 block">Tipe Pelanggan</span>
                    <span class="font-bold text-sm text-slate-800">
                        {{ $customer->type === 'company' ? '🏢 Perusahaan' : '👤 Perorangan' }}
                    </span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block">Nomor Telepon</span>
                    <span class="font-bold text-sm text-slate-800">
                        {{ $customer->phone ?: '-' }}
                    </span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block">Termin Pembayaran (BR-I7)</span>
                    <span class="font-bold text-sm text-slate-800">
                        {{ $customer->payment_term_days > 0 ? "{$customer->payment_term_days} Hari" : 'Tunai (0 Hari)' }}
                    </span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block">Total Unit Terdaftar</span>
                    <span class="font-bold text-sm text-amber-700">
                        {{ $customer->equipmentUnits->count() }} Unit Alat Berat
                    </span>
                </div>
                <div class="md:col-span-2">
                    <span class="text-xs text-slate-400 block">Alamat</span>
                    <p class="text-sm text-slate-700 mt-0.5">{{ $customer->address ?: '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <span class="text-xs text-slate-400 block">Catatan Khusus</span>
                    <p class="text-sm text-slate-700 mt-0.5">{{ $customer->notes ?: '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Section 1: Armada Unit Alat Berat -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6" x-data="{ openAddUnit: false }">
            <div class="flex items-center justify-between flex-wrap gap-4 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Armada Unit Alat Berat</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar excavator, bulldozer, loader, atau unit lain milik pelanggan ini.</p>
                </div>
                @can('customer.create')
                    <button @click="openAddUnit = !openAddUnit" class="inline-flex items-center px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg transition">
                        <span x-show="!openAddUnit">+ Tambah Unit Baru</span>
                        <span x-show="openAddUnit">Tutup Form</span>
                    </button>
                @endcan
            </div>

            <!-- Form Tambah Unit Baru (Collapsible) -->
            <div x-show="openAddUnit" x-transition class="p-5 rounded-xl bg-slate-50 border border-slate-200">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Pendaftaran Unit Alat Berat Baru</h4>
                <form method="POST" action="{{ route('customers.equipment-units.store', $customer) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Merek Alat <span class="text-red-500">*</span></label>
                            <input type="text" name="brand" placeholder="Contoh: Komatsu, Caterpillar, Hitachi" required class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Model / Tipe <span class="text-red-500">*</span></label>
                            <input type="text" name="model" placeholder="Contoh: PC200-8, D85ESS-2, 320D" required class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode / Nomor Lambung</label>
                            <input type="text" name="unit_code" placeholder="Contoh: EX-01, DT-04" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Seri (Serial Number / VIN)</label>
                            <input type="text" name="serial_number" placeholder="Contoh: KMTPC200XXXXXXXXX" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Hour Meter Saat Ini (HM)</label>
                            <input type="number" name="hour_meter" min="0" value="0" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Unit</label>
                            <input type="text" name="notes" placeholder="Lokasi tambang / kondisi" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                            Simpan Unit
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Unit -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-3">Kode Unit</th>
                            <th class="py-3 px-3">Merek & Model</th>
                            <th class="py-3 px-3">Serial Number</th>
                            <th class="py-3 px-3 text-right">Hour Meter (HM)</th>
                            <th class="py-3 px-3">Catatan</th>
                            @can('master-data.manage')
                                <th class="py-3 px-3 text-right">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($customer->equipmentUnits as $unit)
                            <tr class="hover:bg-slate-50 transition" x-data="{ editing: false }">
                                <td class="py-3 px-3 font-mono font-bold text-amber-700">
                                    {{ $unit->unit_code ?: '-' }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-900">
                                    {{ $unit->brand }} {{ $unit->model }}
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-500">
                                    {{ $unit->serial_number ?: '-' }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">
                                    {{ number_format($unit->hour_meter, 0, ',', '.') }} jam
                                </td>
                                <td class="py-3 px-3 text-slate-500">
                                    {{ $unit->notes ?: '-' }}
                                </td>
                                @can('master-data.manage')
                                    <td class="py-3 px-3 text-right space-x-1 whitespace-nowrap">
                                        <button @click="editing = !editing" class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 font-semibold">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('equipment-units.destroy', $unit) }}" class="inline-block" onsubmit="return confirm('Hapus unit ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-0.5 rounded bg-red-100 text-red-700 hover:bg-red-200 font-semibold">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                @endcan

                                <!-- Form Edit Inline untuk Unit -->
                                <tr x-show="editing" class="bg-amber-50/50">
                                    <td colspan="6" class="p-4 border-b border-amber-200">
                                        <form method="POST" action="{{ route('equipment-units.update', $unit) }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-600 block">Kode Unit</label>
                                                <input type="text" name="unit_code" value="{{ $unit->unit_code }}" class="w-full text-xs rounded border-slate-300">
                                            </div>
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-600 block">Merek</label>
                                                <input type="text" name="brand" value="{{ $unit->brand }}" required class="w-full text-xs rounded border-slate-300">
                                            </div>
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-600 block">Model</label>
                                                <input type="text" name="model" value="{{ $unit->model }}" required class="w-full text-xs rounded border-slate-300">
                                            </div>
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-600 block">Serial Number</label>
                                                <input type="text" name="serial_number" value="{{ $unit->serial_number }}" class="w-full text-xs rounded border-slate-300">
                                            </div>
                                            <div>
                                                <label class="text-[10px] uppercase font-bold text-slate-600 block">HM</label>
                                                <input type="number" name="hour_meter" value="{{ $unit->hour_meter }}" min="0" class="w-full text-xs rounded border-slate-300">
                                            </div>
                                            <div class="flex space-x-1">
                                                <button type="submit" class="px-3 py-1.5 bg-amber-500 text-slate-950 font-bold rounded text-xs">Simpan</button>
                                                <button type="button" @click="editing = false" class="px-2 py-1.5 bg-slate-200 text-slate-700 rounded text-xs">Batal</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Belum ada unit alat berat yang didaftarkan untuk pelanggan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Tarif Jasa Khusus (BR-P1) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-4 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Tarif Jasa Khusus Pelanggan (BR-P1)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Sesuai BR-P1: Saat order servis dibuat, harga jasa otomatis mengambil tarif khusus ini jika ada, atau tarif standar jika tidak ada.
                    </p>
                </div>
            </div>

            <!-- Form Pasang Tarif Khusus (Admin Only) -->
            @can('master-data.manage')
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">Tetapkan / Perbarui Tarif Jasa Khusus</h4>
                    <form method="POST" action="{{ route('customers.rates.store', $customer) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Jenis Jasa <span class="text-red-500">*</span></label>
                            <select name="service_id" required class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                <option value="">-- Pilih Jasa --</option>
                                @foreach($services as $srv)
                                    <option value="{{ $srv->id }}">
                                        {{ $srv->code }} - {{ $srv->name }} (Standar: Rp{{ number_format($srv->default_price, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tarif Khusus Pelanggan (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="price" min="0" placeholder="Contoh: 175000" required class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        </div>
                        <div>
                            <button type="submit" class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                                Simpan Tarif Khusus
                            </button>
                        </div>
                    </form>
                    <p class="text-[11px] text-slate-400 mt-2">Setiap perubahan tarif khusus otomatis dicatat ke tabel Audit Log.</p>
                </div>
            @endcan

            <!-- Daftar Tarif Khusus Aktif -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-3">Kode Jasa</th>
                            <th class="py-3 px-3">Nama Jasa</th>
                            <th class="py-3 px-3 text-right">Tarif Standar</th>
                            <th class="py-3 px-3 text-right">Tarif Khusus Pelanggan</th>
                            <th class="py-3 px-3 text-right">Selisih</th>
                            @can('master-data.manage')
                                <th class="py-3 px-3 text-right">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($customer->serviceRates as $rate)
                            @php
                                $diff = (int)$rate->price - (int)$rate->service->default_price;
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-3 font-mono font-bold text-slate-700">
                                    {{ $rate->service->code }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-900">
                                    {{ $rate->service->name }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-slate-500">
                                    Rp{{ number_format($rate->service->default_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-amber-700">
                                    Rp{{ number_format($rate->price, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono">
                                    @if($diff < 0)
                                        <span class="text-emerald-700 font-bold">-Rp{{ number_format(abs($diff), 0, ',', '.') }} (Diskon)</span>
                                    @elseif($diff > 0)
                                        <span class="text-blue-700 font-bold">+Rp{{ number_format($diff, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-400">Sama</span>
                                    @endif
                                </td>
                                @can('master-data.manage')
                                    <td class="py-3 px-3 text-right">
                                        <form method="POST" action="{{ route('customers.rates.destroy', [$customer, $rate]) }}" class="inline-block" onsubmit="return confirm('Kembalikan tarif jasa ini ke tarif standar?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded bg-red-100 text-red-700 hover:bg-red-200 transition">
                                                Hapus Tarif Khusus
                                            </button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    Pelanggan ini belum memiliki tarif khusus (semua order servis akan memakai tarif standar).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
