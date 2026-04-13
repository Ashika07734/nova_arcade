@extends('layouts.app')

@section('title', 'Survival Arena')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-16 space-y-10">
    <div class="rounded-[2rem] border border-gray-800 bg-gradient-to-br from-gray-950 via-gray-900 to-gray-900 p-10 shadow-2xl">
        <p class="mb-3 text-sm uppercase tracking-[0.35em] text-cyan-400">Survival Arena 3D</p>
        <h1 class="text-5xl font-black leading-tight md:text-7xl">Drop in, gear up, and outlast the lobby.</h1>
        <p class="mt-6 max-w-3xl text-lg text-gray-300">Fast matchmaking, persistent progression, and a shrinking safe zone combine into a browser battle royale that plays like a real live-service game.</p>
        <div class="mt-8 flex flex-wrap gap-4">
            <a href="{{ route('survival-arena.matchmaking') }}" class="rounded-lg bg-gradient-to-r from-green-500 to-cyan-500 px-6 py-3 font-bold text-gray-950">Play now</a>
            <a href="{{ route('leaderboards') }}" class="rounded-lg border border-gray-700 px-6 py-3 font-bold text-white">Leaderboards</a>
            <a href="{{ route('inventory') }}" class="rounded-lg border border-gray-700 px-6 py-3 font-bold text-white">Inventory</a>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6"><div class="text-sm text-gray-400">Active matches</div><div class="mt-2 text-4xl font-bold text-cyan-400">{{ number_format($activeMatches) }}</div></div>
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6"><div class="text-sm text-gray-400">Players online</div><div class="mt-2 text-4xl font-bold text-green-400">{{ number_format($totalPlayers) }}</div></div>
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6"><div class="text-sm text-gray-400">Game mode</div><div class="mt-2 text-4xl font-bold text-purple-400">Solo / Duo / Squad</div></div>
    </div>
</div>
@endsection

