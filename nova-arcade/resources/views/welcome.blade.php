@extends('layouts.app')

@section('title', 'Home')

@section('content')
@php
    $featuredMode = $gameModes[0] ?? null;
    $featuredPlayer = collect($topPlayers)->first();
@endphp

<div class="page-shell page-section space-y-10">
    <section class="panel-strong overflow-hidden">
        <div class="grid gap-0 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="p-8 sm:p-10 lg:p-14">
                <div class="chip mb-5 w-fit">Browser battle royale</div>
                <h1 class="hero-title max-w-4xl text-5xl leading-tight sm:text-6xl lg:text-7xl">
                    Survive the arena, earn the crown, keep your loadout.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                    Join fast matches, track progression, and jump straight into the next fight without leaving the browser.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('survival-arena.matchmaking') }}" class="surface-button text-base">Play now</a>
                    @else
                        <a href="{{ route('register') }}" class="surface-button text-base">Start free</a>
                        <a href="{{ route('how-to-play') }}" class="surface-button-secondary text-base">How to play</a>
                    @endauth
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div class="metric-card">
                        <div class="text-sm text-slate-400">Active matches</div>
                        <div class="mt-2 text-3xl font-black text-cyan-300">{{ number_format($activeMatches) }}</div>
                    </div>
                    <div class="metric-card">
                        <div class="text-sm text-slate-400">Players online</div>
                        <div class="mt-2 text-3xl font-black text-emerald-300">{{ number_format($onlinePlayers) }}</div>
                    </div>
                    <div class="metric-card">
                        <div class="text-sm text-slate-400">Total players</div>
                        <div class="mt-2 text-3xl font-black text-violet-300">{{ number_format($totalPlayers) }}</div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800/80 bg-slate-950/50 p-8 sm:p-10 lg:border-l lg:border-t-0 lg:p-12">
                <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/80 p-6">
                    <div class="eyebrow">Live snapshot</div>
                    <div class="mt-4 space-y-4 text-sm text-slate-300">
                        <div class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                            <span>Total finished matches</span>
                            <span class="font-bold text-white">{{ number_format($totalMatches) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3">
                            <span>Matchmaking pool</span>
                            <span class="font-bold text-cyan-300">Solo / Duo / Squad</span>
                        </div>
                        <div class="rounded-2xl border border-cyan-400/20 bg-cyan-500/10 p-4 text-cyan-100">
                            The arena rotates zone pressure, loot, and ranking pressure into a short-session loop.
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="action-card">
                        <div class="text-xs uppercase tracking-[0.3em] text-cyan-300">Current mode</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ $featuredMode['name'] ?? 'Solo' }}</div>
                        <p class="mt-2 text-sm text-slate-400">{{ $featuredMode['description'] ?? 'Join a fast solo match and start climbing.' }}</p>
                    </div>
                    <div class="action-card">
                        <div class="text-xs uppercase tracking-[0.3em] text-emerald-300">Best player</div>
                        <div class="mt-2 text-2xl font-bold text-white">{{ $featuredPlayer?->user?->username ?? 'N/A' }}</div>
                        <p class="mt-2 text-sm text-slate-400">{{ $featuredPlayer?->score ?? 0 }} wins on the all-time board</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-3">
        @foreach([
            ['icon' => '01', 'title' => 'Real-time Combat', 'desc' => 'Smooth 3D gameplay and immediate state updates keep each match fast and readable.'],
            ['icon' => '02', 'title' => 'Match Persistence', 'desc' => 'Stats, inventory, and recent match history are stored on the account.'],
            ['icon' => '03', 'title' => 'Competitive Loop', 'desc' => 'Leaderboards and progression make every session feed the next one.'],
        ] as $feature)
            <article class="action-card">
                <div class="text-3xl font-black text-cyan-300">{{ $feature['icon'] }}</div>
                <h2 class="mt-4 text-2xl font-bold text-white">{{ $feature['title'] }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-400">{{ $feature['desc'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="panel p-6 sm:p-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <div class="eyebrow">Game modes</div>
                    <h2 class="mt-3 text-3xl font-black text-white">Jump into the lobby that fits your squad size.</h2>
                </div>
                <a href="{{ route('survival-arena.matchmaking') }}" class="surface-button-secondary hidden sm:inline-flex">Matchmake</a>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach($gameModes as $mode)
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5 {{ $mode['available'] ? 'hover:border-cyan-400/50' : 'opacity-70' }} transition">
                        <div class="text-sm uppercase tracking-[0.3em] text-cyan-300">{{ $mode['icon'] }}</div>
                        <div class="mt-3 text-xl font-bold text-white">{{ $mode['name'] }}</div>
                        <p class="mt-2 text-sm leading-6 text-slate-400">{{ $mode['description'] }}</p>
                        <div class="mt-4 text-xs uppercase tracking-[0.28em] text-slate-500">{{ $mode['players'] }} players</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel p-6 sm:p-8">
            <div class="eyebrow">Hall of fame</div>
            <h2 class="mt-3 text-3xl font-black text-white">Top warriors this season.</h2>

            <div class="mt-6 space-y-4">
                @foreach($topPlayers as $index => $entry)
                    <div class="flex items-center gap-4 rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-700 bg-slate-900 text-lg font-black text-cyan-300">
                            {{ $index + 1 }}
                        </div>
                        <img src="{{ $entry->user->avatar_url }}" alt="{{ $entry->user->username }}" class="h-12 w-12 rounded-full border border-slate-700 object-cover">
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-bold text-white">{{ $entry->user->username }}</div>
                            <div class="truncate text-sm text-slate-400">{{ $entry->user->name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-emerald-300">{{ $entry->score }}</div>
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-500">Wins</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="panel p-6 sm:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="eyebrow">Recent matches</div>
                <h2 class="mt-3 text-3xl font-black text-white">Recent battle outcomes.</h2>
            </div>
            <a href="{{ route('survival-arena.matchmaking') }}" class="surface-button-secondary">Join the next match</a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($recentMatches as $match)
                <article class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-cyan-300">{{ ucfirst($match->game_mode) }}</div>
                            <div class="mt-2 text-xl font-bold text-white">Match {{ $match->match_code }}</div>
                        </div>
                        <div class="rounded-full border border-slate-700 px-3 py-1 text-xs text-slate-300">{{ $match->players->count() }} players</div>
                    </div>
                    <div class="mt-4 text-sm text-slate-400">Winner: <span class="text-slate-200">{{ $match->winner?->username ?? 'Unknown' }}</span></div>
                    <div class="mt-2 text-sm text-slate-400">Finished {{ $match->ended_at?->diffForHumans() ?? 'recently' }}</div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/70 p-6 text-slate-400">No finished matches yet.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
</style>
@endpush