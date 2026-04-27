@extends('layouts.app')

@section('title', 'Lobby')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal" data-tilt data-tilt-intensity="2">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="eyebrow">Lobby</div>
                <h1 class="hero-title mt-4 text-4xl sm:text-5xl">{{ $match->match_code }}</h1>
                <p class="mt-3 text-slate-400">{{ ucfirst($match->game_mode) }} mode | {{ $match->current_players }}/{{ $match->max_players }} players</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" id="toggle-ready" class="surface-button-secondary">Ready up</button>
                @if ($isHost)
                    <button type="button" id="start-match" class="surface-button">Start match</button>
                @endif
                <button type="button" id="leave-match" class="rounded-full border border-rose-500/50 bg-rose-500/10 px-5 py-3 text-sm font-semibold uppercase tracking-[0.2em] text-rose-200 transition-all hover:bg-rose-500/20 hover:translate-y-[-2px] hover:shadow-[0_8px_25px_rgba(244,63,94,0.2)]">Leave</button>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="panel lg:col-span-2 p-6 scroll-reveal">
            <h2 class="text-2xl font-bold text-white">Players</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($players as $i => $player)
                    <article class="rounded-3xl border border-slate-800 bg-slate-950/70 p-4 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30 hover:shadow-[0_10px_30px_rgba(0,0,0,0.3)]" style="animation: slide-up-3d 0.5s ease {{ $i * 0.08 }}s both;">
                        <div class="flex items-center gap-3">
                            @if (!$player->is_bot && !empty($player->user?->avatar_url))
                                <img src="{{ $player->user->avatar_url }}" class="h-12 w-12 rounded-full border border-slate-700 object-cover" alt="{{ $player->user->username ?? 'Unknown' }}">
                            @else
                                <div class="grid h-12 w-12 place-items-center rounded-full border border-slate-700 bg-slate-900 text-xs font-bold uppercase text-slate-300">
                                    {{ $player->is_bot ? 'BOT' : 'PLY' }}
                                </div>
                            @endif
                            <div>
                                <div class="font-bold text-white">{{ $player->is_bot ? ($player->bot_name ?? 'BOT') : ($player->user->username ?? 'Unknown') }}</div>
                                <div class="text-sm text-slate-400">Joined {{ $player->joined_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
        <aside class="panel p-6 scroll-reveal scroll-reveal-delay-1" style="box-shadow: var(--depth-shadow), 0 0 25px rgba(34,211,238,0.05);">
            <h2 class="mb-4 text-2xl font-bold text-white">Match details</h2>
            <dl class="space-y-4 text-sm text-slate-300">
                <div><dt class="text-slate-500">Match code</dt><dd class="font-semibold text-white">{{ $match->match_code }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="font-semibold text-white">{{ ucfirst($match->status) }}</dd></div>
                <div><dt class="text-slate-500">Host</dt><dd class="font-semibold text-white">{{ $players->first()?->user->username ?? 'Unknown' }}</dd></div>
            </dl>
        </aside>
    </div>
</div>

@push('scripts')
<script>
const matchRoute = @json(route('survival-arena.lobby.ready', $match));
const startRoute = @json(route('survival-arena.lobby.start', $match));
const leaveRoute = @json(route('survival-arena.lobby.leave', $match));
const headers = {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': @json(csrf_token()),
    'Accept': 'application/json',
};

async function postJson(url) {
    const response = await fetch(url, { method: 'POST', headers });
    return await response.json();
}

document.getElementById('toggle-ready')?.addEventListener('click', async () => {
    await postJson(matchRoute);
    window.location.reload();
});

document.getElementById('start-match')?.addEventListener('click', async () => {
    const payload = await postJson(startRoute);
    if (payload.redirect) {
        window.location.href = payload.redirect;
    }
});

document.getElementById('leave-match')?.addEventListener('click', async () => {
    await postJson(leaveRoute);
    window.location.href = @json(route('survival-arena.matchmaking'));
});
</script>
@endpush
@endsection
