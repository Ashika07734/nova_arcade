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
    <!-- Hero Strip -->
    <section class="panel-strong overflow-hidden dash-glass-main hero-strip scroll-reveal" data-tilt data-tilt-intensity="2">
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

    <!-- Quick Action Cards (3D tilt) -->
    <section class="grid gap-6 md:grid-cols-3">
        <a href="{{ route('survival-arena.matchmaking') }}" class="action-card dash-glass-card scroll-reveal" data-tilt data-tilt-intensity="8">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Quick Play</div>
            <p class="mt-2 text-sm text-slate-400">Find a match instantly.</p>
        </a>
        <a href="{{ route('survival-arena.matches.create') }}" class="action-card dash-glass-card scroll-reveal scroll-reveal-delay-1" data-tilt data-tilt-intensity="8">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Create Match</div>
            <p class="mt-2 text-sm text-slate-400">Host a private or public room.</p>
        </a>
        <a href="{{ route('inventory') }}" class="action-card dash-glass-card scroll-reveal scroll-reveal-delay-2" data-tilt data-tilt-intensity="8">
            <div class="eyebrow">Action</div>
            <div class="mt-3 text-2xl font-bold text-white">Inventory</div>
            <p class="mt-2 text-sm text-slate-400">Manage cosmetics and unlocks.</p>
        </a>
    </section>

    <!-- Stats & Missions -->
    <section class="grid gap-8 lg:grid-cols-3">
        <div class="panel p-6 lg:col-span-2 dash-glass-main mission-stack scroll-reveal">
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
                        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/40 p-4 backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.3)]">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-semibold text-white">{{ $mission->description }}</div>
                                    <div class="text-sm text-slate-400">Reward: {{ number_format($mission->reward_xp) }} XP</div>
                                </div>
                                <div class="text-right text-sm {{ $mission->completed ? 'text-emerald-300' : 'text-slate-400' }}">
                                    {{ $mission->progress }}/{{ $mission->target }}
                                </div>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-slate-800" style="box-shadow: var(--inner-depth);">
                                <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all duration-700" style="width: {{ min(100, ($mission->progress / max(1, $mission->target)) * 100) }}%; box-shadow: 0 0 10px rgba(16,185,129,0.3);"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/70 p-6 text-slate-400">Missions reset daily. Check back later for new objectives.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="panel p-6 dash-glass-main scroll-reveal scroll-reveal-delay-1">
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
                <div class="mini-metric border-cyan-400/20 bg-cyan-500/10 animate-glow-pulse">
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
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30">
                    <div class="text-sm text-slate-400">Best stat</div>
                    <div class="mt-2 text-xl font-bold text-white">{{ number_format((int) ($stats->highest_kills_match ?? 0)) }} kills in one match</div>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30">
                    <div class="text-sm text-slate-400">Total damage</div>
                    <div class="mt-2 text-xl font-bold text-white">{{ number_format($totalDamage) }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stat Metric Cards -->
    <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Matches', 'value' => $totalMatches, 'color' => 'text-white', 'desc' => 'Completed arenas played.'],
            ['label' => 'Kills', 'value' => $kills, 'color' => 'text-cyan-300', 'desc' => 'Total eliminations recorded.'],
            ['label' => 'Top 10s', 'value' => $topTen, 'color' => 'text-emerald-300', 'desc' => 'Consistent deep runs.'],
            ['label' => 'Deaths', 'value' => $deaths, 'color' => 'text-rose-300', 'desc' => 'Times eliminated in the arena.'],
        ] as $i => $metric)
            <div class="metric-card scroll-reveal scroll-reveal-delay-{{ $i + 1 }}">
                <div class="text-sm text-slate-400">{{ $metric['label'] }}</div>
                <div class="mt-2 text-3xl font-black {{ $metric['color'] }}">{{ number_format($metric['value']) }}</div>
                <div class="mt-2 text-sm text-slate-500">{{ $metric['desc'] }}</div>
            </div>
        @endforeach
    </section>

    <!-- Recent Matches -->
    <section class="panel p-6 dash-glass-main scroll-reveal">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="eyebrow">Recent matches</div>
                <h2 class="mt-3 text-2xl font-black text-white">Your last five sessions.</h2>
            </div>
        </div>

        <div class="mt-6 space-y-3">
            @forelse ($recentMatches as $entry)
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-700/70 bg-slate-900/40 p-4 backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.3)]">
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
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        transition: transform 0.5s ease;
    }

    .hero-center:hover {
        transform: scale(1.02);
    }

    .hero-stats-title {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: #cbd5e1;
        font-weight: 700;
        text-shadow: 0 0 10px rgba(34, 211, 238, 0.2);
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
        transition: all 0.3s ease;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .hero-stat-box:hover {
        border-color: rgba(34, 211, 238, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3), 0 0 15px rgba(34, 211, 238, 0.08);
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
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .hero-level-track {
        height: 0.5rem;
        border-radius: 999px;
        background: rgba(30, 41, 59, 0.95);
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
    }

    .hero-level-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #06b6d4, #22d3ee);
        box-shadow: 0 0 12px rgba(34, 211, 238, 0.4);
        transition: width 1s ease;
    }

    .stats-half { min-height: 48%; }
    .mission-half { min-height: 48%; }

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
        transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .hero-cta-primary {
        background: linear-gradient(90deg, #06b6d4, #22d3ee);
        color: #082f49;
        border-color: rgba(34, 211, 238, 0.6);
        box-shadow: 0 4px 15px rgba(34, 211, 238, 0.25);
    }

    .hero-cta-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(34, 211, 238, 0.35);
    }

    .hero-cta-primary:active {
        transform: translateY(1px) scale(0.98);
    }

    .hero-cta-secondary {
        background: rgba(15, 23, 42, 0.68);
        color: #e2e8f0;
    }

    .hero-cta-secondary:hover {
        transform: translateY(-2px);
        border-color: rgba(148, 163, 184, 0.4);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .hero-quick-stat,
    .mini-metric {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(15, 23, 42, 0.42);
        padding: 0.9rem 1rem;
        transition: all 0.3s ease;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .hero-quick-stat:hover,
    .mini-metric:hover {
        transform: translateY(-3px);
        border-color: rgba(34, 211, 238, 0.25);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
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
