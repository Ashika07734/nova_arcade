@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="page-shell page-section space-y-8">
    <section class="panel-strong p-8 sm:p-10">
        <div class="eyebrow">Inventory</div>
        <h1 class="hero-title mt-4 text-4xl sm:text-5xl">Cosmetics and unlocks tied to your account.</h1>
    </section>

    @foreach ($inventory as $type => $items)
        <section class="panel p-6 sm:p-8">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-2xl font-bold text-white">{{ ucwords(str_replace('_', ' ', $type)) }}</h2>
                <span class="chip">{{ count($items) }} items</span>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($items as $item)
                    <article class="rounded-3xl border border-slate-800 bg-slate-950/70 p-4 transition hover:border-cyan-400/40">
                        <div class="flex items-center gap-4">
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-16 w-16 rounded-2xl border border-slate-800 object-cover">
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

