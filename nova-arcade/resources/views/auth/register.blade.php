@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="page-shell page-section">
    <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
        <aside class="panel-strong overflow-hidden p-8 sm:p-10">
            <div class="eyebrow">Join the arena</div>
            <h1 class="hero-title mt-4 text-4xl leading-tight">Create your account and start tracking progress.</h1>
            <p class="mt-4 max-w-xl text-slate-300">Unlock inventory, record match history, and appear on the leaderboards from the first session.</p>

            <div class="mt-8 grid gap-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                    <div class="text-sm text-slate-400">Custom profile</div>
                    <div class="mt-1 font-semibold text-white">Set a username, avatar, and bio right away.</div>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                    <div class="text-sm text-slate-400">Ready for battle</div>
                    <div class="mt-1 font-semibold text-white">The moment you register, the dashboard is ready.</div>
                </div>
            </div>
        </aside>

        <div class="panel p-8 sm:p-10">
            <div class="mb-8">
                <div class="chip mb-4 w-fit">New account</div>
                <h2 class="text-3xl font-black text-white">Register</h2>
                <p class="mt-2 text-slate-400">Choose your identity for the arena.</p>
            </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 p-4 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Name</label>
                <input name="name" type="text" value="{{ old('name') }}" required class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Username</label>
                <input name="username" type="text" value="{{ old('username') }}" required class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Password</label>
                <input name="password" type="password" required class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Confirm password</label>
                <input name="password_confirmation" type="password" required class="surface-input">
            </div>
            <button class="surface-button w-full">Create account</button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-400">
            Already have an account? <a href="{{ route('login') }}" class="text-white hover:underline">Login</a>
        </div>
    </div>
</div>
    </div>
</div>
@endsection