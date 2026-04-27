@extends('layouts.app')

@section('title', 'Browse Matches')

@section('content')
<div class="page-shell page-section space-y-8">
    <div class="panel-strong p-10 scroll-reveal">
        <div class="eyebrow">Browse</div>
        <h1 class="hero-title mt-4 text-4xl">Browse matches</h1>
        <p class="mt-3 text-slate-300">Join an open match by code or jump into a waiting lobby.</p>
    </div>

    <div class="grid gap-4">
        @forelse ($matches as $i => $match)
            <div class="panel p-6 flex flex-wrap items-center justify-between gap-4 scroll-reveal scroll-reveal-delay-{{ min($i + 1, 4) }}" style="transition-all duration-300;">
                <div>
                    <div class="text-xl font-bold text-white">{{ $match->match_code }}</div>
                    <div class="text-sm text-slate-400">{{ ucfirst($match->game_mode) }} | {{ $match->current_players }}/{{ $match->max_players }} players</div>
                </div>
                <a href="{{ route('survival-arena.matches.join', $match) }}" class="surface-button">Join</a>
            </div>
        @empty
            <div class="panel p-6 text-slate-400">No waiting matches right now.</div>
        @endforelse
    </div>
</div>
@endsection