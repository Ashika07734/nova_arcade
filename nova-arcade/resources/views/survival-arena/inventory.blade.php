@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10 scroll-reveal" data-tilt data-tilt-intensity="2">
        <div class="eyebrow">Inventory</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Cosmetics and unlocks tied to your account.</h1>
    </section>

    @foreach ($inventory as $type => $items)
        <section class="panel p-6 sm:p-8 scroll-reveal">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold text-white">{{ ucwords(str_replace('_', ' ', $type)) }}</h2>
                <span class="chip">{{ count($items) }} items</span>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($items as $i => $item)
                    @php
                        $rarityGlow = match(strtolower($item['rarity'])) {
                            'legendary' => 'rgba(250,204,21,0.2)',
                            'epic' => 'rgba(139,92,246,0.2)',
                            'rare' => 'rgba(34,211,238,0.2)',
                            default => 'rgba(148,163,184,0.1)',
                        };
                        $rarityBorder = match(strtolower($item['rarity'])) {
                            'legendary' => 'rgba(250,204,21,0.4)',
                            'epic' => 'rgba(139,92,246,0.4)',
                            'rare' => 'rgba(34,211,238,0.4)',
                            default => 'rgba(51,65,85,1)',
                        };
                    @endphp
                    <article class="rounded-3xl border bg-slate-950/70 p-4 transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_15px_40px_rgba(0,0,0,0.3)]" style="border-color: {{ $rarityBorder }}; box-shadow: 0 0 20px {{ $rarityGlow }};" data-tilt data-tilt-intensity="6">
                        <div class="flex items-center gap-4">
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-16 w-16 rounded-2xl border border-slate-800 object-cover" style="box-shadow: 0 4px 15px rgba(0,0,0,0.4);">
                            <div>
                                <div class="font-bold text-white">{{ $item['name'] }}</div>
                                <div class="text-sm text-slate-400">{{ ucfirst($item['rarity']) }} | {{ $item['unlocked_at'] }}</div>
                                <div class="mt-1 text-xs uppercase tracking-[0.25em] {{ $item['equipped'] ? 'text-emerald-300' : 'text-slate-500' }}">{{ $item['equipped'] ? 'Equipped' : 'Owned' }}</div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-800 bg-slate-950/70 p-6 text-slate-400">No items unlocked yet.</div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
@endsection
