@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal">
        <div class="flex flex-col gap-6 md:flex-row md:items-center">
            <div class="relative">
                <img src="{{ $profileUser->avatar_url }}" alt="{{ $profileUser->username }}" class="h-28 w-28 rounded-3xl border-2 border-cyan-400/40 object-cover animate-float-3d" style="box-shadow: 0 0 40px rgba(34,211,238,0.18), 0 15px 35px rgba(0,0,0,0.4);">
                <div class="absolute inset-0 rounded-3xl" style="box-shadow: inset 0 0 20px rgba(34,211,238,0.1);"></div>
            </div>
            <div>
                <div class="eyebrow">Profile</div>
                <h1 class="hero-title mt-3 text-4xl sm:text-5xl">{{ $profileUser->username }}</h1>
                <p class="text-slate-400">{{ $profileUser->name }}</p>
                <p class="mt-4 max-w-3xl text-slate-300">{{ $profileUser->bio ?: 'This player has not added a bio yet.' }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-4">
        @foreach([
            ['label' => 'Matches', 'value' => $profileUser->stats->total_matches ?? 0, 'color' => 'text-white'],
            ['label' => 'Wins', 'value' => $profileUser->stats->wins ?? 0, 'color' => 'text-emerald-300'],
            ['label' => 'Kills', 'value' => $profileUser->stats->kills ?? 0, 'color' => 'text-cyan-300'],
            ['label' => 'K/D', 'value' => $profileUser->stats?->formatted_kd_ratio ?? '0.00', 'color' => 'text-violet-300', 'raw' => true],
        ] as $i => $stat)
            <div class="metric-card scroll-reveal scroll-reveal-delay-{{ $i + 1 }}" data-tilt data-tilt-intensity="8">
                <div class="text-sm text-slate-400">{{ $stat['label'] }}</div>
                <div class="mt-2 text-3xl font-black {{ $stat['color'] }}">{{ isset($stat['raw']) ? $stat['value'] : number_format($stat['value']) }}</div>
            </div>
        @endforeach
    </section>
</div>
@endsection
