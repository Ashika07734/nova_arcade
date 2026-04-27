@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $displayName = strtoupper(auth()->user()->username ?: auth()->user()->name ?: 'PLAYER');
    $totalMatches = (int) ($stats->total_matches ?? 0);
    $wins = (int) ($stats->wins ?? 0);
    $kills = (int) ($stats->kills ?? 0);
    $deaths = (int) ($stats->deaths ?? 0);
    $headshots = (int) ($stats->headshots ?? 0);
    $topFive = (int) ($stats->top_5 ?? 0);
    $topTen = (int) ($stats->top_10 ?? 0);
    $totalDamage = (int) ($stats->total_damage ?? 0);
    $level = max(1, (int) floor($totalMatches / 5) + 1);
    $xpCurrent = ($kills * 25) + ($wins * 120) + ($headshots * 15);
    $xpCap = 1200;
    $xpProgress = min(100, ($xpCurrent / max(1, $xpCap)) * 100);
    $winRate = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;
@endphp

<div class="page-shell page-section space-y-8 dash-bg">
    <section class="panel-strong overflow-hidden dash-glass-main hero-strip">
        <div class="hero-overlay"></div>
        <div class="hero-grid">
            <div class="hero-left">
                <div class="eyebrow">Welcome back,</div>
                <h1 class="hero-title mt-3 text-4xl sm:text-5xl">{{ $displayName }}</h1>
                <p class="mt-3 text-slate-300">Gear up, soldier! The arena is waiting.</p>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('survival-arena.matchmaking') }}" class="hero-cta-primary">Quick Play</a>
                    <a href="{{ route('survival-arena.matches.create') }}" class="hero-cta-secondary">Create Match</a>
                    <a href="{{ route('inventory') }}" class="hero-cta-secondary sm:col-span-2">Inventory</a>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="hero-quick-stat">
                        <span>Level</span>
                        <strong>{{ $level }}</strong>
                    </div>
                    <div class="hero-quick-stat">
                        <span>Win rate</span>
                        <strong>{{ number_format($winRate, 1) }}%</strong>
                    </div>
                    <div class="hero-quick-stat">
                        <span>Playtime</span>
                        <strong>{{ $stats->formatted_playtime }}</strong>
                    </div>
                </div>
            </div>

            <div class="hero-center"></div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-3">
        <a href="{{ route('survival-arena.matchmaking') }}" class="action-card dash-glass-card">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Quick Play</div>
            <p class="mt-2 text-sm text-slate-400">Find a match instantly.</p>
        </a>
        <a href="{{ route('survival-arena.matches.create') }}" class="action-card dash-glass-card">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Create Match</div>
            <p class="mt-2 text-sm text-slate-400">Host a private or public room.</p>
        </a>
        <a href="{{ route('inventory') }}" class="action-card dash-glass-card">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Inventory</div>
            <p class="mt-2 text-sm text-slate-400">Manage cosmetics and unlocks.</p>
        </a>
    </section>

    <section class="grid gap-8 lg:grid-cols-3">
        <div class="panel p-6 lg:col-span-2 dash-glass-main mission-stack">
            <div class="stats-half">
                <div class="hero-stats-title">Player Stats</div>
                <div class="hero-stat-grid">
                    <div class="hero-stat-box">
                        <span>Matches</span>
                        <strong>{{ number_format($stats->total_matches) }}</strong>
                    </div>
                    <div class="hero-stat-box">
                        <span>Wins</span>
                        <strong>{{ number_format($stats->wins) }}</strong>
                    </div>
                    <div class="hero-stat-box">
                        <span>Kills</span>
                        <strong>{{ number_format($stats->kills) }}</strong>
                    </div>
                    <div class="hero-stat-box">
                        <span>K/D Ratio</span>
                        <strong>{{ number_format($stats->kd_ratio, 2) }}</strong>
                    </div>
                </div>

                <div class="hero-level mt-4">
                    <div class="flex items-center justify-between text-sm text-slate-300">
                        <span>Level {{ $level }}</span>
                        <span>{{ number_format($xpCurrent) }} / {{ number_format($xpCap) }} XP</span>
                    </div>
                    <div class="hero-level-track mt-2">
                        <div class="hero-level-fill" style="width: {{ $xpProgress }}%"></div>
                    </div>
                </div>
            </div>

            <div class="mission-half mt-6 border-t border-slate-700/70 pt-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="eyebrow">Daily missions</div>
                        <h2 class="mt-3 text-2xl font-black text-white">Complete objectives for bonus XP.</h2>
                    </div>
                    <div class="chip">{{ number_format($activeMatches) }} active matches</div>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($dailyMissions as $mission)
                        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/40 p-4 backdrop-blur-md">
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
        </div>

        <div class="panel p-6 dash-glass-main">
            <div class="eyebrow">Queue</div>
            <h2 class="mt-3 text-2xl font-black text-white">Live platform activity</h2>
            <div class="mt-5 overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/80">
                <img
                    src="{{ asset('assets/images/soldier.png') }}"
                    alt="Soldier artwork"
                    class="h-48 w-full object-cover object-center"
                >
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="mini-metric border-cyan-400/20 bg-cyan-500/10">
                    <div class="text-sm text-slate-300">Active matches</div>
                    <div class="mt-2 text-4xl font-black text-cyan-300">{{ number_format($activeMatches) }}</div>
                </div>
                <div class="mini-metric border-emerald-400/20 bg-emerald-500/10">
                    <div class="text-sm text-slate-300">Win rate</div>
                    <div class="mt-2 text-4xl font-black text-emerald-300">{{ number_format($winRate, 1) }}%</div>
                </div>
                <div class="mini-metric border-violet-400/20 bg-violet-500/10">
                    <div class="text-sm text-slate-300">Top 5 finishes</div>
                    <div class="mt-2 text-4xl font-black text-violet-300">{{ number_format($topFive) }}</div>
                </div>
                <div class="mini-metric border-amber-400/20 bg-amber-500/10">
                    <div class="text-sm text-slate-300">Headshots</div>
                    <div class="mt-2 text-4xl font-black text-amber-300">{{ number_format($headshots) }}</div>
                </div>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
                    <div class="text-sm text-slate-400">Best stat</div>
                    <div class="mt-2 text-xl font-bold text-white">{{ number_format((int) ($stats->highest_kills_match ?? 0)) }} kills in one match</div>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
                    <div class="text-sm text-slate-400">Total damage</div>
                    <div class="mt-2 text-xl font-bold text-white">{{ number_format($totalDamage) }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card">
            <div class="text-sm text-slate-400">Matches</div>
            <div class="mt-2 text-3xl font-black text-white">{{ number_format($totalMatches) }}</div>
            <div class="mt-2 text-sm text-slate-500">Completed arenas played.</div>
        </div>
        <div class="metric-card">
            <div class="text-sm text-slate-400">Kills</div>
            <div class="mt-2 text-3xl font-black text-cyan-300">{{ number_format($kills) }}</div>
            <div class="mt-2 text-sm text-slate-500">Total eliminations recorded.</div>
        </div>
        <div class="metric-card">
            <div class="text-sm text-slate-400">Top 10s</div>
            <div class="mt-2 text-3xl font-black text-emerald-300">{{ number_format($topTen) }}</div>
            <div class="mt-2 text-sm text-slate-500">Consistent deep runs.</div>
        </div>
        <div class="metric-card">
            <div class="text-sm text-slate-400">Deaths</div>
            <div class="mt-2 text-3xl font-black text-rose-300">{{ number_format($deaths) }}</div>
            <div class="mt-2 text-sm text-slate-500">Times eliminated in the arena.</div>
        </div>
    </section>

    <section class="panel p-6 dash-glass-main">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="eyebrow">Recent matches</div>
                <h2 class="mt-3 text-2xl font-black text-white">Your last five sessions.</h2>
            </div>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($recentMatches as $entry)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-700/70 bg-slate-900/40 p-4 backdrop-blur-md">
                    <div>
                        <div class="font-semibold text-white">Match {{ $entry->match?->match_code ?? 'Unknown' }}</div>
                        <div class="text-sm text-slate-400">{{ ucfirst($entry->match?->game_mode ?? 'solo') }} | {{ $entry->created_at->diffForHumans() }} | {{ number_format((int) ($entry->kills ?? 0)) }} kills</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-400">Placement</div>
                        <div class="text-xl font-black text-cyan-300">#{{ $entry->placement ?? '-' }}</div>
                        <div class="text-sm text-slate-400">{{ $entry->formatted_survival_time ?? '00:00' }} survival</div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/70 p-6 text-slate-400">No recent matches yet. Queue your first game from Quick Play.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .dash-bg {
        position: relative;
        border-radius: 1.75rem;
        padding: 1.25rem;
        overflow: hidden;
        background:
            linear-gradient(180deg, rgba(2, 6, 23, 0.68), rgba(2, 6, 23, 0.78));
        box-shadow: 0 18px 60px rgba(2, 6, 23, 0.45);
    }

    .dash-glass-main {
        background: rgba(15, 23, 42, 0.38) !important;
        border: 1px solid rgba(148, 163, 184, 0.22) !important;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: 0 10px 35px rgba(2, 6, 23, 0.28);
    }

    .dash-glass-card {
        background: rgba(15, 23, 42, 0.34) !important;
        border: 1px solid rgba(148, 163, 184, 0.2) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .hero-strip {
        position: relative;
        border: 1px solid rgba(56, 189, 248, 0.35) !important;
        background:
            linear-gradient(90deg, rgba(2, 6, 23, 0.78) 0%, rgba(2, 6, 23, 0.55) 45%, rgba(2, 6, 23, 0.8) 100%),
            url("{{ asset('assets/images/' . rawurlencode('ChatGPT Image Apr 21, 2026, 09_19_29 PM.png')) }}") center/cover no-repeat;
        min-height: 380px;
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at center, rgba(56, 189, 248, 0.14), transparent 55%);
        pointer-events: none;
    }

    .hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 1rem;
        min-height: 380px;
        padding: 1.5rem;
    }

    .hero-left {
        position: relative;
        padding: 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(56, 189, 248, 0.22);
        overflow: hidden;
        background: rgba(2, 6, 23, 0.45);
    }

    .hero-left::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(120deg, rgba(2, 6, 23, 0.78), rgba(2, 6, 23, 0.52)),
            url("{{ asset('assets/images/' . rawurlencode('ChatGPT Image Apr 21, 2026, 09_19_29 PM.png')) }}") center/cover no-repeat;
        z-index: -1;
    }

    .hero-center {
        min-height: 300px;
        border-radius: 1rem;
        border: 1px solid rgba(56, 189, 248, 0.22);
        background:
            linear-gradient(160deg, rgba(2, 6, 23, 0.48), rgba(2, 6, 23, 0.7)),
            url("{{ asset('assets/images/soldier.png') }}") center/cover no-repeat;
    }

    .hero-stats-title {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: #cbd5e1;
        font-weight: 700;
    }

    .hero-stat-grid {
        margin-top: 0.75rem;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.6rem;
    }

    .hero-stat-box {
        border-radius: 0.7rem;
        border: 1px solid rgba(51, 65, 85, 0.8);
        background: rgba(15, 23, 42, 0.5);
        padding: 0.7rem;
    }

    .hero-stat-box span {
        display: block;
        color: #94a3b8;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.14em;
    }

    .hero-stat-box strong {
        display: block;
        color: #f8fafc;
        font-size: 1.55rem;
        line-height: 1.1;
        margin-top: 0.25rem;
        font-family: 'Orbitron', 'Rajdhani', sans-serif;
    }

    .hero-level {
        border-radius: 0.75rem;
        border: 1px solid rgba(51, 65, 85, 0.8);
        background: rgba(15, 23, 42, 0.5);
        padding: 0.7rem;
    }

    .hero-level-track {
        height: 0.5rem;
        border-radius: 999px;
        background: rgba(30, 41, 59, 0.95);
        overflow: hidden;
    }

    .hero-level-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #06b6d4, #22d3ee);
    }

    .stats-half {
        min-height: 48%;
    }

    .mission-half {
        min-height: 48%;
    }

    .hero-cta-primary,
    .hero-cta-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        padding: 0.85rem 1rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 0.76rem;
        border: 1px solid rgba(51, 65, 85, 0.8);
    }

    .hero-cta-primary {
        background: linear-gradient(90deg, #06b6d4, #22d3ee);
        color: #082f49;
        border-color: rgba(34, 211, 238, 0.6);
    }

    .hero-cta-secondary {
        background: rgba(15, 23, 42, 0.68);
        color: #e2e8f0;
    }

    .hero-quick-stat,
    .mini-metric,
    .metric-card {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(15, 23, 42, 0.42);
        padding: 0.9rem 1rem;
    }

    .hero-quick-stat span {
        display: block;
        color: #94a3b8;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.14em;
    }

    .hero-quick-stat strong {
        display: block;
        margin-top: 0.25rem;
        color: #f8fafc;
        font-size: 1.3rem;
        font-family: 'Orbitron', 'Rajdhani', sans-serif;
    }

    @media (max-width: 1200px) {
        .hero-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
