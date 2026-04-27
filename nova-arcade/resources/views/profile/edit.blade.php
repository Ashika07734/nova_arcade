@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="page-shell page-section">
    <div class="panel-strong mx-auto max-w-3xl p-10 scroll-reveal" data-tilt data-tilt-intensity="3">
        <div class="eyebrow">Profile</div>
        <h1 class="hero-title mt-4 text-4xl">Edit profile</h1>
        <p class="mt-3 text-slate-300 mb-8">Update your public identity and avatar.</p>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Name</label>
                <input name="name" type="text" value="{{ old('name', $user->name) }}" class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Username</label>
                <input name="username" type="text" value="{{ old('username', $user->username) }}" class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Bio</label>
                <textarea name="bio" rows="5" class="surface-input">{{ old('bio', $user->bio) }}</textarea>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Avatar</label>
                <input name="avatar" type="file" class="surface-input file:border-0 file:bg-transparent file:text-slate-200">
            </div>
            <button class="surface-button">Save changes</button>
        </form>
    </div>
</div>
@endsection