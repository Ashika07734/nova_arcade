@extends('layouts.app')

@section('title', 'How To Play')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal" data-tilt data-tilt-intensity="2">
        <div class="eyebrow">How to play</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Use movement, map pressure, and smart positioning to survive the zone.</h1>
        <p class="mt-4 max-w-4xl text-lg leading-8 text-slate-300">Use WASD to move, the mouse to aim, and keep rotating as the safe zone shrinks. Loot, fight, and survive until you are the last player standing.</p>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        @php $groupIndex = 0; @endphp
        @foreach($controls as $group => $items)
            <article class="panel p-6 scroll-reveal scroll-reveal-delay-{{ $groupIndex + 1 }}" data-tilt data-tilt-intensity="5">
                <div class="eyebrow">{{ $group }}</div>
                <div class="mt-3 space-y-3">
                    @foreach($items as $item)
                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-950/70 px-4 py-3 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30" style="box-shadow: var(--inner-depth);">
                            <div class="font-semibold text-white">{{ $item['action'] }}</div>
                            <div class="chip text-[10px] tracking-[0.35em]">{{ $item['key'] }}</div>
                        </div>
                    @endforeach
                </div>
            </article>
            @php $groupIndex++; @endphp
        @endforeach
    </section>

    <section class="panel p-6 sm:p-8 scroll-reveal">
        <div class="eyebrow">Gameplay tips</div>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach($gameplayTips as $tip)
                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4 text-sm leading-7 text-slate-300 transition-all duration-300 hover:-translate-y-1 hover:border-cyan-400/30 hover:shadow-[0_10px_25px_rgba(0,0,0,0.3)]" style="box-shadow: var(--inner-depth);">{{ $tip }}</div>
            @endforeach
        </div>
    </section>
</div>
@endsection