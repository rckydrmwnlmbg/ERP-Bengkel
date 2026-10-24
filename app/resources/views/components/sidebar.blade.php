<aside x-data="{ collapsed: false, mobileOpen: false }" 
       @toggle-mobile-sidebar.window="mobileOpen = !mobileOpen"
       :class="{
           'w-20': collapsed && !mobileOpen,
           'w-64': !collapsed || mobileOpen,
           'translate-x-0': mobileOpen,
           '-translate-x-full lg:translate-x-0': !mobileOpen
       }" 
       class="fixed lg:static inset-y-0 left-0 z-40 transition-all duration-300 ease-in-out bg-slate-950 text-slate-200 min-h-screen flex flex-col shrink-0 border-r border-slate-800/80 shadow-xl">
    
    <!-- Sidebar Brand Header -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800/80 bg-slate-950">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 overflow-hidden">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center p-2 shrink-0">
                <x-application-logo class="w-full h-full text-amber-500" />
            </div>
            <div x-show="!collapsed" class="transition-opacity duration-300 truncate">
                <div class="flex items-center space-x-1">
                    <span class="text-base font-extrabold tracking-tight text-white">TRAK<span class="text-amber-500">ERP</span></span>
                </div>
                <div class="text-[10px] font-semibold text-slate-400 tracking-wider uppercase">Bengkel &amp; Parts</div>
            </div>
        </a>

        <!-- Collapse Toggle (Desktop) -->
        <button @click="collapsed = !collapsed" 
                class="hidden lg:flex p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white transition focus:outline-none"
                title="Sembunyikan / Tampilkan Menu">
            <svg class="w-4 h-4 transition-transform duration-300" :class="collapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
        </button>

        <!-- Close Toggle (Mobile) -->
        <button @click="mobileOpen = false" 
                class="lg:hidden p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white transition focus:outline-none"
                title="Tutup Menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- User Profile Strip -->
    <div class="p-3.5 border-b border-slate-800/80 bg-slate-900/40">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-xs flex items-center justify-center shrink-0">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            </div>
            <div x-show="!collapsed" class="overflow-hidden min-w-0">
                <div class="text-xs font-bold text-white truncate">{{ Auth::user()->name ?? 'Pengguna' }}</div>
                <div class="inline-flex items-center mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider
                    @if(Auth::user()?->role === 'admin') bg-rose-500/10 text-rose-400 border border-rose-500/20
                    @elseif(Auth::user()?->role === 'cashier') bg-blue-500/10 text-blue-400 border border-blue-500/20
                    @else bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 @endif">
                    {{ Auth::user()?->role === 'admin' ? 'Administrator' : (Auth::user()?->role === 'cashier' ? 'Kasir' : 'Mekanik') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('dashboard') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-amber-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Dashboard</span>
        </a>

        <!-- Master Data Section -->
        @can('master-data.view')
        <div x-show="!collapsed" class="pt-5 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Master Data
        </div>
        <a href="{{ route('customers.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('customers.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Pelanggan &amp; Unit</span>
        </a>
        <a href="{{ route('parts.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('parts.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Suku Cadang</span>
        </a>
        <a href="{{ route('services.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('services.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Tarif Jasa Servis</span>
        </a>
        <a href="{{ route('suppliers.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('suppliers.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Pemasok / Supplier</span>
        </a>
        @endcan

        <!-- Operasional Section -->
        <div x-show="!collapsed" class="pt-5 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Operasional Bengkel
        </div>
        <a href="{{ route('work-orders.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('work-orders.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Surat Perintah Kerja (SPK)</span>
        </a>
        @can('direct-sales.manage')
        <a href="{{ route('direct-sales.create') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('direct-sales.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Penjualan Langsung</span>
        </a>
        @endcan

        <!-- Keuangan Section -->
        @can('invoices.manage')
        <div x-show="!collapsed" class="pt-5 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Keuangan &amp; Penagihan
        </div>
        <a href="{{ route('invoices.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('invoices.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Faktur &amp; Pembayaran</span>
        </a>
        <a href="{{ route('receivables.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('receivables.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Buku Piutang Dagang</span>
        </a>
        @endcan

        <!-- Inventaris & Stok -->
        @can('inventory.manage')
        <div x-show="!collapsed" class="pt-5 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Inventaris &amp; Stok
        </div>
        <a href="{{ route('purchases.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('purchases.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Barang Masuk (Beli)</span>
        </a>
        <a href="{{ route('stock-adjustments.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('stock-adjustments.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Penyesuaian Stok Opname</span>
        </a>
        @endcan

        @can('expenses.manage')
        <div x-show="!collapsed" class="pt-5 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Biaya Operasional
        </div>
        <a href="{{ route('expenses.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('expenses.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Beban Pengeluaran</span>
        </a>
        @endcan

        <!-- Laporan Section -->
        @can('reports.receivables.view')
        <div x-show="!collapsed" class="pt-5 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Laporan Keuangan
        </div>
        @can('reports.view')
        <a href="{{ route('reports.revenue') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('reports.revenue') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Laporan Omzet</span>
        </a>
        <a href="{{ route('reports.profit-loss') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('reports.profit-loss') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m0 0l-6-6m6 6H3" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Laba Rugi</span>
        </a>
        <a href="{{ route('reports.cash-flow') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('reports.cash-flow') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Arus Kas</span>
        </a>
        @endcan
        <a href="{{ route('reports.receivables') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('reports.receivables') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Analisis Aging Piutang</span>
        </a>
        @endcan

        <!-- Sistem & Kontrol Section -->
        @can('audit-logs.view')
        <div x-show="!collapsed" class="pt-5 pb-1.5 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
            Sistem &amp; Kontrol
        </div>
        <a href="{{ route('audit-logs.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('audit-logs.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Audit Log Aktivitas</span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('settings.index') }}" 
           class="flex items-center px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150
                  {{ request()->routeIs('settings.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/80' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="!collapsed" class="ms-3 truncate">Pengaturan Sistem</span>
        </a>
        @endcan
    </nav>

    <!-- Logout Area -->
    <div class="p-3 border-t border-slate-800/80 bg-slate-950">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" 
                    class="w-full flex items-center px-3 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span x-show="!collapsed" class="ms-3 truncate">Keluar dari Sistem</span>
            </button>
        </form>
    </div>
</aside>
