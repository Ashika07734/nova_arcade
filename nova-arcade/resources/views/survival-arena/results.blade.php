@extends('layouts.app')

@section('title', 'Results')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal">
        <div class="eyebrow">Match results</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Match {{ $match->match_code }} has ended.</h1>
    </section>

    <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/60 table-3d scroll-reveal scroll-reveal-delay-1">
        <table class="min-w-full divide-y divide-slate-800">
            <thead class="bg-slate-950 text-left text-sm uppercase tracking-[0.2em] text-slate-400">
                <tr>
                    <th class="px-6 py-4">Placement</th>
                    <th class="px-6 py-4">Player</th>
                    <th class="px-6 py-4">Kills</th>
                    <th class="px-6 py-4">Accuracy</th>
                    <th class="px-6 py-4">Score</th>
                    <th class="px-6 py-4">Survival</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach ($players as $player)
                    <tr class="bg-slate-950/40 transition-all duration-300 hover:bg-slate-900/60 {{ $currentPlayer && $currentPlayer->id === $player->id ? 'ring-1 ring-cyan-400/60 animate-glow-pulse' : '' }}" style="{{ $player->placement <= 3 ? 'box-shadow: inset 3px 0 0 ' . ($player->placement === 1 ? 'rgba(250,204,21,0.5)' : ($player->placement === 2 ? 'rgba(148,163,184,0.5)' : 'rgba(180,83,9,0.5)')) . ';' : '' }}">
                        <td class="px-6 py-4 font-bold" style="{{ $player->placement <= 3 ? 'text-shadow: 0 0 15px ' . ($player->placement === 1 ? 'rgba(250,204,21,0.5)' : ($player->placement === 2 ? 'rgba(148,163,184,0.4)' : 'rgba(180,83,9,0.4)')) : '' }}">#{{ $player->placement }}</td>
                        <td class="px-6 py-4 text-white">
                            {{ $player->is_bot ? ($player->bot_name ?? 'BOT') : ($player->user->username ?? 'Unknown') }}
                        </td>
                        <td class="px-6 py-4 text-cyan-300">{{ $player->kills }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ number_format($player->accuracy, 1) }}%</td>
                        <td class="px-6 py-4 text-emerald-300">{{ $player->score }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ $player->formatted_survival_time }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection