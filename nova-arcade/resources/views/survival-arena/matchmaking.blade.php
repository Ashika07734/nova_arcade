@extends('layouts.app')

@section('title', 'Matchmaking')

@section('content')
<div class="page-shell page-section space-y-10">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal" data-tilt data-tilt-intensity="2">
        <div class="eyebrow">Matchmaking</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Choose difficulty and enter the Solo Bot Arena.</h1>
        <p class="mt-4 max-w-4xl text-lg leading-8 text-slate-300">Each run spawns AI bots immediately: Easy (3), Medium (5), Hard (8).</p>
    </section>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach ([
            ['difficulty' => 'easy', 'title' => 'Easy', 'desc' => '3 bots, forgiving aim and slower reaction speed.', 'glow' => 'rgba(16,185,129,0.3)'],
            ['difficulty' => 'medium', 'title' => 'Medium', 'desc' => '5 bots, balanced pressure and movement.', 'glow' => 'rgba(34,211,238,0.3)'],
            ['difficulty' => 'hard', 'title' => 'Hard', 'desc' => '8 bots, fast tracking and aggressive cadence.', 'glow' => 'rgba(244,63,94,0.3)'],
        ] as $i => $mode)
            <button type="button" data-difficulty="{{ $mode['difficulty'] }}" class="matchmaking-button action-card text-left scroll-reveal scroll-reveal-delay-{{ $i + 1 }}" data-tilt data-tilt-intensity="8" style="--card-glow: {{ $mode['glow'] }};">
                <div class="eyebrow">{{ $mode['title'] }}</div>
                <div class="mt-4 text-2xl font-bold text-white">{{ $mode['title'] }}</div>
                <p class="mt-2 text-sm leading-7 text-slate-400">{{ $mode['desc'] }}</p>
            </button>
        @endforeach
    </div>

    <section class="panel p-6 sm:p-8 scroll-reveal">
        <h2 class="mb-4 text-2xl font-bold text-white">Join by match code</h2>
        <form method="POST" action="{{ route('survival-arena.matches.store') }}" class="grid gap-4 md:grid-cols-4">
            @csrf
            <input type="hidden" name="game_mode" value="solo">
            <input type="hidden" name="max_players" value="50">
            <input type="hidden" name="is_public" value="1">
            <select name="difficulty" class="surface-select">
                <option value="easy">Easy (3 bots)</option>
                <option value="medium">Medium (5 bots)</option>
                <option value="hard">Hard (8 bots)</option>
            </select>
            <input type="text" name="room_name" placeholder="Room name" class="surface-input md:col-span-2">
            <button class="surface-button">Create room</button>
        </form>
    </section>
</div>

@push('scripts')
<script>
document.querySelectorAll('.matchmaking-button').forEach((button) => {
    button.addEventListener('click', async () => {
        button.style.boxShadow = `0 0 30px ${getComputedStyle(button).getPropertyValue('--card-glow')}, 0 20px 50px rgba(0,0,0,0.4)`;
        const response = await fetch(@json(route('survival-arena.matchmaking.join')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ game_mode: 'solo', difficulty: button.dataset.difficulty || 'easy' }),
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
