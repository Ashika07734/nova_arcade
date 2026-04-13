@extends('layouts.app')

@section('title', 'Matchmaking')

@section('content')
<div class="page-shell page-section space-y-10">
    <section class="panel-strong p-8 sm:p-10">
        <div class="eyebrow">Matchmaking</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Choose a mode and jump into a match.</h1>
        <p class="mt-4 max-w-4xl text-lg leading-8 text-slate-300">The buttons below submit to the existing JSON endpoint and redirect on success.</p>
    </section>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach ([
            ['mode' => 'solo', 'title' => 'Solo', 'desc' => 'Free-for-all survival with no teammates.'],
            ['mode' => 'duo', 'title' => 'Duo', 'desc' => 'Queue with a partner and coordinate rotations.'],
            ['mode' => 'squad', 'title' => 'Squad', 'desc' => 'Form a full team and control the lobby.'],
        ] as $mode)
            <button type="button" data-mode="{{ $mode['mode'] }}" class="matchmaking-button action-card text-left transition hover:-translate-y-1 hover:border-cyan-400/40">
                <div class="eyebrow">{{ $mode['title'] }}</div>
                <div class="mt-4 text-2xl font-bold text-white">{{ $mode['title'] }}</div>
                <p class="mt-2 text-sm leading-7 text-slate-400">{{ $mode['desc'] }}</p>
            </button>
        @endforeach
    </div>

    <section class="panel p-6 sm:p-8">
        <h2 class="mb-4 text-2xl font-bold text-white">Join by match code</h2>
        <form method="POST" action="{{ route('survival-arena.matches.store') }}" class="grid gap-4 md:grid-cols-4">
            @csrf
            <input type="hidden" name="game_mode" value="solo">
            <input type="hidden" name="max_players" value="50">
            <input type="hidden" name="is_public" value="1">
            <input type="text" name="room_name" placeholder="Room name" class="surface-input md:col-span-2">
            <button class="surface-button md:col-span-2">Create room</button>
        </form>
    </section>
</div>

@push('scripts')
<script>
document.querySelectorAll('.matchmaking-button').forEach((button) => {
    button.addEventListener('click', async () => {
        const response = await fetch(@json(route('survival-arena.matchmaking.join')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ game_mode: button.dataset.mode }),
        });

        const payload = await response.json();

        if (payload.redirect) {
            window.location.href = payload.redirect;
            return;
        }

        alert(payload.message || 'Unable to join matchmaking.');
    });
});
</script>
@endpush
@endsection

