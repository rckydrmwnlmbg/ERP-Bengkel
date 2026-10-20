<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 leading-tight">
                    Audit Log Aktivitas Sistem
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Rekam jejak seluruh mutasi harga, perubahan status order servis, pembatalan invoice/pembayaran, dan penyesuaian stok.
                </p>
            </div>
            <div class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 text-white flex items-center gap-2">
                <span>Total Tercatat:</span>
                <span class="font-bold text-amber-400 font-mono">{{ $logs->total() }} Log</span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter & Search Card -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cari Kata Kunci</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari aksi, entitas, ID, atau nama pengguna..."
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tipe Aksi</label>
                    <select name="action" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">-- Semua Aksi --</option>
                        @foreach($actions as $act)
                            <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                                {{ strtoupper(str_replace('_', ' ', $act)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Entitas</label>
                    <select name="entity_type" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">-- Semua Entitas --</option>
                        @foreach($entityTypes as $et)
                            <option value="{{ $et }}" {{ request('entity_type') === $et ? 'selected' : '' }}>
                                {{ class_basename($et) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Pengguna</label>
                    <select name="user_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">-- Semua Pengguna --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ ucfirst($u->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'action', 'entity_type', 'user_id', 'start_date', 'end_date']))
                        <a href="{{ route('audit-logs.index') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">Waktu</th>
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-4">Aksi</th>
                            <th class="py-3.5 px-4">Entitas Terkait</th>
                            <th class="py-3.5 px-4">Detail Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-slate-50/80 transition align-top">
                                <td class="py-3.5 px-4 whitespace-nowrap text-xs font-mono text-slate-600">
                                    <div>{{ $log->created_at?->format('d/m/Y') }}</div>
                                    <div class="text-[11px] text-slate-400 font-medium">{{ $log->created_at?->format('H:i:s') }} WIB</div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-xs">
                                    <div class="font-bold text-slate-900">{{ $log->user?->name ?? 'Sistem / Seeder' }}</div>
                                    @if($log->user)
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 text-[10px] font-semibold rounded uppercase
                                            @if($log->user->role === 'admin') bg-red-100 text-red-800
                                            @elseif($log->user->role === 'cashier') bg-blue-100 text-blue-800
                                            @else bg-emerald-100 text-emerald-800 @endif">
                                            {{ $log->user->role }}
                                        </span>
                                    @else
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 text-[10px] font-semibold rounded uppercase bg-slate-100 text-slate-600">
                                            System
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-xs">
                                    @php
                                        $act = strtolower($log->action);
                                        $badgeColor = 'bg-slate-100 text-slate-700 border-slate-300';
                                        if (str_contains($act, 'void') || str_contains($act, 'delete')) {
                                            $badgeColor = 'bg-rose-100 text-rose-800 border-rose-300';
                                        } elseif (str_contains($act, 'create') || str_contains($act, 'in')) {
                                            $badgeColor = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                                        } elseif (str_contains($act, 'price') || str_contains($act, 'cost')) {
                                            $badgeColor = 'bg-amber-100 text-amber-800 border-amber-300';
                                        } elseif (str_contains($act, 'status')) {
                                            $badgeColor = 'bg-blue-100 text-blue-800 border-blue-300';
                                        }
                                    @endphp
                                    <span class="inline-block px-2.5 py-1 rounded text-[11px] font-bold border {{ $badgeColor }} uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $log->action) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-xs font-mono">
                                    <span class="font-bold text-slate-800">{{ class_basename($log->entity_type) }}</span>
                                    <span class="text-slate-500">#{{ $log->entity_id }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-xs font-mono">
                                    @if(!empty($log->old_values) || !empty($log->new_values))
                                        <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-[11px] space-y-1.5 max-w-xl">
                                            @if(!empty($log->old_values))
                                                <div class="flex items-start gap-2">
                                                    <span class="text-rose-700 font-bold shrink-0">LAMA:</span>
                                                    <span class="text-slate-600 break-all">{{ json_encode($log->old_values, JSON_UNESCAPED_UNICODE) }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($log->new_values))
                                                <div class="flex items-start gap-2">
                                                    <span class="text-emerald-700 font-bold shrink-0">BARU:</span>
                                                    <span class="text-slate-900 font-semibold break-all">{{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">- Tidak ada data perubahan spesifik -</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-400 text-sm">
                                    Tidak ada catatan log audit yang sesuai kriteria pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
