@extends('layouts.app')

@section('title', 'Literasi Data')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Literasi Data</h1>
    <p class="text-pandora-muted text-sm md:text-base">Modul pembelajaran data science terstruktur, dikaitkan langsung dengan data nyata PANDORA.</p>
</div>

{{-- Search --}}
<div class="mb-6">
    <form action="{{ route('literasi-data.search') }}" method="GET">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-pandora-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" placeholder="Cari materi... (misal: clustering, regresi, confusion matrix)"
                   class="w-full bg-pandora-surface border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-pandora-text placeholder-pandora-muted/50 focus:border-pandora-accent focus:outline-none transition-colors">
        </div>
    </form>
</div>

{{-- Jalur Belajar --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 p-5 mb-8">
    <h3 class="text-sm font-semibold text-pandora-text mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        Saran Jalur Belajar
    </h3>
    <div class="flex flex-wrap gap-3 text-xs">
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-pandora-accent/10 border border-pandora-accent/20">
            <span class="w-2 h-2 rounded-full bg-pandora-accent"></span>
            <span class="text-pandora-accent font-medium">Pemula:</span>
            <span class="text-pandora-muted">Fondasi &rarr; Data Engineering &rarr; Klasifikasi</span>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-pandora-gold/10 border border-pandora-gold/20">
            <span class="w-2 h-2 rounded-full bg-pandora-gold"></span>
            <span class="text-pandora-gold font-medium">Menengah:</span>
            <span class="text-pandora-muted">+ Estimasi &amp; Regresi &rarr; Clustering</span>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-pandora-danger/10 border border-pandora-danger/20">
            <span class="w-2 h-2 rounded-full bg-pandora-danger"></span>
            <span class="text-pandora-danger font-medium">Lanjut:</span>
            <span class="text-pandora-muted">+ Association Rule &rarr; Data Tak Terstruktur</span>
        </div>
    </div>
</div>

{{-- Grid Kategori --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    @foreach($categories as $i => $cat)
        <a href="{{ route('literasi-data.category', $cat['slug']) }}"
           class="group relative bg-pandora-surface rounded-xl border border-white/5 p-5 transition-all duration-300 hover:border-{{ $cat['color'] }}/30 hover:shadow-lg hover:shadow-{{ $cat['color'] }}/5 hover:-translate-y-0.5"
           x-data="{ shown: false }"
           x-init="setTimeout(() => shown = true, {{ $i * 80 + 100 }})"
           :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
           style="transition: opacity 0.5s ease-out, transform 0.5s ease-out, border-color 0.3s, box-shadow 0.3s;">

            {{-- Subtle glow effect on hover --}}
            <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-{{ $cat['color'] }}/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

            <div class="relative flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-{{ $cat['color'] }}/20 to-{{ $cat['color'] }}/5 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                    <span class="text-lg font-bold text-{{ $cat['color'] }}">{{ str_pad(explode('-', $cat['slug'])[0], 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-pandora-text group-hover:text-{{ $cat['color'] }} transition-colors duration-200">{{ $cat['title'] }}</h3>
                    <p class="text-xs text-pandora-muted mt-1.5 line-clamp-2 leading-relaxed">{{ $cat['description'] }}</p>

                    {{-- Concept count bar --}}
                    <div class="mt-3 flex items-center gap-3">
                        <div class="flex-1 h-1 rounded-full bg-pandora-dark overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-{{ $cat['color'] }}/60 to-{{ $cat['color'] }} transition-all duration-700"
                                 x-data="{ width: 0 }"
                                 x-init="setTimeout(() => width = {{ min($cat['concept_count'] * 10, 100) }}, {{ $i * 80 + 400 }})"
                                 :style="'width: ' + width + '%'">
                            </div>
                        </div>
                        <span class="text-[11px] font-semibold text-{{ $cat['color'] }} tabular-nums">{{ $cat['concept_count'] }} konsep</span>
                    </div>
                </div>

                {{-- Arrow --}}
                <svg class="w-5 h-5 text-pandora-muted/30 group-hover:text-{{ $cat['color'] }} group-hover:translate-x-0.5 transition-all duration-200 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
    @endforeach
</div>
@endsection
