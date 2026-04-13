@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10">
        <div class="flex flex-col gap-6 md:flex-row md:items-center">
            <img src="{{ $profileUser->avatar_url }}" alt="{{ $profileUser->username }}" class="h-28 w-28 rounded-3xl border border-cyan-400/40 object-cover shadow-[0_0_40px_rgba(34,211,238,0.18)]">
            <div>
                <div class="eyebrow">Profile</div>
                <h1 class="hero-title mt-3 text-4xl sm:text-5xl">{{ $profileUser->username }}</h1>
                <p class="text-slate-400">{{ $profileUser->name }}</p>
                <p class="mt-4 max-w-3xl text-slate-300">{{ $profileUser->bio ?: 'This player has not added a bio yet.' }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-4">
        <div class="metric-card"><div class="text-sm text-slate-400">Matches</div><div class="mt-2 text-3xl font-black text-white">{{ number_format($profileUser->stats->total_matches ?? 0) }}</div></div>
        <div class="metric-card"><div class="text-sm text-slate-400">Wins</div><div class="mt-2 text-3xl font-black text-emerald-300">{{ number_format($profileUser->stats->wins ?? 0) }}</div></div>
        <div class="metric-card"><div class="text-sm text-slate-400">Kills</div><div class="mt-2 text-3xl font-black text-cyan-300">{{ number_format($profileUser->stats->kills ?? 0) }}</div></div>
        <div class="metric-card"><div class="text-sm text-slate-400">K/D</div><div class="mt-2 text-3xl font-black text-violet-300">{{ $profileUser->stats?->formatted_kd_ratio ?? '0.00' }}</div></div>
    </section>
</div>
@endsection

