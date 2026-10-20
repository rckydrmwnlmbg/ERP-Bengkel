<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- 1-Click Demo Login Selector -->
    <div class="mb-6 p-4 rounded-xl bg-slate-900 text-white shadow-md border border-slate-800">
        <div class="text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-2.5 flex items-center gap-1.5">
            <span>⚡ Akun Demo (1-Klik untuk Mengisi)</span>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <button type="button" 
                    onclick="document.getElementById('email').value='admin@bengkel.com'; document.getElementById('password').value='password';"
                    class="px-2 py-2 rounded-lg bg-red-950/80 hover:bg-red-900 border border-red-700/80 text-red-200 text-xs font-bold text-center transition shadow-sm">
                👑 Admin
            </button>
            <button type="button" 
                    onclick="document.getElementById('email').value='kasir@bengkel.com'; document.getElementById('password').value='password';"
                    class="px-2 py-2 rounded-lg bg-blue-950/80 hover:bg-blue-900 border border-blue-700/80 text-blue-200 text-xs font-bold text-center transition shadow-sm">
                💼 Kasir
            </button>
            <button type="button" 
                    onclick="document.getElementById('email').value='mekanik1@bengkel.com'; document.getElementById('password').value='password';"
                    class="px-2 py-2 rounded-lg bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-700/80 text-emerald-200 text-xs font-bold text-center transition shadow-sm">
                🔧 Mekanik
            </button>
        </div>
        <p class="mt-2 text-[11px] text-slate-400">
            Password: <code class="text-amber-400 font-mono font-bold">password</code> &bull; Klik salah satu tombol di atas untuk mengisi formulir secara instan.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
