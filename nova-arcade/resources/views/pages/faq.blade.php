@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10">
        <div class="eyebrow">FAQ</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Answers to the most common questions about the game.</h1>
    </section>

    <section class="space-y-4">
        @foreach($faqs as $faq)
            <details class="panel p-6 group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-bold text-white">
                    <span>{{ $faq['question'] }}</span>
                    <span class="text-cyan-300 transition group-open:rotate-45">+</span>
                </summary>
                <p class="mt-4 max-w-4xl text-slate-400">{{ $faq['answer'] }}</p>
            </details>
        @endforeach
    </section>
</div>
@endsection