@extends('layouts.app')

@section('title', 'Create Match')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-16">
    <div class="rounded-3xl border border-gray-800 bg-gray-900/90 p-10">
        <h1 class="text-4xl font-bold mb-4">Create a custom match</h1>
        <form method="POST" action="{{ route('survival-arena.matches.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Room name</label>
                <input name="room_name" type="text" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white">
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Mode</label>
                    <select name="game_mode" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white">
                        <option value="solo">Solo</option>
                        <option value="duo">Duo</option>
                        <option value="squad">Squad</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Players</label>
                    <input name="max_players" type="number" min="2" max="50" value="50" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-300">Public</label>
                    <select name="is_public" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <button class="rounded-lg bg-gradient-to-r from-green-500 to-cyan-500 px-5 py-3 font-bold text-gray-950">Create match</button>
        </form>
    </div>
</div>
@endsection