@extends('layouts.app')

@section('title', 'Leaderboards')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal">
        <div class="eyebrow">Leaderboards</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Track the best players by wins, kills, damage, or K/D ratio.</h1>
    </section>

    <form method="GET" class="panel flex flex-wrap gap-3 p-4 sm:p-6 scroll-reveal scroll-reveal-delay-1">
        <select name="period" class="surface-select">
            @foreach (['all_time' => 'All time', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'seasonal' => 'Seasonal'] as $value => $label)
                <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category" class="surface-select">
            @foreach (['wins' => 'Wins', 'kills' => 'Kills', 'kd_ratio' => 'K/D Ratio', 'damage' => 'Damage'] as $value => $label)
                <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="surface-button">Update</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/60 table-3d scroll-reveal scroll-reveal-delay-2">
        <table class="min-w-full divide-y divide-slate-800">
            <thead class="bg-slate-950 text-left text-sm uppercase tracking-[0.2em] text-slate-400">
                <tr>
                    <th class="px-6 py-4">Rank</th>
                    <th class="px-6 py-4">Player</th>
                    <th class="px-6 py-4">Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($topPlayers as $entry)
                    <tr class="bg-slate-950/40 transition-all duration-300 hover:bg-slate-900/60">
                        <td class="px-6 py-4 font-bold">{{ $entry->rank_with_suffix }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img src="{{ $entry->user->avatar_url }}" alt="{{ $entry->user->username }}" class="h-10 w-10 rounded-full border border-slate-700" style="box-shadow: 0 3px 10px rgba(0,0,0,0.3);">
                                <div>
                                    <div class="font-semibold text-white">{{ $entry->user->username }}</div>
                                    <div class="text-sm text-slate-400">{{ $entry->user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-cyan-300">{{ number_format($entry->score, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400">No leaderboard data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
