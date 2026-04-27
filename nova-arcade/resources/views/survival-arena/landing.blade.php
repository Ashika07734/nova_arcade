@extends('layouts.app')

@section('title', 'Survival Arena')

@section('content')
<div class="page-shell page-section space-y-10">
    <div class="panel-strong overflow-hidden p-10 scroll-reveal" data-tilt data-tilt-intensity="3" style="box-shadow: 0 25px 60px rgba(0,0,0,0.4), 0 0 40px rgba(34,211,238,0.06);">
        <div class="eyebrow">Survival Arena 3D</div>
        <h1 class="hero-title mt-4 text-5xl leading-tight md:text-7xl">Drop in, gear up, and outlast the lobby.</h1>
        <p class="mt-6 max-w-3xl text-lg text-slate-300">Fast matchmaking, persistent progression, and a shrinking safe zone combine into a browser battle royale that plays like a real live-service game.</p>
        <div class="mt-8 flex flex-wrap gap-4">
            <a href="{{ route('survival-arena.matchmaking') }}" class="surface-button">Play now</a>
            <a href="{{ route('leaderboards') }}" class="surface-button-secondary">Leaderboards</a>
            <a href="{{ route('inventory') }}" class="surface-button-secondary">Inventory</a>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach([
            ['label' => 'Active matches', 'value' => number_format($activeMatches), 'color' => 'text-cyan-300'],
            ['label' => 'Players online', 'value' => number_format($totalPlayers), 'color' => 'text-emerald-300'],
            ['label' => 'Game mode', 'value' => 'Solo / Duo / Squad', 'color' => 'text-violet-300'],
        ] as $i => $stat)
            <div class="metric-card scroll-reveal scroll-reveal-delay-{{ $i + 1 }}" data-tilt data-tilt-intensity="8">
                <div class="text-sm text-slate-400">{{ $stat['label'] }}</div>
                <div class="mt-2 text-4xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
