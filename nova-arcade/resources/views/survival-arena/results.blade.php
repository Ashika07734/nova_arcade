@extends('layouts.app')

@section('title', 'Results')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10">
        <div class="eyebrow">Match results</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Match {{ $match->match_code }} has ended.</h1>
    </section>

    <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/60">
        <table class="min-w-full divide-y divide-slate-800">
            <thead class="bg-slate-950 text-left text-sm uppercase tracking-[0.2em] text-slate-400">
                <tr>
                    <th class="px-6 py-4">Placement</th>
                    <th class="px-6 py-4">Player</th>
                    <th class="px-6 py-4">Kills</th>
                    <th class="px-6 py-4">Survival</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach ($players as $player)
                    <tr class="bg-slate-950/40 {{ $currentPlayer && $currentPlayer->id === $player->id ? 'ring-1 ring-cyan-400/60' : '' }}">
                        <td class="px-6 py-4 font-bold">#{{ $player->placement }}</td>
                        <td class="px-6 py-4 text-white">{{ $player->user->username }}</td>
                        <td class="px-6 py-4 text-cyan-300">{{ $player->kills }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ $player->formatted_survival_time }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection