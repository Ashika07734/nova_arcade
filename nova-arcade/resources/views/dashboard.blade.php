@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong overflow-hidden">
        <div class="grid gap-0 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="p-8 sm:p-10 lg:p-12">
                <div class="eyebrow">Player hub</div>
                <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Welcome back, {{ auth()->user()->username }}</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Track progression, review your missions, and drop into the next match from one hub.</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('survival-arena.matchmaking') }}" class="surface-button">Quick play</a>
                    <a href="{{ route('survival-arena.matches.create') }}" class="surface-button-secondary">Create match</a>
                    <a href="{{ route('inventory') }}" class="surface-button-secondary">Inventory</a>
                </div>
            </div>

            <div class="border-t border-slate-800/80 bg-slate-950/50 p-8 lg:border-l lg:border-t-0">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="metric-card">
                        <div class="text-sm text-slate-400">Matches</div>
                        <div class="mt-2 text-3xl font-black text-white">{{ number_format($stats->total_matches) }}</div>
                    </div>
                    <div class="metric-card">
                        <div class="text-sm text-slate-400">Wins</div>
                        <div class="mt-2 text-3xl font-black text-emerald-300">{{ number_format($stats->wins) }}</div>
                    </div>
                    <div class="metric-card">
                        <div class="text-sm text-slate-400">Kills</div>
                        <div class="mt-2 text-3xl font-black text-cyan-300">{{ number_format($stats->kills) }}</div>
                    </div>
                    <div class="metric-card">
                        <div class="text-sm text-slate-400">K/D</div>
                        <div class="mt-2 text-3xl font-black text-violet-300">{{ number_format($stats->kd_ratio, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-3">
        <a href="{{ route('survival-arena.matchmaking') }}" class="action-card">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Quick Play</div>
            <p class="mt-2 text-sm text-slate-400">Find a match instantly.</p>
        </a>
        <a href="{{ route('survival-arena.matches.create') }}" class="action-card">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Create Match</div>
            <p class="mt-2 text-sm text-slate-400">Host a private or public room.</p>
        </a>
        <a href="{{ route('inventory') }}" class="action-card">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Inventory</div>
            <p class="mt-2 text-sm text-slate-400">Manage cosmetics and unlocks.</p>
        </a>
    </section>

    <section class="grid gap-8 lg:grid-cols-3">
        <div class="panel p-6 lg:col-span-2">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="eyebrow">Daily missions</div>
                    <h2 class="mt-3 text-2xl font-black text-white">Complete objectives for bonus XP.</h2>
                </div>
                <div class="chip">{{ number_format($activeMatches) }} active matches</div>
            </div>

            <div class="mt-6 space-y-3">
                @forelse ($dailyMissions as $mission)
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="font-semibold text-white">{{ $mission->description }}</div>
                                <div class="text-sm text-slate-400">Reward: {{ number_format($mission->reward_xp) }} XP</div>
                            </div>
                            <div class="text-right text-sm {{ $mission->completed ? 'text-emerald-300' : 'text-slate-400' }}">
                                {{ $mission->progress }}/{{ $mission->target }}
                            </div>
                        </div>
                        <div class="mt-3 h-2 rounded-full bg-slate-800">
                            <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500" style="width: {{ min(100, ($mission->progress / max(1, $mission->target)) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/70 p-6 text-slate-400">Missions reset daily. Check back later for new objectives.</div>
                @endforelse
            </div>
        </div>

        <div class="panel p-6">
            <div class="eyebrow">Queue</div>
            <h2 class="mt-3 text-2xl font-black text-white">Live platform activity</h2>
            <div class="mt-6 rounded-2xl border border-cyan-400/20 bg-cyan-500/10 p-6">
                <div class="text-sm text-slate-300">Active matches</div>
                <div class="mt-2 text-5xl font-black text-cyan-300">{{ number_format($activeMatches) }}</div>
            </div>
            <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
                <div class="text-sm text-slate-400">Best stat</div>
                <div class="mt-2 text-xl font-bold text-white">{{ $stats->highest_kills_match }} kills in one match</div>
            </div>
        </div>
    </section>

    <section class="panel p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="eyebrow">Recent matches</div>
                <h2 class="mt-3 text-2xl font-black text-white">Your last five sessions.</h2>
            </div>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($recentMatches as $entry)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                    <div>
                        <div class="font-semibold text-white">Match {{ $entry->match->match_code ?? 'Unknown' }}</div>
                        <div class="text-sm text-slate-400">{{ ucfirst($entry->match->game_mode ?? 'solo') }} | {{ $entry->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-400">Placement</div>
                        <div class="text-xl font-black text-cyan-300">#{{ $entry->placement ?? '-' }}</div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/70 p-6 text-slate-400">No recent matches yet. Queue your first game from Quick Play.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
