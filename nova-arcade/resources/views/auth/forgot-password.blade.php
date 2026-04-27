@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
<div class="page-shell page-section">
    <div class="max-w-md mx-auto panel p-8 sm:p-10 scroll-reveal" data-tilt data-tilt-intensity="4">
        <div class="eyebrow">Account recovery</div>
        <h1 class="mt-4 text-3xl font-black text-white">Reset password</h1>
        <p class="mt-2 text-slate-400">Send a reset link to your email address.</p>

        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 p-4 text-sm text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 p-4 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="surface-input">
            </div>
            <button class="surface-button w-full">Send reset link</button>
        </form>
    </div>
</div>
@endsection