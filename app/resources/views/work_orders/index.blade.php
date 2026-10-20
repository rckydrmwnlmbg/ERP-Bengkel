<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-bold text-xl text-slate-800 leading-tight">
                    {{ __('Order Servis (Surat Perintah Kerja)') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Daftar instruksi perbaikan unit alat berat dan penggunaan sparepart bengkel (BR-W1..W4).
                </p>
            </div>
            @can('service-orders.manage')
                <a href="{{ route('work-orders.create') }}" class="inline-flex items-center px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider rounded-lg transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat Order Servis Baru
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Status Filter Tabs (Touch-friendly on mobile) -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            @php
                $currentStatus = request('status');
                $statuses = [
                    '' => 'Semua',
                    'open' => 'Open',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'invoiced' => 'Invoiced',
                    'cancelled' => 'Cancelled',
                ];
            @endphp
            @foreach($statuses as $stKey => $stLabel)
                <a href="{{ route('work-orders.index', array_merge(request()->except('status', 'page'), $stKey ? ['status' => $stKey] : [])) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ ($currentStatus === $stKey || ($stKey === '' && !$currentStatus)) ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    {{ $stLabel }}
                </a>
            @endforeach
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('work-orders.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="sm:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari nomor WO (mis: WO-2610-0001), nama customer, atau unit..."
                           class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
                <div class="flex items-center space-x-2">
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition">
                        Cari
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'mechanic_id']))
                        <a href="{{ route('work-orders.index') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Responsive Card List for Mobile / Table for Desktop -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <!-- Mobile Cards (Visible only on small screens) -->
            <div class="block sm:hidden divide-y divide-slate-100">
                @forelse($workOrders as $wo)
                    <a href="{{ route('work-orders.show', $wo) }}" class="block p-4 hover:bg-slate-50/80 transition">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-mono font-bold text-amber-700 text-sm">
                                {{ $wo->wo_number }}
                            </span>
                            @switch($wo->status)
                                @case('open')
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Open</span>
                                    @break
                                @case('in_progress')
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-300">In Progress</span>
                                    @break
                                @case('completed')
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Completed</span>
                                    @break
                                @case('invoiced')
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-300">Invoiced</span>
                                    @break
                                @case('cancelled')
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-200 text-slate-600 border border-slate-300">Cancelled</span>
                                    @break
                            @endswitch
                        </div>
                        <div class="text-sm font-bold text-slate-900">{{ $wo->customer?->name ?? '-' }}</div>
                        <div class="text-xs text-slate-600 mt-0.5">
                            {{ $wo->equipmentUnit?->brand }} {{ $wo->equipmentUnit?->model }} 
                            <span class="font-mono text-slate-500">({{ $wo->equipmentUnit?->unit_code ?? '-' }})</span>
                        </div>
                        <div class="text-xs text-slate-500 mt-2 line-clamp-2 italic">
                            "{{ $wo->complaint }}"
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-2 border-t border-slate-100 text-xs text-slate-500">
                            <span>Mekanik: <strong>{{ $wo->mechanic?->name ?? 'Belum Ditugaskan' }}</strong></span>
                            <span class="font-semibold text-slate-700">{{ $wo->items_count }} Item</span>
                        </div>
                    </a>
                @empty
                    <div class="py-12 text-center text-slate-400 text-sm">
                        Belum ada data order servis.
                    </div>
                @endforelse
            </div>

            <!-- Desktop Table (Hidden on mobile) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">No. WO</th>
                            <th class="py-3.5 px-4">Customer</th>
                            <th class="py-3.5 px-4">Unit Alat Berat</th>
                            <th class="py-3.5 px-4">Mekanik</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-center">Item</th>
                            <th class="py-3.5 px-4 text-right">Tanggal Masuk</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($workOrders as $wo)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-amber-700 text-xs whitespace-nowrap">
                                    <a href="{{ route('work-orders.show', $wo) }}" class="hover:underline">
                                        {{ $wo->wo_number }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ $wo->customer?->name ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <span class="font-medium">{{ $wo->equipmentUnit?->brand }} {{ $wo->equipmentUnit?->model }}</span>
                                    <span class="block text-xs font-mono text-slate-400">{{ $wo->equipmentUnit?->unit_code ?? $wo->equipmentUnit?->serial_number }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-700">
                                    {{ $wo->mechanic?->name ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @switch($wo->status)
                                        @case('open')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">Open</span>
                                            @break
                                        @case('in_progress')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">In Progress</span>
                                            @break
                                        @case('completed')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Completed</span>
                                            @break
                                        @case('invoiced')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-300">Invoiced</span>
                                            @break
                                        @case('cancelled')
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 text-slate-600 border border-slate-300">Cancelled</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-xs text-slate-600">
                                    {{ $wo->items_count }} Item
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-600 whitespace-nowrap">
                                    {{ $wo->opened_at?->format('d-m-Y H:i') ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <a href="{{ route('work-orders.show', $wo) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition">
                                        Buka SPK &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    Belum ada data order servis.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($workOrders->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $workOrders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
