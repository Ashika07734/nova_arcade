@extends('layouts.app')

@section('title', 'Stats')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10">
        <div class="eyebrow">Game statistics</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Live and historical platform metrics from the Survival Arena backend.</h1>
    </section>

    <section class="grid gap-6 md:grid-cols-4">
        <div class="metric-card"><div class="text-sm text-slate-400">Users</div><div class="mt-2 text-3xl font-black text-white">{{ number_format($stats['total_users']) }}</div></div>
        <div class="metric-card"><div class="text-sm text-slate-400">Matches today</div><div class="mt-2 text-3xl font-black text-cyan-300">{{ number_format($stats['matches_today']) }}</div></div>
        <div class="metric-card"><div class="text-sm text-slate-400">Active matches</div><div class="mt-2 text-3xl font-black text-emerald-300">{{ number_format($stats['active_matches']) }}</div></div>
        <div class="metric-card"><div class="text-sm text-slate-400">Total kills</div><div class="mt-2 text-3xl font-black text-violet-300">{{ number_format($stats['total_kills']) }}</div></div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="panel p-6"><div class="eyebrow">Match duration</div><div class="mt-4 text-4xl font-black text-white">{{ $stats['average_match_duration'] }}</div><p class="mt-2 text-sm text-slate-400">Average completed match length.</p></div>
        <div class="panel p-6"><div class="eyebrow">Most used weapon</div><div class="mt-4 text-4xl font-black text-white">{{ $stats['most_used_weapon'] }}</div><p class="mt-2 text-sm text-slate-400">Weapon seen most often in match kills.</p></div>
        <div class="panel p-6"><div class="eyebrow">Longest kill</div><div class="mt-4 text-4xl font-black text-white">{{ $stats['longest_kill'] }}</div><p class="mt-2 text-sm text-slate-400">Best long-range elimination logged.</p></div>
    </section>
</div>
@endsection