@extends('layouts.app')

@section('title', 'Contact')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10">
        <div class="eyebrow">Contact</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Send feedback or report issues with the game.</h1>
    </section>

    <section class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
        <div class="panel p-6 sm:p-8">
            <div class="space-y-4 text-sm text-slate-300">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">Fastest path for bug reports, balance notes, and account issues.</div>
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">Include your username, match code, and a short reproduction step when possible.</div>
                <div class="rounded-2xl border border-cyan-400/20 bg-cyan-500/10 p-4 text-cyan-100">We’ll get back to you as soon as possible.</div>
            </div>
        </div>

        <div class="panel p-6 sm:p-8">
            <form method="POST" action="{{ route('contact.submit') }}" class="space-y-5">
                @csrf
                <div class="grid gap-5 md:grid-cols-2">
                    <input name="name" type="text" placeholder="Name" class="surface-input">
                    <input name="email" type="email" placeholder="Email" class="surface-input">
                </div>
                <input name="subject" type="text" placeholder="Subject" class="surface-input">
                <textarea name="message" rows="6" placeholder="Message" class="surface-input"></textarea>
                <button class="surface-button">Send message</button>
            </form>
        </div>
    </section>
</div>
@endsection