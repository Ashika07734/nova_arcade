@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="page-shell page-section">
    <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr]">
        <aside class="panel-strong overflow-hidden p-8 sm:p-10 scroll-reveal" data-tilt data-tilt-intensity="3">
            <div class="eyebrow">Welcome back</div>
            <h1 class="hero-title mt-4 text-4xl leading-tight">Sign in to keep your loadout and stats in sync.</h1>
            <p class="mt-4 max-w-xl text-slate-300">Resume where you left off, track progression, and jump back into the arena in seconds.</p>

            <div class="mt-8 space-y-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30 hover:shadow-[0_10px_25px_rgba(0,0,0,0.3)]" style="box-shadow: var(--inner-depth);">
                    <div class="text-sm text-slate-400">Fast access</div>
                    <div class="mt-1 font-semibold text-white">One account for matches, inventory, and leaderboards.</div>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30 hover:shadow-[0_10px_25px_rgba(0,0,0,0.3)]" style="box-shadow: var(--inner-depth);">
                    <div class="text-sm text-slate-400">Secure profile</div>
                    <div class="mt-1 font-semibold text-white">Your progression is stored server-side and tied to your username.</div>
                </div>
            </div>
        </aside>

        <div class="panel p-8 sm:p-10 scroll-reveal scroll-reveal-delay-1" data-tilt data-tilt-intensity="3">
            <div class="mb-8">
                <div class="chip mb-4 w-fit">Account access</div>
                <h2 class="text-3xl font-black text-white">Login</h2>
                <p class="mt-2 text-slate-400">Enter your credentials to continue.</p>
            </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 p-4 text-sm text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="surface-input">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-300">Password</label>
                <input name="password" type="password" required class="surface-input">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-950 text-cyan-500 focus:ring-cyan-500">
                Remember me
            </label>
            <button class="surface-button w-full">Login</button>
        </form>

        <div class="mt-6 flex items-center justify-between text-sm text-slate-400">
            <a href="{{ route('password.request') }}" class="hover:text-white transition-all hover:translate-x-1">Forgot password?</a>
            <a href="{{ route('register') }}" class="hover:text-white transition-all hover:-translate-x-1">Create account</a>
        </div>
    </div>
</div>
    </div>
</div>
@endsection