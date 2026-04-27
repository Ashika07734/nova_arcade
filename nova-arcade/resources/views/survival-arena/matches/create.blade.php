@extends('layouts.app')

@section('title', 'Create Match')

@section('content')
<div class="page-shell page-section">
    <div class="panel-strong mx-auto max-w-3xl p-10 scroll-reveal" data-tilt data-tilt-intensity="3">
        <div class="eyebrow">New match</div>
        <h1 class="hero-title mt-4 text-4xl">Create a custom match</h1>
        <form method="POST" action="{{ route('survival-arena.matches.store') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Room name</label>
                <input name="room_name" type="text" class="surface-input">
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Mode</label>
                    <select name="game_mode" class="surface-select">
                        <option value="solo">Solo</option>
                        <option value="duo">Duo</option>
                        <option value="squad">Squad</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Players</label>
                    <input name="max_players" type="number" min="2" max="50" value="50" class="surface-input">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Public</label>
                    <select name="is_public" class="surface-select">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <button class="surface-button">Create match</button>
        </form>
    </div>
</div>
@endsection