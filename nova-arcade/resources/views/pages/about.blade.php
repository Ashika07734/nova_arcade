@extends('layouts.app')

@section('title', 'About')

@section('content')
<div class="page-shell page-section space-y-8">
    <div class="panel-strong p-10 scroll-reveal" data-tilt data-tilt-intensity="3">
        <div class="eyebrow">About the game</div>
        <h1 class="hero-title mt-4 text-4xl">A browser battle royale built for fast sessions.</h1>
        <p class="mt-4 text-lg text-slate-300">Survival Arena 3D combines instant matchmaking, live stats, loot progression, and a lightweight Three.js game loop so players can jump in and compete without a launcher.</p>
    </div>
    <div class="grid gap-6 md:grid-cols-3">
        @foreach([
            ['title' => 'Real-time play', 'desc' => 'Responsive combat, zone pressure, and multiplayer state updates.'],
            ['title' => 'Persistent progression', 'desc' => 'Stats, inventory, leaderboards, and mission tracking are wired into the app.'],
            ['title' => 'Built for the web', 'desc' => 'The stack is Laravel, Blade, Vite, and Reverb for live game events.'],
        ] as $i => $card)
            <div class="action-card scroll-reveal scroll-reveal-delay-{{ $i + 1 }}" data-tilt data-tilt-intensity="6">
                <h2 class="mb-2 text-xl font-bold text-white">{{ $card['title'] }}</h2>
                <p class="text-slate-400">{{ $card['desc'] }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection