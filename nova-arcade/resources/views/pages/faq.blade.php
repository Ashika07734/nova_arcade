@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal">
        <div class="eyebrow">FAQ</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Answers to the most common questions about the game.</h1>
    </section>

    <section class="space-y-4">
        @foreach($faqs as $i => $faq)
            <details class="panel p-6 group scroll-reveal scroll-reveal-delay-{{ min($i + 1, 4) }}" style="transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-bold text-white">
                    <span>{{ $faq['question'] }}</span>
                    <span class="text-cyan-300 transition-transform duration-300 group-open:rotate-45 text-xl">+</span>
                </summary>
                <p class="mt-4 max-w-4xl text-slate-400" style="animation: slide-up-3d 0.4s ease forwards;">{{ $faq['answer'] }}</p>
            </details>
        @endforeach
    </section>
</div>
@endsection