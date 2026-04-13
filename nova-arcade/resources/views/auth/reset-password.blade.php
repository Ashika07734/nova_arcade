@extends('layouts.app')

@section('title', 'Set New Password')

@section('content')
<div class="page-shell page-section">
    <div class="max-w-md mx-auto panel p-8 sm:p-10">
        <div class="eyebrow">Secure your account</div>
        <h1 class="mt-4 text-3xl font-black text-white">Choose a new password</h1>
        <p class="mt-2 text-slate-400">Use a strong password you have not used before.</p>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 p-4 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Email</label>
                <input name="email" type="email" value="{{ old('email', $email) }}" required class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">New password</label>
                <input name="password" type="password" required class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Confirm password</label>
                <input name="password_confirmation" type="password" required class="surface-input">
            </div>
            <button class="surface-button w-full">Update password</button>
        </form>
    </div>
</div>
@endsection