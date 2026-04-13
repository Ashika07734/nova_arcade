@extends('layouts.app')

@section('title', 'Browse Matches')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-16 space-y-8">
    <div class="rounded-3xl border border-gray-800 bg-gray-900/90 p-10">
        <h1 class="text-4xl font-bold mb-4">Browse matches</h1>
        <p class="text-gray-300">Join an open match by code or jump into a waiting lobby.</p>
    </div>

    <div class="grid gap-4">
        @forelse ($matches as $match)
            <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-xl font-bold">{{ $match->match_code }}</div>
                    <div class="text-sm text-gray-400">{{ ucfirst($match->game_mode) }} | {{ $match->current_players }}/{{ $match->max_players }} players</div>
                </div>
                <a href="{{ route('survival-arena.matches.join', $match) }}" class="rounded-lg bg-gradient-to-r from-green-500 to-cyan-500 px-5 py-3 font-bold text-gray-950">Join</a>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-800 bg-gray-900 p-6 text-gray-400">No waiting matches right now.</div>
        @endforelse
    </div>
</div>
@endsection