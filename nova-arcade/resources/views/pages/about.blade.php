@extends('layouts.app')

@section('title', 'About')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-16 space-y-8">
    <div class="rounded-3xl border border-gray-800 bg-gray-900/90 p-10">
        <p class="mb-3 text-sm uppercase tracking-[0.35em] text-cyan-400">About the game</p>
        <h1 class="text-4xl font-bold mb-4">A browser battle royale built for fast sessions.</h1>
        <p class="text-lg text-gray-300">Survival Arena 3D combines instant matchmaking, live stats, loot progression, and a lightweight Three.js game loop so players can jump in and compete without a launcher.</p>
    </div>
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6"><h2 class="mb-2 text-xl font-bold">Real-time play</h2><p class="text-gray-400">Responsive combat, zone pressure, and multiplayer state updates.</p></div>
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6"><h2 class="mb-2 text-xl font-bold">Persistent progression</h2><p class="text-gray-400">Stats, inventory, leaderboards, and mission tracking are wired into the app.</p></div>
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6"><h2 class="mb-2 text-xl font-bold">Built for the web</h2><p class="text-gray-400">The stack is Laravel, Blade, Vite, and Reverb for live game events.</p></div>
    </div>
</div>
@endsection