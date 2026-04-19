@extends('layouts.app')

@section('title', $meta['title'] . ' — Literasi Data')

@section('content')
{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="{{ route('literasi-data.index') }}" class="hover:text-pandora-accent transition-colors flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        Literasi Data
    </a>
    <svg class="w-3 h-3 text-pandora-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">{{ $meta['title'] }}</span>
</div>

{{-- Header --}}
<div class="mb-8">
    <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-{{ $meta['color'] }}/20 to-{{ $meta['color'] }}/5 flex items-center justify-center flex-shrink-0">
            <span class="text-lg font-bold text-{{ $meta['color'] }}">{{ explode('-', $category)[0] }}</span>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $meta['title'] }}</h1>
        </div>
    </div>
    <p class="text-pandora-muted text-sm leading-relaxed ml-[52px]">{{ $description }}</p>
</div>

{{-- Daftar Konsep --}}
<div class="space-y-3">
    @foreach($concepts as $i => $concept)
        <a href="{{ route('literasi-data.show', [$category, $concept['slug']]) }}"
           class="group relative block bg-pandora-surface rounded-xl border border-white/5 transition-all duration-300 hover:border-{{ $meta['color'] }}/30 hover:shadow-lg hover:shadow-{{ $meta['color'] }}/5 hover:-translate-y-0.5 overflow-hidden"
           x-data="{ shown: false }"
           x-init="setTimeout(() => shown = true, {{ $i * 60 + 100 }})"
           :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
           style="transition: opacity 0.4s ease-out, transform 0.4s ease-out, border-color 0.3s, box-shadow 0.3s;">

            {{-- Hover gradient overlay --}}
            <div class="absolute inset-0 bg-gradient-to-r from-{{ $meta['color'] }}/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

            <div class="relative flex items-center gap-4 px-5 py-4">
                {{-- Number badge with gradient --}}
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-{{ $meta['color'] }}/25 to-{{ $meta['color'] }}/10 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300 border border-{{ $meta['color'] }}/20">
                    <span class="text-sm font-bold text-{{ $meta['color'] }}">{{ str_pad($concept['number'], 2, '0', STR_PAD_LEFT) }}</span>
                </div>

                {{-- Title --}}
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-pandora-text group-hover:text-{{ $meta['color'] }} transition-colors duration-200">{{ $concept['title'] }}</span>
                    <span class="block text-[11px] text-pandora-muted/60 mt-0.5">~3 min baca</span>
                </div>

                {{-- Arrow indicator --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    <svg class="w-4 h-4 text-pandora-muted/30 group-hover:text-{{ $meta['color'] }} group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>

            {{-- Bottom accent line --}}
            <div class="h-0.5 bg-gradient-to-r from-{{ $meta['color'] }} to-transparent w-0 group-hover:w-full transition-all duration-500"></div>
        </a>
    @endforeach
</div>

{{-- Back --}}
<div class="mt-8">
    <a href="{{ route('literasi-data.index') }}" class="inline-flex items-center gap-2 text-xs text-pandora-muted hover:text-pandora-accent transition-colors px-3 py-2 rounded-lg hover:bg-pandora-accent/5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke semua kategori
    </a>
</div>
@endsection
