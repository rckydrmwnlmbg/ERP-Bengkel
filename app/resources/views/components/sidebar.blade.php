<aside x-data="{ 
        collapsed: false, 
        showModal: false, 
        modalTitle: '', 
        modalPhase: '', 
        modalDesc: '', 
        modalRole: '',
        openPhase(title, phase, desc, role) {
            this.modalTitle = title;
            this.modalPhase = phase;
            this.modalDesc = desc;
            this.modalRole = role;
            this.showModal = true;
        }
    }" 
    :class="collapsed ? 'w-20' : 'w-64'" 
    class="transition-all duration-300 ease-in-out bg-slate-900 text-slate-100 min-h-screen flex flex-col shrink-0 border-r border-slate-800 shadow-lg relative">
    
    <!-- Sidebar Header -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800">
        <div x-show="!collapsed" class="flex items-center space-x-2 overflow-hidden transition-all duration-300">
            <span class="text-xl font-black tracking-wider text-amber-400">ERP</span>
            <span class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Bengkel</span>
        </div>
        <button @click="collapsed = !collapsed" 
                class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white focus:outline-none transition-colors"
                title="Sembunyikan / Tampilkan Sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
        </button>
    </div>

    <!-- User Role Badge -->
    <div class="p-4 border-b border-slate-800 bg-slate-950/40">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-amber-500 text-slate-900 font-bold flex items-center justify-center shrink-0 shadow">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            </div>
            <div x-show="!collapsed" class="overflow-hidden">
                <div class="text-sm font-medium text-white truncate">{{ Auth::user()->name ?? 'Pengguna' }}</div>
                <div class="inline-block mt-0.5 px-2 py-0.5 text-xs font-semibold rounded uppercase 
                    @if(Auth::user()?->role === 'admin') bg-red-900/80 text-red-200 border border-red-700
                    @elseif(Auth::user()?->role === 'cashier') bg-blue-900/80 text-blue-200 border border-blue-700
                    @else bg-emerald-900/80 text-emerald-200 border border-emerald-700 @endif">
                    {{ Auth::user()?->role === 'admin' ? 'Admin' : (Auth::user()?->role === 'cashier' ? 'Kasir' : 'Mekanik') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
        <!-- Dashboard (Aktif di Fase 1) -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                  {{ request()->routeIs('dashboard') ? 'bg-amber-500 text-slate-950 font-bold shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span x-show="!collapsed" class="ms-3">Dashboard</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-900/80 text-emerald-200 border border-emerald-700">Aktif</span>
        </a>

        <!-- Master Data Section -->
        @can('master-data.view')
        <div x-show="!collapsed" class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Master Data
        </div>
        <button type="button" 
                @click="openPhase('Customer & Unit Alat Berat', 'Fase 2 (Master Data)', 'Pengelolaan data pelanggan (perusahaan/individu), tarif khusus jasa per customer, dan unit alat berat (Excavator, Bulldozer, Dump Truck). Dilengkapi soft delete dan validasi FormRequest.', 'Admin (CRUD Penuh), Kasir (Lihat & Tambah), Mekanik (Lihat Saja)')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Customer &amp; Unit</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 2</span>
        </button>

        <button type="button" 
                @click="openPhase('Master Sparepart & Kategori', 'Fase 2 (Master Data)', 'Katalog master sparepart, nomor part, merk, harga jual, dan stok minimum. HPP / harga beli disembunyikan dari Kasir dan Mekanik sesuai PRD.', 'Admin (Akses Penuh + HPP), Kasir & Mekanik (Lihat Tanpa HPP)')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Sparepart</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 2</span>
        </button>

        <button type="button" 
                @click="openPhase('Jasa & Servis', 'Fase 2 (Master Data)', 'Katalog master tarif standar pekerjaan jasa servis, perawatan berkala, overhaul, dan perbaikan hidrolik alat berat.', 'Admin (Akses Penuh), Kasir & Mekanik (Lihat Saja)')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Jasa / Servis</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 2</span>
        </button>
        @endcan

        <!-- Operasional Section -->
        <div x-show="!collapsed" class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Operasional
        </div>
        <button type="button" 
                @click="openPhase('Order Servis (Work Order)', 'Fase 4 (Order Servis)', 'Alur order servis: Draft -> Dikerjakan -> Menunggu Part -> Selesai -> Ditagih. Tampilan ramah ponsel bagi mekanik untuk diagnosa, pemakaian suku cadang, dan jasa perbaikan.', 'Admin & Mekanik (Buat, Diagnosa, Input Part/Jasa, Ubah Status), Kasir (Lihat Saja)')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Order Servis (WO)</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 4</span>
        </button>

        @can('direct-sales.manage')
        <button type="button" 
                @click="openPhase('Penjualan Langsung Sparepart', 'Fase 5 (Invoice & Pembayaran)', 'Transaksi kasir penjualan sparepart langsung kepada pelanggan tanpa melalui alur order servis bengkel.', 'Admin & Kasir')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Penjualan Langsung</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 5</span>
        </button>
        @endcan

        <!-- Keuangan Section -->
        @can('invoices.manage')
        <div x-show="!collapsed" class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Keuangan
        </div>
        <button type="button" 
                @click="openPhase('Invoice & Pembayaran Cicilan', 'Fase 5 (Invoice & Pembayaran)', 'Penerbitan invoice dari Work Order atau Penjualan Langsung, pembayaran cicilan bertahap, status Unpaid/Partial/Paid, cetak PDF, dan void invoice/pembayaran.', 'Admin (Lengkap + Void), Kasir (Buat & Bayar tanpa Void)')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Invoice &amp; Pembayaran</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 5</span>
        </button>
        @endcan

        @can('inventory.manage')
        <button type="button" 
                @click="openPhase('Barang Masuk & Mesin Stok (WAC)', 'Fase 3 (Mesin Stok)', 'Pencatatan pembelian barang masuk dari supplier, perhitungan otomatis Weighted Average Cost (WAC), penyesuaian stok opname, dan kartu stok tamper-evident.', 'Khusus Admin (Dilindungi Row Lock & DB Transaction)')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Barang Masuk / Stok</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 3</span>
        </button>
        @endcan

        @can('expenses.manage')
        <button type="button" 
                @click="openPhase('Pengeluaran Operasional', 'Fase 6 (Laporan & Keuangan)', 'Pencatatan biaya operasional bengkel: konsumsi, listrik, sewa alat bantu, dan gaji mekanik untuk perhitungan laba rugi bersih.', 'Khusus Admin')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Pengeluaran</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 6</span>
        </button>
        @endcan

        <!-- Laporan & Admin Section -->
        @can('reports.receivables.view')
        <div x-show="!collapsed" class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Laporan
        </div>
        @php
            $laporanTitle = Auth::user()?->role === 'cashier' ? 'Laporan Piutang' : 'Laporan Keuangan & Laba Rugi';
        @endphp
        <button type="button" 
                @click="openPhase('{{ $laporanTitle }}', 'Fase 6 (Laporan & Keuangan)', 'Laporan Omzet, Laba Rugi Sederhana, Arus Kas, dan Aging Piutang pelanggan (0-30, 31-60, 61-90, >90 hari) dengan ekspor Excel/PDF.', 'Admin (Semua Laporan), Kasir (Laporan Piutang Saja)')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Laporan {{ Auth::user()?->role === 'cashier' ? 'Piutang' : 'Keuangan' }}</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 6</span>
        </button>
        @endcan

        @can('settings.manage')
        <div x-show="!collapsed" class="pt-4 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Sistem
        </div>
        <button type="button" 
                @click="openPhase('Audit Log & Pengaturan Sistem', 'Fase 8 (Pengerasan & Sistem)', 'Jejak audit append-only yang mencatat seluruh perubahan data penting, perubahan harga, void invoice/pembayaran, serta konfigurasi PPN dan profil bengkel.', 'Khusus Admin')"
                class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors group">
            <div class="flex items-center">
                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Audit Log &amp; Pengaturan</span>
            </div>
            <span x-show="!collapsed" class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-slate-800 text-amber-400 border border-slate-700">Fase 8</span>
        </button>
        @endcan
    </nav>

    <!-- Logout Footer -->
    <div class="p-3 border-t border-slate-800">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" 
                    class="w-full flex items-center px-3 py-2 rounded-lg text-sm font-medium text-red-400 hover:bg-red-950/40 hover:text-red-300 transition-colors">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span x-show="!collapsed" class="ms-3">Keluar</span>
            </button>
        </form>
    </div>

    <!-- Phase Roadmap Information Modal -->
    <div x-show="showModal" 
         x-cloak
         style="display: none;" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative text-left"
             @click.outside="showModal = false">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-xl font-bold shrink-0">
                        📋
                    </div>
                    <div>
                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase" x-text="modalPhase"></span>
                        <h3 class="text-lg font-black text-white mt-1" x-text="modalTitle"></h3>
                    </div>
                </div>
                <button @click="showModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                    ✕
                </button>
            </div>
            
            <p class="text-sm text-slate-300 mb-4 leading-relaxed" x-text="modalDesc"></p>

            <div class="space-y-2.5 mb-6 text-xs">
                <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800 text-slate-300">
                    <span class="font-bold text-amber-400 uppercase tracking-wider block mb-1">Hak Akses Modul:</span>
                    <span x-text="modalRole"></span>
                </div>
                <div class="p-3 rounded-lg bg-amber-950/30 border border-amber-800/40 text-amber-200">
                    <span class="font-bold block mb-1">ℹ️ Roadmap PRD (docs/AGENTS.md):</span>
                    Sistem saat ini berada pada <strong>Fase 1 (Fondasi &amp; RBAC Selesai)</strong>. Sesuai panduan pengembangan PRD, modul bisnis dikerjakan bertahap satu per satu agar setiap modul teruji penuh dengan unit test. Modul ini siap diimplementasikan pada fase berikutnya!
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button @click="showModal = false" class="px-5 py-2.5 rounded-xl bg-amber-500 text-slate-950 font-bold text-sm hover:bg-amber-400 transition">
                    Mengerti &amp; Tutup
                </button>
            </div>
        </div>
    </div>
</aside>
