@extends('layouts.app')

@section('title', 'Cari — ' . $q)

@section('content')
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="{{ route('literasi-data.index') }}" class="hover:text-pandora-accent transition-colors">Literasi Data</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">Pencarian</span>
</div>

{{-- Search bar --}}
<div class="mb-6">
    <form action="{{ route('literasi-data.search') }}" method="GET">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-pandora-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="{{ $q }}" autofocus
                   placeholder="Cari materi..."
                   class="w-full bg-pandora-surface border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-pandora-text placeholder-pandora-muted/50 focus:border-pandora-accent focus:outline-none transition-colors">
        </div>
    </form>
</div>

{{-- Results --}}
@if(strlen($q) >= 2)
    <p class="text-pandora-muted text-sm mb-4">{{ count($results) }} hasil ditemukan untuk "<span class="text-pandora-accent">{{ $q }}</span>"</p>

    @if(count($results) > 0)
        <div class="space-y-3">
            @foreach($results as $r)
                <a href="{{ route('literasi-data.show', [$r['category_slug'], $r['slug']]) }}"
                   class="block bg-pandora-surface rounded-xl border border-white/5 p-4 hover:border-{{ $r['color'] }}/30 transition-colors group">
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 px-2 py-0.5 rounded text-[10px] font-medium bg-{{ $r['color'] }}/15 text-{{ $r['color'] }}">{{ $r['category'] }}</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-medium text-pandora-text group-hover:text-{{ $r['color'] }} transition-colors">{{ $r['title'] }}</h3>
                            @if($r['snippet'])
                                <p class="text-xs text-pandora-muted mt-1 line-clamp-2">{{ $r['snippet'] }}</p>
                            @endif
                        </div>
                        <svg class="w-4 h-4 text-pandora-muted/30 group-hover:text-{{ $r['color'] }} flex-shrink-0 mt-0.5 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="bg-pandora-surface rounded-xl border border-white/5 p-8 text-center">
            <p class="text-pandora-muted">Tidak ditemukan materi yang cocok dengan "<span class="text-pandora-accent">{{ $q }}</span>"</p>
        </div>
    @endif
@else
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-8 text-center">
        <p class="text-pandora-muted">Ketik minimal 2 karakter untuk mencari.</p>
    </div>
@endif
@endsection
