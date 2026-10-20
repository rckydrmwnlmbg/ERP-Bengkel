<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('work-orders.show', $workOrder) }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                &larr; {{ $workOrder->wo_number }}
            </a>
            <span class="text-slate-300">/</span>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                {{ __('Edit Informasi Order Servis') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200">
            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800">{{ $workOrder->wo_number }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Customer: <strong>{{ $workOrder->customer?->name }}</strong> &bull; Unit: <strong>{{ $workOrder->equipmentUnit?->brand }} {{ $workOrder->equipmentUnit?->model }}</strong>
                    </p>
                </div>
                <span class="px-2.5 py-1 rounded text-xs font-mono font-bold bg-slate-100 text-slate-800">
                    Status: {{ strtoupper($workOrder->status) }}
                </span>
            </div>

            <form method="POST" action="{{ route('work-orders.update', $workOrder) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Mekanik Penanggung Jawab -->
                <div>
                    <label for="mechanic_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Mekanik Penanggung Jawab
                    </label>
                    <select name="mechanic_id" id="mechanic_id"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('mechanic_id') border-red-500 @enderror">
                        <option value="">-- Belum Ditugaskan / Bebas --</option>
                        @foreach($mechanics as $m)
                            <option value="{{ $m->id }}" {{ (string) old('mechanic_id', $workOrder->mechanic_id) === (string) $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ ucfirst($m->role) }})
                            </option>
                        @endforeach
                    </select>
                    @error('mechanic_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keluhan Pelanggan -->
                <div>
                    <label for="complaint" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Keluhan Pelanggan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="complaint" id="complaint" rows="3" required
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('complaint') border-red-500 @enderror">{{ old('complaint', $workOrder->complaint) }}</textarea>
                    @error('complaint')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Diagnosa / Catatan Teknis -->
                <div>
                    <label for="diagnosis" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Diagnosa & Analisa Teknis Mekanik
                    </label>
                    <textarea name="diagnosis" id="diagnosis" rows="3"
                              placeholder="Tuliskan temuan mekanik setelah unit dibongkar/diperiksa..."
                              class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 @error('diagnosis') border-red-500 @enderror">{{ old('diagnosis', $workOrder->diagnosis) }}</textarea>
                    @error('diagnosis')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold shadow transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
