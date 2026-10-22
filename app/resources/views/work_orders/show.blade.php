<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('work-orders.index') }}" class="text-slate-400 hover:text-slate-600 transition text-sm font-semibold">
                    &larr; Order Servis
                </a>
                <span class="text-slate-300">/</span>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ $workOrder->wo_number }}
                </h2>
                @switch($workOrder->status)
                    @case('open')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            Open
                        </span>
                        @break
                    @case('in_progress')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">
                            In Progress
                        </span>
                        @break
                    @case('completed')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            Completed
                        </span>
                        @break
                    @case('invoiced')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-300">
                            Invoiced
                        </span>
                        @break
                    @case('cancelled')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 text-slate-600 border border-slate-300">
                            Cancelled
                        </span>
                        @break
                @endswitch
            </div>

            <div class="flex items-center space-x-2">
                @can('service-orders.manage')
                    @if($workOrder->status !== 'invoiced' && $workOrder->status !== 'cancelled')
                        <a href="{{ route('work-orders.edit', $workOrder) }}" class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-lg transition">
                            Edit Keluhan / Diagnosa
                        </a>
                    @endif
                @endcan

                @if($workOrder->activeInvoice)
                    <a href="{{ route('invoices.show', $workOrder->activeInvoice) }}" class="inline-flex items-center px-3.5 py-2 bg-purple-100 hover:bg-purple-200 text-purple-800 font-bold text-xs uppercase tracking-wider rounded-lg transition border border-purple-300">
                        Invoice: {{ $workOrder->activeInvoice->invoice_number }}
                    </a>
                @elseif($workOrder->status === 'completed')
                    @can('invoices.manage')
                        <form action="{{ route('invoices.store-from-wo', $workOrder) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs uppercase tracking-wider rounded-lg shadow transition">
                                Terbitkan Invoice
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Quick Action Banner for Mechanics (Mobile-First Touch Target) -->
        @can('service-orders.manage')
            @if($workOrder->status === 'open' || $workOrder->status === 'in_progress')
                <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-4 sm:p-5 rounded-2xl shadow-md text-white">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs uppercase tracking-wider text-amber-400 font-bold block">Aksi Alur Pengerjaan (BR-W1)</span>
                            <h3 class="text-lg font-black mt-0.5">
                                @if($workOrder->status === 'open')
                                    Unit Menunggu Pengerjaan
                                @else
                                    Unit Sedang Dikerjakan oleh Mekanik
                                @endif
                            </h3>
                            <p class="text-xs text-slate-300 mt-1">
                                @if($workOrder->status === 'open')
                                    Klik "Mulai Pengerjaan" saat mekanik mulai membongkar atau memeriksa unit.
                                @else
                                    Pastikan seluruh sparepart dan jasa sudah diinput sebelum menyelesaikan order servis.
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            @if($workOrder->status === 'open')
                                <form method="POST" action="{{ route('work-orders.status', $workOrder) }}" class="w-full sm:w-auto">
                                    @csrf
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit" class="w-full sm:w-auto min-h-[48px] px-6 py-3 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                                        <span>▶️ Mulai Pengerjaan</span>
                                    </button>
                                </form>
                            @elseif($workOrder->status === 'in_progress')
                                <form method="POST" action="{{ route('work-orders.status', $workOrder) }}" class="w-full sm:w-auto">
                                    @csrf
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="w-full sm:w-auto min-h-[48px] px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                                        <span>✅ Selesaikan Servis</span>
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('work-orders.status', $workOrder) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan order servis ini? Semua sparepart yang terpasang akan dikembalikan otomatis ke stok (BR-S4).');" class="w-full sm:w-auto">
                                @csrf
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="w-full sm:w-auto min-h-[48px] px-4 py-3 bg-red-600/30 hover:bg-red-600/50 text-red-200 font-semibold text-xs uppercase tracking-wider rounded-xl transition border border-red-500/40">
                                    Batalkan Order
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        @if($workOrder->status === 'completed')
            <div class="bg-gradient-to-r from-emerald-900 to-emerald-800 p-4 sm:p-5 rounded-2xl shadow-md text-white">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-emerald-300 font-bold block">Pengerjaan Selesai (BR-W1)</span>
                        <h3 class="text-lg font-black mt-0.5">Order Servis Siap Dibuatkan Faktur Tagihan</h3>
                        <p class="text-xs text-emerald-100 mt-1">Servis telah diselesaikan oleh tim mekanik. Kasir atau Admin dapat menerbitkan Invoice sekarang.</p>
                    </div>
                    @if($workOrder->activeInvoice)
                        <div class="flex items-center">
                            <a href="{{ route('invoices.show', $workOrder->activeInvoice) }}" class="w-full sm:w-auto min-h-[48px] px-6 py-3 bg-purple-500 hover:bg-purple-400 text-white font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                                <span>👁️ Buka Faktur Invoice</span>
                            </a>
                        </div>
                    @else
                        @can('invoices.manage')
                            <div class="flex items-center">
                                <form action="{{ route('invoices.store-from-wo', $workOrder) }}" method="POST" class="w-full sm:w-auto">
                                    @csrf
                                    <button type="submit" class="w-full sm:w-auto min-h-[48px] px-6 py-3 bg-white hover:bg-emerald-50 text-emerald-900 font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                                        <span>📄 Terbitkan Invoice</span>
                                    </button>
                                </form>
                            </div>
                        @endcan
                    @endif
                </div>
            </div>
        @elseif($workOrder->status === 'invoiced')
            <div class="bg-gradient-to-r from-purple-900 to-purple-800 p-4 sm:p-5 rounded-2xl shadow-md text-white">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-purple-300 font-bold block">Status Penagihan (BR-W1)</span>
                        <h3 class="text-lg font-black mt-0.5">Faktur Tagihan Telah Diterbitkan</h3>
                        <p class="text-xs text-purple-100 mt-1">Order servis ini terikat ke invoice <span class="font-mono font-bold text-amber-300">{{ $workOrder->activeInvoice?->invoice_number }}</span>.</p>
                    </div>
                    @if($workOrder->activeInvoice)
                        <div class="flex items-center">
                            <a href="{{ route('invoices.show', $workOrder->activeInvoice) }}" class="w-full sm:w-auto min-h-[48px] px-6 py-3 bg-purple-500 hover:bg-purple-400 text-white font-bold text-sm uppercase tracking-wider rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                                <span>👁️ Buka Faktur Invoice</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Grid Info Unit & Customer -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Customer Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-400 block uppercase font-bold tracking-wider mb-2">Customer / Pelanggan</span>
                <div class="text-base font-bold text-slate-900">{{ $workOrder->customer?->name }}</div>
                <div class="text-xs text-slate-600 mt-1">Telp: {{ $workOrder->customer?->phone ?? '-' }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $workOrder->customer?->address ?? '-' }}</div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Termin Bayar:</span>
                    <span class="font-bold text-slate-700">{{ $workOrder->customer?->payment_term_days ?? 0 }} Hari</span>
                </div>
            </div>

            <!-- Equipment Unit Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-400 block uppercase font-bold tracking-wider mb-2">Unit Alat Berat</span>
                <div class="text-base font-bold text-slate-900">
                    {{ $workOrder->equipmentUnit?->brand }} {{ $workOrder->equipmentUnit?->model }}
                </div>
                <div class="text-xs text-slate-600 mt-1">
                    No. Seri: <span class="font-mono">{{ $workOrder->equipmentUnit?->serial_number ?? '-' }}</span>
                </div>
                <div class="text-xs text-slate-600 mt-0.5">
                    Kode Unit: <span class="font-mono font-bold text-amber-700">{{ $workOrder->equipmentUnit?->unit_code ?? '-' }}</span>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Hour Meter (HM):</span>
                    <span class="font-mono font-bold text-slate-700">{{ number_format($workOrder->equipmentUnit?->hour_meter ?? 0) }} Jam</span>
                </div>
            </div>

            <!-- Mechanic & Timeline Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold tracking-wider mb-2">Mekanik & Waktu</span>
                    <div class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>{{ $workOrder->mechanic?->name ?? 'Belum Ditugaskan' }}</span>
                    </div>
                    <div class="text-xs text-slate-500 mt-2">
                        Masuk: <span class="font-mono">{{ $workOrder->opened_at?->format('d-m-Y H:i') ?? '-' }}</span>
                    </div>
                    @if($workOrder->completed_at)
                        <div class="text-xs text-emerald-600 font-semibold mt-1">
                            Selesai: <span class="font-mono">{{ $workOrder->completed_at->format('d-m-Y H:i') }}</span>
                        </div>
                    @endif
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-400">
                    Dibuat oleh: {{ $workOrder->creator?->name ?? 'Sistem' }}
                </div>
            </div>
        </div>

        <!-- Keluhan & Diagnosa -->
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Keluhan Pelanggan</h4>
                <p class="text-sm text-slate-800 mt-1 whitespace-pre-line">{{ $workOrder->complaint }}</p>
            </div>
            @if($workOrder->diagnosis)
                <div class="pt-3 border-t border-slate-100">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Diagnosa & Tindakan Teknis Mekanik</h4>
                    <p class="text-sm text-slate-700 mt-1 whitespace-pre-line">{{ $workOrder->diagnosis }}</p>
                </div>
            @endif
        </div>

        <!-- SECTION 1: Jasa Servis / Tenaga Kerja (BR-P1, BR-P2) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ showAddService: false }">
            <div class="p-4 sm:p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="font-bold text-sm sm:text-base text-slate-800 flex items-center space-x-2">
                        <span>🔧 Jasa & Tindakan Servis</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                            {{ $workOrder->serviceItems->count() }}
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Biaya jasa menggunakan tarif khusus customer jika ada (BR-P1).</p>
                </div>

                @can('service-orders.manage')
                    @if($workOrder->canModifyItems())
                        <button type="button" @click="showAddService = !showAddService" class="inline-flex items-center px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition">
                            <span x-show="!showAddService">+ Tambah Jasa</span>
                            <span x-show="showAddService">&times; Tutup Form</span>
                        </button>
                    @endif
                @endcan
            </div>

            <!-- Form Tambah Jasa (Collapsible / Mobile Optimized) -->
            @can('service-orders.manage')
                @if($workOrder->canModifyItems())
                    <div x-show="showAddService" class="p-4 bg-amber-50/50 border-b border-amber-200">
                        <form method="POST" action="{{ route('work-orders.services.store', $workOrder) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                            @csrf
                            <div class="sm:col-span-2">
                                <label for="service_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Pilih Jasa Servis <span class="text-red-500">*</span>
                                </label>
                                <select name="service_id" id="service_id" required class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">-- Pilih Jasa Servis --</option>
                                    @foreach($availableServices as $srv)
                                        @php
                                            $custPrice = app(\App\Services\PricingService::class)->resolveServicePrice((int)$workOrder->customer_id, (int)$srv->id);
                                        @endphp
                                        <option value="{{ $srv->id }}">
                                            {{ $srv->name }} (Rp{{ number_format($custPrice, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="srv_qty" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Jumlah <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="qty" id="srv_qty" value="1" min="1" required class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                            </div>
                            <div>
                                <button type="submit" class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg shadow transition">
                                    Simpan Jasa
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            @endcan

            <!-- Table of Services -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Nama Jasa Servis</th>
                            <th class="py-3 px-4 text-center">Jumlah</th>
                            <th class="py-3 px-4 text-right">Tarif Satuan</th>
                            <th class="py-3 px-4 text-right">Subtotal</th>
                            @can('service-orders.manage')
                                @if($workOrder->canModifyItems())
                                    <th class="py-3 px-4 text-center w-16">Hapus</th>
                                @endif
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($workOrder->serviceItems as $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $item->getItemName() }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono">
                                    {{ $item->qty }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp{{ number_format($item->line_total, 0, ',', '.') }}
                                </td>
                                @can('service-orders.manage')
                                    @if($workOrder->canModifyItems())
                                        <td class="py-3 px-4 text-center">
                                            <form method="POST" action="{{ route('work-orders.items.destroy', [$workOrder, $item]) }}" onsubmit="return confirm('Hapus jasa ini dari order servis?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-slate-400 hover:text-red-600 transition" title="Hapus">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $workOrder->canModifyItems() ? 5 : 4 }}" class="py-8 text-center text-slate-400 text-xs">
                                    Belum ada jasa servis yang ditambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($workOrder->serviceItems->count() > 0)
                        <tfoot>
                            <tr class="bg-slate-50 font-bold text-xs text-slate-700 border-t border-slate-200">
                                <td colspan="3" class="py-3 px-4 text-right uppercase">Total Jasa:</td>
                                <td class="py-3 px-4 text-right font-mono text-sm text-slate-900">
                                    Rp{{ number_format($workOrder->calculateServicesTotal(), 0, ',', '.') }}
                                </td>
                                @if($workOrder->canModifyItems()) <td></td> @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- SECTION 2: Penggunaan Sparepart (BR-S4, BR-P3, BR-P4) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ showAddPart: false }">
            <div class="p-4 sm:p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="font-bold text-sm sm:text-base text-slate-800 flex items-center space-x-2">
                        <span>📦 Penggunaan Sparepart / Bahan</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-semibold">
                            {{ $workOrder->partItems->count() }}
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Penambahan memotong stok otomatis (SERVICE_OUT, BR-S4).</p>
                </div>

                @can('service-orders.manage')
                    @if($workOrder->canModifyItems())
                        <button type="button" @click="showAddPart = !showAddPart" class="inline-flex items-center px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider rounded-lg transition">
                            <span x-show="!showAddPart">+ Pasang Sparepart</span>
                            <span x-show="showAddPart">&times; Tutup Form</span>
                        </button>
                    @endif
                @endcan
            </div>

            <!-- Form Pasang Sparepart (Collapsible / Mobile Optimized) -->
            @can('service-orders.manage')
                @if($workOrder->canModifyItems())
                    <div x-show="showAddPart" class="p-4 bg-amber-50/50 border-b border-amber-200">
                        <form method="POST" action="{{ route('work-orders.parts.store', $workOrder) }}" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                <div class="sm:col-span-2">
                                    <label for="part_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Pilih Sparepart <span class="text-red-500">*</span>
                                    </label>
                                    <select name="part_id" id="part_id" required class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                        <option value="">-- Pilih Sparepart --</option>
                                        @foreach($availableParts as $p)
                                            <option value="{{ $p->id }}" {{ $p->qty_on_hand <= 0 ? 'disabled' : '' }}>
                                                [{{ $p->sku }}] {{ $p->name }} — Stok: {{ $p->qty_on_hand }} {{ $p->unit }} (Harga: Rp{{ number_format($p->sell_price, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="part_qty" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                        Kuantitas <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="qty" id="part_qty" value="1" min="1" required class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 font-mono">
                                </div>
                            </div>

                            @can('master-data.manage')
                                <div>
                                    <label for="override_reason" class="block text-[11px] font-semibold text-slate-500 mb-1">
                                        Alasan Override Admin (Khusus jika harga jual < HPP, BR-P4)
                                    </label>
                                    <input type="text" name="override_reason" id="override_reason" placeholder="Contoh: Diskon garansi overhaul..." class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                                </div>
                            @endcan

                            <div class="flex justify-end">
                                <button type="submit" class="py-2.5 px-6 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg shadow transition">
                                    Pasang Sparepart & Potong Stok
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            @endcan

            <!-- Table of Parts -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Nama Sparepart</th>
                            <th class="py-3 px-4 text-center">Jumlah</th>
                            <th class="py-3 px-4 text-right">Harga Jual</th>
                            @can('view-cost-price')
                                <th class="py-3 px-4 text-right bg-amber-50/50 text-amber-900">Snapshot HPP (BR-S8)</th>
                            @endcan
                            <th class="py-3 px-4 text-right">Subtotal</th>
                            @can('service-orders.manage')
                                @if($workOrder->canModifyItems())
                                    <th class="py-3 px-4 text-center w-16">Retur</th>
                                @endif
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($workOrder->partItems as $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $item->getItemName() }}
                                    @if($item->part)
                                        <span class="block text-xs font-mono text-slate-400 font-normal">SKU: {{ $item->part->sku }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-mono">
                                    {{ $item->qty }} {{ $item->part?->unit }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                                </td>
                                @can('view-cost-price')
                                    <td class="py-3 px-4 text-right font-mono text-xs bg-amber-50/30 text-amber-900">
                                        Rp{{ number_format($item->unit_cost_snapshot, 0, ',', '.') }}
                                    </td>
                                @endcan
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    Rp{{ number_format($item->line_total, 0, ',', '.') }}
                                </td>
                                @can('service-orders.manage')
                                    @if($workOrder->canModifyItems())
                                        <td class="py-3 px-4 text-center">
                                            <form method="POST" action="{{ route('work-orders.items.destroy', [$workOrder, $item]) }}" onsubmit="return confirm('Hapus sparepart ini? Stok fisik akan dikembalikan ke gudang (BR-S4).');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-slate-400 hover:text-red-600 transition" title="Hapus & Retur Stok">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $workOrder->canModifyItems() ? (auth()->user()->isAdmin() ? 6 : 5) : (auth()->user()->isAdmin() ? 5 : 4) }}" class="py-8 text-center text-slate-400 text-xs">
                                    Belum ada sparepart yang digunakan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($workOrder->partItems->count() > 0)
                        <tfoot>
                            <tr class="bg-slate-50 font-bold text-xs text-slate-700 border-t border-slate-200">
                                <td colspan="3" class="py-3 px-4 text-right uppercase">Total Sparepart:</td>
                                @can('view-cost-price')
                                    <td></td>
                                @endcan
                                <td class="py-3 px-4 text-right font-mono text-sm text-slate-900">
                                    Rp{{ number_format($workOrder->calculatePartsTotal(), 0, ',', '.') }}
                                </td>
                                @if($workOrder->canModifyItems()) <td></td> @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- TOTAL REKAPITULASI ORDER SERVIS -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Total Biaya Servis (Sebelum Diskon/Pajak Invoice)</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Nilai akumulasi jasa dan sparepart yang siap ditagihkan.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-semibold uppercase">Total Nilai Order</span>
                    <span class="font-mono font-black text-2xl sm:text-3xl text-amber-600">
                        Rp{{ number_format($workOrder->calculateTotal(), 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
