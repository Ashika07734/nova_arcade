<nav class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
    <div class="page-shell">
        <div class="flex h-16 items-center justify-between gap-4">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 shadow-lg shadow-cyan-950/30">
                        <span class="text-sm font-black text-slate-950">SA</span>
                    </div>
                    <span class="hidden text-xl font-bold bg-gradient-to-r from-emerald-300 to-cyan-400 bg-clip-text text-transparent sm:inline-flex">
                        Survival Arena 3D
                    </span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white {{ request()->routeIs('dashboard') ? 'bg-white/8 text-white' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('survival-arena.matchmaking') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white {{ request()->routeIs('survival-arena.*') ? 'bg-white/8 text-white' : '' }}">
                        Play Now
                    </a>
                    <a href="{{ route('leaderboards') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white {{ request()->routeIs('leaderboards') ? 'bg-white/8 text-white' : '' }}">
                        Leaderboards
                    </a>
                    <a href="{{ route('inventory') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white {{ request()->routeIs('inventory') ? 'bg-white/8 text-white' : '' }}">
                        Inventory
                    </a>
                @else
                    <a href="{{ route('how-to-play') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white {{ request()->routeIs('how-to-play') ? 'bg-white/8 text-white' : '' }}">
                        How to Play
                    </a>
                    <a href="{{ route('leaderboards') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white {{ request()->routeIs('leaderboards') ? 'bg-white/8 text-white' : '' }}">
                        Leaderboards
                    </a>
                @endauth
            </div>

            <!-- User Menu -->
            <div class="flex items-center gap-3">
                @auth
                    <div class="relative" data-menu-root>
                        <button type="button" data-menu-button class="flex items-center gap-3 rounded-full border border-slate-800 bg-slate-900/70 px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-cyan-400/30">
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="h-8 w-8 rounded-full border border-emerald-400/60 object-cover">
                            <span class="hidden max-w-28 truncate text-sm font-semibold text-white sm:inline-flex">{{ auth()->user()->username }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>

                        <div data-menu-panel class="absolute right-0 mt-3 hidden w-56 rounded-2xl border border-slate-800 bg-slate-950/95 p-2 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
                            <a href="{{ route('profile.edit') }}" class="block rounded-xl px-4 py-3 text-sm text-slate-300 hover:bg-white/5 hover:text-white">Profile</a>
                            <a href="{{ route('settings') }}" class="block rounded-xl px-4 py-3 text-sm text-slate-300 hover:bg-white/5 hover:text-white">Settings</a>
                            <a href="{{ route('inventory') }}" class="block rounded-xl px-4 py-3 text-sm text-slate-300 hover:bg-white/5 hover:text-white">Inventory</a>
                            <hr class="my-2 border-slate-800">
                            <form method="POST" action="{{ route('logout') }}" class="px-1 pb-1">
                                @csrf
                                <button type="submit" class="w-full rounded-xl px-4 py-3 text-left text-sm font-semibold text-rose-300 hover:bg-rose-500/10">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="surface-button px-5 py-2.5 text-sm">
                        Sign Up Free
                    </a>
                @endauth

                <button type="button" data-mobile-menu-button class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-800 bg-slate-900/70 text-slate-300 lg:hidden">
                    <span class="sr-only">Toggle menu</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div data-mobile-menu class="hidden border-t border-slate-800 py-4 lg:hidden">
            <div class="grid gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Dashboard</a>
                    <a href="{{ route('survival-arena.matchmaking') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Play Now</a>
                    <a href="{{ route('leaderboards') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Leaderboards</a>
                    <a href="{{ route('inventory') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Inventory</a>
                    <a href="{{ route('profile.edit') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Profile</a>
                @else
                    <a href="{{ route('how-to-play') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">How to Play</a>
                    <a href="{{ route('leaderboards') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Leaderboards</a>
                    <a href="{{ route('login') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Login</a>
                    <a href="{{ route('register') }}" class="rounded-xl px-4 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Register</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mobileButton = document.querySelector('[data-mobile-menu-button]');
            const mobileMenu = document.querySelector('[data-mobile-menu]');
            const menuRoot = document.querySelector('[data-menu-root]');
            const menuButton = document.querySelector('[data-menu-button]');
            const menuPanel = document.querySelector('[data-menu-panel]');

            mobileButton?.addEventListener('click', () => {
                mobileMenu?.classList.toggle('hidden');
            });

            menuButton?.addEventListener('click', (event) => {
                event.stopPropagation();
                menuPanel?.classList.toggle('hidden');
            });

            document.addEventListener('click', (event) => {
                if (menuRoot && !menuRoot.contains(event.target)) {
                    menuPanel?.classList.add('hidden');
                }
            });
        });
    </script>
    @endpush
@endonce