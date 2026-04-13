@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-16">
    <div class="rounded-3xl border border-gray-800 bg-gray-900/90 p-10">
        <h1 class="text-4xl font-bold mb-4">Edit profile</h1>
        <p class="text-gray-300 mb-8">Update your public identity and avatar.</p>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Name</label>
                <input name="name" type="text" value="{{ old('name', $user->name) }}" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Username</label>
                <input name="username" type="text" value="{{ old('username', $user->username) }}" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Bio</label>
                <textarea name="bio" rows="5" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white focus:border-cyan-500 focus:outline-none">{{ old('bio', $user->bio) }}</textarea>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-300">Avatar</label>
                <input name="avatar" type="file" class="w-full rounded-lg border border-gray-700 bg-gray-950 px-4 py-3 text-white focus:border-cyan-500 focus:outline-none">
            </div>
            <button class="rounded-lg bg-gradient-to-r from-green-500 to-cyan-500 px-5 py-3 font-bold text-gray-950 transition hover:opacity-90">Save changes</button>
        </form>
    </div>
</div>
@endsection