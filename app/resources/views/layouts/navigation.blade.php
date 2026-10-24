<nav x-data="{ open: false }" class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
    <!-- Primary Top Bar Menu -->
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left Side: Mobile Toggle & Context Breadcrumb -->
            <div class="flex items-center space-x-3">
                <!-- Mobile Sidebar Toggle -->
                <button @click="$dispatch('toggle-mobile-sidebar')" 
                        class="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none transition lg:hidden"
                        aria-label="Buka Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Context Breadcrumb / Module Indicator -->
                <div class="flex items-center space-x-2 text-xs font-medium text-slate-500">
                    <span class="font-bold text-slate-900 tracking-tight text-sm">TRAK<span class="text-amber-600">ERP</span></span>
                    <span class="text-slate-300">/</span>
                    <span class="hidden sm:inline text-slate-600 font-semibold">Operasional Bengkel &amp; Sparepart</span>
                </div>
            </div>

            <!-- Right Side: Environment Status & User Menu -->
            <div class="flex items-center space-x-4">
                <!-- Live System Indicator -->
                <div class="hidden md:flex items-center space-x-2 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="font-medium">{{ \Carbon\Carbon::now()->translatedFormat('d M Y') }}</span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="font-mono text-[10px] text-slate-500">WIB</span>
                </div>

                <!-- Settings & Profile Dropdown -->
                <div class="flex items-center">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center space-x-2.5 p-1.5 sm:px-3 sm:py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 focus:outline-none transition group shadow-2xs">
                                <div class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-700 font-extrabold text-xs flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                                </div>

                                <div class="hidden sm:flex flex-col text-left">
                                    <span class="text-xs font-bold text-slate-800 leading-tight group-hover:text-amber-600 transition truncate max-w-[120px]">
                                        {{ Auth::user()->name }}
                                    </span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider
                                        @if(Auth::user()?->role === 'admin') text-rose-600
                                        @elseif(Auth::user()?->role === 'cashier') text-blue-600
                                        @else text-emerald-600 @endif">
                                        {{ Auth::user()?->role === 'admin' ? 'Administrator' : (Auth::user()?->role === 'cashier' ? 'Kasir' : 'Mekanik') }}
                                    </span>
                                </div>

                                <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2 border-b border-slate-100 text-xs">
                                <div class="font-bold text-slate-900 truncate">{{ Auth::user()->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email }}</div>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')" class="text-xs flex items-center space-x-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ __('Profil Pengguna') }}</span>
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        class="text-xs text-rose-600 hover:text-rose-700 flex items-center space-x-2"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>{{ __('Keluar') }}</span>
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>
</nav>
