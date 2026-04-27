@extends('layouts.app')

@section('title', 'Stats')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal">
        <div class="eyebrow">Game statistics</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Live and historical platform metrics from the Survival Arena backend.</h1>
    </section>

    <section class="grid gap-6 md:grid-cols-4">
        @foreach([
            ['label' => 'Users', 'value' => $stats['total_users'], 'color' => 'text-white'],
            ['label' => 'Matches today', 'value' => $stats['matches_today'], 'color' => 'text-cyan-300'],
            ['label' => 'Active matches', 'value' => $stats['active_matches'], 'color' => 'text-emerald-300'],
            ['label' => 'Total kills', 'value' => $stats['total_kills'], 'color' => 'text-violet-300'],
        ] as $i => $metric)
            <div class="metric-card scroll-reveal scroll-reveal-delay-{{ $i + 1 }}" data-tilt data-tilt-intensity="8">
                <div class="text-sm text-slate-400">{{ $metric['label'] }}</div>
                <div class="mt-2 text-3xl font-black {{ $metric['color'] }}">{{ number_format($metric['value']) }}</div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        @foreach([
            ['label' => 'Match duration', 'value' => $stats['average_match_duration'], 'desc' => 'Average completed match length.'],
            ['label' => 'Most used weapon', 'value' => $stats['most_used_weapon'], 'desc' => 'Weapon seen most often in match kills.'],
            ['label' => 'Longest kill', 'value' => $stats['longest_kill'], 'desc' => 'Best long-range elimination logged.'],
        ] as $i => $stat)
            <div class="panel p-6 scroll-reveal scroll-reveal-delay-{{ $i + 1 }}" data-tilt data-tilt-intensity="5">
                <div class="eyebrow">{{ $stat['label'] }}</div>
                <div class="mt-4 text-4xl font-black text-white">{{ $stat['value'] }}</div>
                <p class="mt-2 text-sm text-slate-400">{{ $stat['desc'] }}</p>
            </div>
        @endforeach
    </section>
</div>
@endsection