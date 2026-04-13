@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="page-shell page-section">
    <div class="panel-strong mx-auto max-w-4xl p-8 sm:p-10">
        <div class="eyebrow">Settings</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Account preferences and profile information.</h1>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="mt-8 space-y-5">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Name</label>
                    <input name="name" type="text" value="{{ old('name', $user->name) }}" class="surface-input">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-300">Username</label>
                    <input name="username" type="text" value="{{ old('username', $user->username) }}" class="surface-input">
                </div>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Bio</label>
                <textarea name="bio" rows="5" class="surface-input">{{ old('bio', $user->bio) }}</textarea>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Avatar</label>
                <input name="avatar" type="file" class="surface-input file:border-0 file:bg-transparent file:text-slate-200">
            </div>
            <button class="surface-button">Save settings</button>
        </form>
    </div>
</div>
@endsection

