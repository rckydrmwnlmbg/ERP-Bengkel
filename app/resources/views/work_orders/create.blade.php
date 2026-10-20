<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('work-orders.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; Order Servis
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Buat Order Servis Baru (SPK)') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200"
             x-data="{
                 customers: {{ Js::from($customers) }},
                 selectedCustomer: '{{ old('customer_id', $selectedCustomerId ?? '') }}',
                 selectedUnit: '{{ old('equipment_unit_id', '') }}',
                 get units() {
                     const cust = this.customers.find(c => c.id == this.selectedCustomer);
                     return cust && cust.equipment_units ? cust.equipment_units : [];
                 }
             }">

            <div class="mb-6 pb-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Surat Perintah Kerja (SPK) Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pilih customer dan unit alat berat yang mengalami kendala teknis.</p>
            </div>

            <form method="POST" action="{{ route('work-orders.store') }}" class="space-y-6">
                @csrf

                <!-- Pilih Customer & Unit -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Customer / Pemilik Unit <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" id="customer_id" required
                                x-model="selectedCustomer"
                                @change="selectedUnit = ''"
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('customer_id') border-red-500 @enderror">
                            <option value="">-- Pilih Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ (string) old('customer_id', $selectedCustomerId ?? '') === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ count($c->equipmentUnits) }} Unit)
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="equipment_unit_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Unit Alat Berat <span class="text-red-500">*</span>
                        </label>
                        <select name="equipment_unit_id" id="equipment_unit_id" required
                                x-model="selectedUnit"
                                :disabled="!selectedCustomer || units.length === 0"
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('equipment_unit_id') border-red-500 @enderror">
                            <option value="" x-text="!selectedCustomer ? '-- Pilih Customer Dulu --' : (units.length === 0 ? '-- Customer Belum Punya Unit --' : '-- Pilih Unit Alat Berat --')"></option>
                            <template x-for="unit in units" :key="unit.id">
                                <option :value="unit.id" x-text="unit.brand + ' ' + unit.model + ' (' + (unit.unit_code ? unit.unit_code : unit.serial_number) + ')'"></option>
                            </template>
                        </select>
                        @error('equipment_unit_id')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Mekanik Penanggung Jawab -->
                <div>
                    <label for="mechanic_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Mekanik Penanggung Jawab (Opsional)
                    </label>
                    <select name="mechanic_id" id="mechanic_id"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('mechanic_id') border-red-500 @enderror">
                        <option value="">-- Belum Ditugaskan / Bebas --</option>
                        @foreach($mechanics as $m)
                            <option value="{{ $m->id }}" {{ (string) old('mechanic_id', auth()->user()->isMechanic() ? auth()->id() : '') === (string) $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ ucfirst($m->role) }})
                            </option>
                        @endforeach
                    </select>
                    @error('mechanic_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keluhan Customer -->
                <div>
                    <label for="complaint" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Keluhan / Gejala Kerusakan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="complaint" id="complaint" rows="3" required
                              placeholder="Deskripsikan keluhan pengemudi/operator (misal: Tenaga hidrolik melemah saat bucket naik, ada kebocoran oli di seal arm boom)..."
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('complaint') border-red-500 @enderror">{{ old('complaint') }}</textarea>
                    @error('complaint')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Diagnosa Awal (Opsional) -->
                <div>
                    <label for="diagnosis" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Diagnosa Awal / Catatan Analisa (Opsional)
                    </label>
                    <textarea name="diagnosis" id="diagnosis" rows="2"
                              placeholder="Catatan hasil inspeksi teknis awal mekanik..."
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('diagnosis') border-red-500 @enderror">{{ old('diagnosis') }}</textarea>
                    @error('diagnosis')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('work-orders.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Buat Order Servis
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
