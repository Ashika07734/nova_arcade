<footer class="mt-16 border-t border-slate-800/80 bg-slate-950/90 relative overflow-hidden">
    <!-- Scan line effect -->
    <div class="absolute inset-0 pointer-events-none scan-overlay" style="z-index:1;"></div>

    <div class="page-shell py-12 relative z-10">
        <!-- CTA Banner with 3D tilt -->
        <div class="mb-10 grid gap-6 rounded-[2rem] border border-cyan-400/15 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 p-6 lg:grid-cols-[1.6fr_1fr] lg:items-center scroll-reveal" data-tilt data-tilt-intensity="4" style="box-shadow: 0 15px 40px rgba(0,0,0,0.3), 0 0 30px rgba(34,211,238,0.05);">
            <div>
                <div class="eyebrow">The arena never sleeps</div>
                <h3 class="mt-3 text-3xl font-black text-white">Drop in for a match, or keep building your loadout between fights.</h3>
                <p class="mt-3 max-w-2xl text-sm text-slate-400">Persistent stats, profile progression, and real-time game sessions are all tied together through the same account.</p>
            </div>
            <div class="flex flex-wrap gap-3 lg:justify-end">
                <a href="{{ route('survival-arena.matchmaking') }}" class="surface-button">Play now</a>
                <a href="{{ route('leaderboards') }}" class="surface-button-secondary">View leaderboards</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
            <!-- About -->
            <div class="scroll-reveal">
                <h3 class="mb-4 text-xl font-bold bg-gradient-to-r from-emerald-300 to-cyan-400 bg-clip-text text-transparent">
                    Survival Arena 3D
                </h3>
                <p class="text-sm text-slate-400">
                    The ultimate browser-based battle royale experience. Fight, survive, conquer.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="scroll-reveal scroll-reveal-delay-1">
                <h4 class="mb-4 font-semibold text-white">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="text-slate-400 hover:text-white hover:translate-x-1 inline-block transition-all">About</a></li>
                    <li><a href="{{ route('how-to-play') }}" class="text-slate-400 hover:text-white hover:translate-x-1 inline-block transition-all">How to Play</a></li>
                    <li><a href="{{ route('faq') }}" class="text-slate-400 hover:text-white hover:translate-x-1 inline-block transition-all">FAQ</a></li>
                    <li><a href="{{ route('contact') }}" class="text-slate-400 hover:text-white hover:translate-x-1 inline-block transition-all">Contact</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div class="scroll-reveal scroll-reveal-delay-2">
                <h4 class="mb-4 font-semibold text-white">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('privacy') }}" class="text-slate-400 hover:text-white hover:translate-x-1 inline-block transition-all">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-slate-400 hover:text-white hover:translate-x-1 inline-block transition-all">Terms of Service</a></li>
                </ul>
            </div>

            <!-- Stats -->
            <div class="scroll-reveal scroll-reveal-delay-3">
                <h4 class="mb-4 font-semibold text-white">Live Stats</h4>
                <ul class="space-y-2 text-sm">
                    <li class="text-slate-400">
                        Active Games: <span class="text-green-400 font-bold">{{ \App\Models\SurvivalArena\ArenaMatch::active()->count() }}</span>
                    </li>
                    <li class="text-slate-400">
                        Players Online: <span class="text-cyan-400 font-bold">{{ \App\Models\SurvivalArena\ArenaMatch::active()->sum('current_players') }}</span>
                    </li>
                    <li class="text-slate-400">
                        Total Players: <span class="text-purple-400 font-bold">{{ \App\Models\User::count() }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Copyright -->
        <div class="mt-8 border-t border-slate-800 pt-8 text-center">
            <p class="text-sm text-slate-500">
                Copyright {{ date('Y') }} Survival Arena 3D. All rights reserved. | Built with Laravel and Three.js
            </p>
        </div>
    </div>
</footer>