@extends('layouts.app')

@section('title', 'Dashboard Pimpinan')

@section('content')
{{-- Skor Kesehatan + Narasi --}}
<div class="bg-gradient-to-r from-pandora-primary to-pandora-primary-light rounded-2xl p-5 md:p-7 mb-5 border border-white/5">
    <div class="flex flex-col md:flex-row items-center gap-5 md:gap-8">
        {{-- SVG Gauge --}}
        <div class="flex-shrink-0 relative w-32 h-32 md:w-36 md:h-36">
            @php
                $radius = 54;
                $circumference = 2 * M_PI * $radius;
                $offset = $circumference - ($skorKesehatan / 100) * $circumference;
                $color = $skorKesehatan > 80 ? '#00c48c' : ($skorKesehatan >= 60 ? '#f0a500' : '#ff4757');
            @endphp
            <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8"/>
                <circle cx="60" cy="60" r="{{ $radius }}" fill="none" stroke="{{ $color }}" stroke-width="8"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $circumference }}"
                        stroke-dashoffset="{{ $offset }}"
                        class="transition-all duration-1000 ease-out"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center rotate-0">
                <span class="text-3xl md:text-4xl font-bold" style="color: {{ $color }}">{{ $skorKesehatan }}</span>
                <span class="text-[10px] text-pandora-muted uppercase tracking-wider mt-0.5">Skor Disiplin</span>
            </div>
        </div>

        {{-- Narasi --}}
        <div class="flex-1 text-center md:text-left">
            <h1 class="text-lg md:text-xl font-bold text-white mb-2">Ringkasan Eksekutif</h1>
            <p class="text-pandora-muted text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi) !!}</p>
            @if($lastSync)
                <p class="text-pandora-muted/50 text-xs mt-3">Terakhir sync: {{ \Carbon\Carbon::parse($lastSync->finished_at)->diffForHumans() }}</p>
            @endif
        </div>
    </div>
</div>

@if($isLibur)
{{-- Banner hari libur --}}
<div class="bg-pandora-gold/10 border border-pandora-gold/20 rounded-xl p-4 mb-5 flex items-start gap-3">
    <div class="w-10 h-10 rounded-lg bg-pandora-gold/20 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    </div>
    <div>
        <p class="text-pandora-gold font-semibold text-sm">{{ $liburKeterangan }}</p>
        <p class="text-pandora-muted text-xs mt-0.5">Data di bawah dari hari kerja terakhir ({{ \Carbon\Carbon::parse($tanggalStat)->translatedFormat('l, d F Y') }}).</p>
    </div>
</div>
@endif

{{-- Quick Stats Row --}}
<div class="grid grid-cols-3 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-xl p-4 border border-white/5 text-center">
        <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($hadirHariIni) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Hadir dari {{ number_format($totalPegawai) }}</p>
    </div>
    <div class="bg-pandora-surface rounded-xl p-4 border border-white/5 text-center">
        <p class="text-2xl md:text-3xl font-bold {{ $deltaTren >= 0 ? 'text-pandora-success' : 'text-pandora-danger' }}">
            {{ $deltaTren >= 0 ? '+' : '' }}{{ $deltaTren }}%
        </p>
        <p class="text-xs text-pandora-muted mt-1">vs Minggu Lalu</p>
    </div>
    <div class="bg-pandora-surface rounded-xl p-4 border border-white/5 text-center">
        <p class="text-2xl md:text-3xl font-bold {{ $totalAlertT1 > 0 ? 'text-pandora-danger' : 'text-pandora-success' }}">{{ $totalAlertT1 }}</p>
        <p class="text-xs text-pandora-muted mt-1">Alert Kritis</p>
    </div>
</div>

{{-- Top 5 Terburuk & Terbaik --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    {{-- Terburuk --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-2 h-2 rounded-full bg-pandora-danger"></div>
            <h2 class="text-sm font-semibold text-pandora-text">5 OPD Kehadiran Terendah</h2>
        </div>
        <div class="space-y-3">
            @forelse($opdTerburuk as $i => $opd)
                <div class="flex items-center gap-3">
                    <span class="w-5 text-xs font-bold text-pandora-danger/70 text-right">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-pandora-muted truncate max-w-[65%]" title="{{ $opd->nama_unit }}">{{ \Illuminate\Support\Str::limit($opd->nama_unit, 30) }}</span>
                            <span class="text-xs font-semibold text-pandora-danger">{{ $opd->persen }}%</span>
                        </div>
                        <div class="w-full bg-pandora-dark rounded-full h-1.5">
                            <div class="h-1.5 rounded-full bg-pandora-danger/70 transition-all duration-500" style="width: {{ min($opd->persen, 100) }}%"></div>
                        </div>
                        <p class="text-[10px] text-pandora-muted/60 mt-0.5">{{ $opd->tidak_hadir }} tidak hadir dari {{ $opd->total }}</p>
                    </div>
                </div>
            @empty
                <p class="text-pandora-muted text-xs text-center py-4">Belum ada data</p>
            @endforelse
        </div>
    </div>

    {{-- Terbaik --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-2 h-2 rounded-full bg-pandora-success"></div>
            <h2 class="text-sm font-semibold text-pandora-text">5 OPD Kehadiran Terbaik</h2>
        </div>
        <div class="space-y-3">
            @forelse($opdTerbaik as $i => $opd)
                <div class="flex items-center gap-3">
                    <span class="w-5 text-xs font-bold text-pandora-success/70 text-right">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-pandora-muted truncate max-w-[65%]" title="{{ $opd->nama_unit }}">{{ \Illuminate\Support\Str::limit($opd->nama_unit, 30) }}</span>
                            <span class="text-xs font-semibold text-pandora-success">{{ $opd->persen }}%</span>
                        </div>
                        <div class="w-full bg-pandora-dark rounded-full h-1.5">
                            <div class="h-1.5 rounded-full bg-pandora-success/70 transition-all duration-500" style="width: {{ min($opd->persen, 100) }}%"></div>
                        </div>
                        <p class="text-[10px] text-pandora-muted/60 mt-0.5">{{ $opd->hadir }} hadir dari {{ $opd->total }}</p>
                    </div>
                </div>
            @empty
                <p class="text-pandora-muted text-xs text-center py-4">Belum ada data</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Alert Kritis (only show if there are T1 anomalies) --}}
@if($totalAlertT1 > 0)
<div class="bg-pandora-danger/5 border border-pandora-danger/20 rounded-xl p-5 mb-5">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.832c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <h2 class="text-sm font-semibold text-pandora-danger">Alert Kritis (Tingkat 1)</h2>
        </div>
        <span class="text-xs text-pandora-muted">{{ $totalAlertT1 }} total</span>
    </div>

    {{-- Breakdown per jenis --}}
    @if($anomaliPerJenis->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($anomaliPerJenis as $aj)
            <span class="px-2 py-1 rounded-lg bg-pandora-danger/10 text-pandora-danger text-xs font-medium">
                {{ str_replace('_', ' ', $aj->jenis_anomali) }}: {{ $aj->jumlah }}
            </span>
        @endforeach
    </div>
    @endif

    <div class="space-y-2">
        @foreach($alertKritis as $a)
            <div class="flex items-center gap-3 p-3 rounded-lg bg-pandora-dark/50 border border-white/5">
                <span class="inline-flex w-6 h-6 rounded-full bg-pandora-danger/20 text-pandora-danger items-center justify-center text-xs font-bold flex-shrink-0">!</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-pandora-text font-medium truncate">{{ $a->nama }}</p>
                    <p class="text-[10px] text-pandora-muted">{{ $a->jenis_label }} &middot; {{ $a->confidence_pct }}% confidence &middot; {{ $a->tanggal }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- Tren 4 Minggu + Regional --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Tren 4 Minggu --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h2 class="text-sm font-semibold text-pandora-text mb-4">Tren 4 Minggu</h2>
        @if($trenMingguan->count() > 0)
        <div class="space-y-3">
            @foreach($trenMingguan as $tw)
                @php
                    $barColor = $tw->persen >= 80 ? 'bg-pandora-success' : ($tw->persen >= 60 ? 'bg-pandora-gold' : 'bg-pandora-danger');
                    $textColor = $tw->persen >= 80 ? 'text-pandora-success' : ($tw->persen >= 60 ? 'text-pandora-gold' : 'text-pandora-danger');
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs text-pandora-muted">Minggu {{ $tw->label }}</span>
                        <span class="text-xs font-semibold {{ $textColor }}">{{ $tw->persen }}%</span>
                    </div>
                    <div class="w-full bg-pandora-dark rounded-full h-2.5">
                        <div class="h-2.5 rounded-full {{ $barColor }} transition-all duration-700" style="width: {{ min($tw->persen, 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        @else
            <p class="text-pandora-muted text-xs text-center py-4">Belum ada data tren</p>
        @endif
    </div>

    {{-- Perbandingan Regional --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h2 class="text-sm font-semibold text-pandora-text mb-4">Perbandingan Regional</h2>
        @if($regional->count() > 0)
        <div class="space-y-3">
            @foreach($regional as $reg)
                @php
                    $barColor = $reg->persen >= 80 ? 'bg-pandora-success' : ($reg->persen >= 60 ? 'bg-pandora-gold' : 'bg-pandora-danger');
                    $textColor = $reg->persen >= 80 ? 'text-pandora-success' : ($reg->persen >= 60 ? 'text-pandora-gold' : 'text-pandora-danger');
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs text-pandora-muted">{{ $reg->wilayah }}</span>
                        <span class="text-xs font-semibold {{ $textColor }}">{{ $reg->persen }}%</span>
                    </div>
                    <div class="w-full bg-pandora-dark rounded-full h-2.5">
                        <div class="h-2.5 rounded-full {{ $barColor }} transition-all duration-700" style="width: {{ min($reg->persen, 100) }}%"></div>
                    </div>
                    <p class="text-[10px] text-pandora-muted/60 mt-0.5">{{ number_format($reg->hadir) }} / {{ number_format($reg->total) }} ASN</p>
                </div>
            @endforeach
        </div>
        @else
            <p class="text-pandora-muted text-xs text-center py-4">Belum ada data regional</p>
        @endif
    </div>
</div>
@endsection
