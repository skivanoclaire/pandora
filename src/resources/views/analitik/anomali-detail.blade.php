@extends('layouts.app')

@section('title', 'Detail Anomali — ' . $anomaly->nama)

@section('content')
@php
    $tingkatLabels = [1 => 'Ketidakmungkinan Fisik', 2 => 'Pelanggaran Aturan', 3 => 'Anomali Statistik', 4 => 'Kandidat False Positive'];
    $tingkatColors = [1 => 'pandora-danger', 2 => 'pandora-gold', 3 => 'pandora-accent', 4 => 'pandora-muted'];
    $color = $tingkatColors[$anomaly->tingkat] ?? 'pandora-accent';
    $conf = round($anomaly->confidence * 100);
@endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="/analitik/anomali" class="hover:text-pandora-accent transition-colors">Deteksi Anomali</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">Detail #{{ $anomaly->id }}</span>
</div>

{{-- Header --}}
<div class="bg-pandora-surface rounded-2xl border border-white/5 p-5 md:p-6 mb-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="inline-flex w-8 h-8 rounded-full bg-{{ $color }}/20 text-{{ $color }} items-center justify-center text-sm font-bold">{{ $anomaly->tingkat }}</span>
                <div>
                    <h1 class="text-xl font-bold text-white">{{ $anomaly->nama }}</h1>
                    <p class="text-pandora-muted text-sm font-mono">{{ $anomaly->nip }} &middot; {{ $anomaly->nama_unit ?? '-' }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-{{ $color }}/15 text-{{ $color }} border border-{{ $color }}/30">
                    Tingkat {{ $anomaly->tingkat }} &mdash; {{ $tingkatLabels[$anomaly->tingkat] ?? '-' }}
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-pandora-surface-light text-pandora-muted">
                    {{ str_replace('_', ' ', $anomaly->jenis_anomali) }}
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-pandora-surface-light text-pandora-muted">
                    {{ \Carbon\Carbon::parse($anomaly->tanggal)->translatedFormat('l, d F Y') }}
                </span>
            </div>
        </div>
        {{-- Confidence gauge --}}
        <div class="text-center flex-shrink-0">
            <div class="relative w-20 h-20">
                <svg class="w-20 h-20 transform -rotate-90" viewBox="0 0 36 36">
                    <path class="stroke-pandora-dark" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="stroke-{{ $conf >= 70 ? 'pandora-danger' : ($conf >= 50 ? 'pandora-gold' : 'pandora-accent') }}" stroke-width="3" stroke-linecap="round" fill="none"
                          stroke-dasharray="{{ $conf }}, 100"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-lg font-bold text-{{ $conf >= 70 ? 'pandora-danger' : ($conf >= 50 ? 'pandora-gold' : 'pandora-accent') }}">{{ $conf }}%</span>
                </div>
            </div>
            <p class="text-[10px] text-pandora-muted mt-1">Confidence</p>
        </div>
    </div>
</div>

{{-- Narasi utama --}}
<div class="space-y-4 mb-5">
    {{-- Ringkasan --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Ringkasan
        </h3>
        <p class="text-pandora-text text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['ringkasan']) !!}</p>
    </div>

    {{-- Alert lokasi di luar Kaltara --}}
    @if(isset($narasi['lokasi_alert']))
    <div class="bg-pandora-danger/10 rounded-xl border border-pandora-danger/30 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-danger mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Absensi di Luar Wilayah Kalimantan Utara
        </h3>
        <p class="text-pandora-text text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['lokasi_alert']) !!}</p>
    </div>
    @endif

    {{-- Implikasi Confidence --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Apa Arti Confidence {{ $conf }}%?
        </h3>
        <p class="text-pandora-text text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['confidence']) !!}</p>
    </div>

    {{-- Penjelasan jenis anomali --}}
    <div class="bg-pandora-surface rounded-xl border border-{{ $color }}/10 p-5">
        <h3 class="text-xs uppercase tracking-wider text-{{ $color }} mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Mengapa Terdeteksi?
        </h3>
        <p class="text-pandora-text text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['jenis']) !!}</p>
    </div>

    {{-- Konteks kehadiran --}}
    @if(isset($narasi['kehadiran']))
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Data Kehadiran Hari Itu
        </h3>
        <p class="text-pandora-text text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['kehadiran']) !!}</p>
    </div>
    @endif

    {{-- Status ijin/cuti/DL dari SIKARA --}}
    @if(isset($narasi['ijin']))
    <div class="bg-pandora-accent/5 rounded-xl border border-pandora-accent/20 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-accent mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Status Ijin/Cuti/DL dari SIKARA
        </h3>
        <p class="text-pandora-text text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['ijin']) !!}</p>
    </div>
    @endif
</div>

{{-- Grid detail --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Peta lokasi --}}
    @if($rekap && $rekap->lat_berangkat)
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3">Lokasi Check-in / Check-out</h3>
        <div id="map" class="h-56 rounded-lg overflow-hidden"></div>

        {{-- Detail lokasi check-in --}}
        <div class="mt-3 space-y-2">
            <div class="flex items-start gap-3 p-2.5 rounded-lg bg-pandora-dark/50">
                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-pandora-danger/20 flex items-center justify-center mt-0.5">
                    <svg class="w-3 h-3 text-pandora-danger" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs text-pandora-muted">Check-in</p>
                    <p class="text-sm text-pandora-text">{{ $rekap->nama_lokasi_berangkat ?? 'Nama lokasi SIKARA tidak tersedia' }}</p>
                    @if($geoBerangkat && $geoBerangkat['display'])
                        <p class="text-xs mt-0.5 {{ $lokasiAlert === 'berangkat' || $lokasiAlert === 'keduanya' ? 'text-pandora-danger font-medium' : 'text-pandora-accent' }}">
                            <svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $geoBerangkat['display'] }}{{ $geoBerangkat['negara'] && $geoBerangkat['negara'] !== 'Indonesia' ? ', ' . $geoBerangkat['negara'] : '' }}
                        </p>
                    @endif
                    <p class="text-[10px] text-pandora-muted/60 font-mono mt-0.5">{{ $rekap->lat_berangkat }}, {{ $rekap->long_berangkat }}</p>
                </div>
            </div>

            @if($rekap->lat_pulang)
            <div class="flex items-start gap-3 p-2.5 rounded-lg bg-pandora-dark/50">
                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-pandora-accent/20 flex items-center justify-center mt-0.5">
                    <svg class="w-3 h-3 text-pandora-accent" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs text-pandora-muted">Check-out</p>
                    <p class="text-sm text-pandora-text">{{ $rekap->nama_lokasi_pulang ?? 'Nama lokasi SIKARA tidak tersedia' }}</p>
                    @if($geoPulang && $geoPulang['display'])
                        <p class="text-xs mt-0.5 {{ $lokasiAlert === 'pulang' || $lokasiAlert === 'keduanya' ? 'text-pandora-danger font-medium' : 'text-pandora-accent' }}">
                            <svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $geoPulang['display'] }}{{ $geoPulang['negara'] && $geoPulang['negara'] !== 'Indonesia' ? ', ' . $geoPulang['negara'] : '' }}
                        </p>
                    @endif
                    <p class="text-[10px] text-pandora-muted/60 font-mono mt-0.5">{{ $rekap->lat_pulang }}, {{ $rekap->long_pulang }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Geofence terdekat --}}
    @if($geofenceInfo && $geofenceInfo->count() > 0)
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3">Geofence Terdekat</h3>
        <div class="space-y-2">
            @foreach($geofenceInfo as $gz)
                @php $jarak = round($gz->jarak_meter); @endphp
                <div class="flex items-center justify-between p-3 rounded-lg bg-pandora-dark/50">
                    <div>
                        <p class="text-sm text-pandora-text">{{ $gz->nama_zona }}</p>
                        <p class="text-[11px] text-pandora-muted">Radius: {{ $gz->radius_meter }}m</p>
                    </div>
                    <span class="text-sm font-mono font-medium {{ $jarak <= $gz->radius_meter ? 'text-pandora-success' : 'text-pandora-danger' }}">
                        {{ number_format($jarak) }}m
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Pola kehadiran 7 hari --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5 lg:col-span-2">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3">Kehadiran & Lokasi 14 Hari Terakhir</h3>
        <div class="space-y-2">
            @forelse($polaKehadiran as $pk)
                @php
                    $isToday = $pk->tanggal === $anomaly->tanggal;
                    $jamMasukJadwal = '07:30:00';
                    $hasJamMasuk = $pk->jam_masuk !== null;
                    $statusLabel = '-';
                    $statusColor = 'pandora-muted';
                    if ($pk->tw == 1 || ($hasJamMasuk && $pk->jam_masuk <= $jamMasukJadwal)) { $statusLabel = 'TW'; $statusColor = 'pandora-success'; }
                    elseif ($pk->mkttw == 1 || ($hasJamMasuk && $pk->jam_masuk > $jamMasukJadwal)) { $statusLabel = 'Terlambat'; $statusColor = 'pandora-gold'; }
                    elseif ($pk->tk == 1 || !$hasJamMasuk) { $statusLabel = 'TK'; $statusColor = 'pandora-danger'; }
                    if ($pk->dl == 1) { $statusLabel = 'DL'; $statusColor = 'pandora-accent'; }
                    if ($pk->i == 1) { $statusLabel = 'Izin'; $statusColor = 'pandora-muted'; }
                @endphp
                <div class="rounded-lg {{ $isToday ? 'bg-'.$color.'/10 border border-'.$color.'/20' : 'bg-pandora-dark/30' }} {{ $pk->diluar_kaltara ? 'ring-1 ring-pandora-danger/50' : '' }}">
                    {{-- Baris utama --}}
                    <div class="flex items-center gap-3 px-3 py-2">
                        <span class="text-xs text-pandora-muted w-20 flex-shrink-0">{{ \Carbon\Carbon::parse($pk->tanggal)->format('d M (D)') }}</span>
                        <span class="text-xs font-mono text-pandora-text">{{ $hasJamMasuk ? \Carbon\Carbon::parse($pk->jam_masuk)->format('H:i') : '--:--' }}</span>
                        <span class="text-pandora-muted text-xs">&rarr;</span>
                        <span class="text-xs font-mono text-pandora-text">{{ $pk->jam_pulang ? \Carbon\Carbon::parse($pk->jam_pulang)->format('H:i') : '--:--' }}</span>
                        <span class="ml-auto px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $statusColor }}/20 text-{{ $statusColor }}">{{ $statusLabel }}</span>
                    </div>
                    {{-- Baris lokasi --}}
                    @if($pk->lat_berangkat)
                    <div class="px-3 pb-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="text-[10px] text-pandora-muted/60 font-mono">{{ number_format($pk->lat_berangkat, 5) }}, {{ number_format($pk->long_berangkat, 5) }}</span>
                        @if($pk->nama_lokasi_berangkat)
                            <span class="text-[10px] text-pandora-muted">{{ $pk->nama_lokasi_berangkat }}</span>
                        @endif
                        @if($pk->geo_berangkat && $pk->geo_berangkat['display'])
                            <span class="text-[10px] font-medium {{ $pk->diluar_kaltara ? 'text-pandora-danger' : 'text-pandora-accent' }}">
                                <svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $pk->geo_berangkat['display'] }}
                            </span>
                        @endif
                        @if($pk->diluar_kaltara)
                            <span class="text-[10px] font-semibold text-pandora-danger bg-pandora-danger/10 px-1.5 py-0.5 rounded">DI LUAR KALTARA</span>
                        @endif
                    </div>
                    @endif
                </div>
            @empty
                <p class="text-pandora-muted text-xs">Tidak ada data</p>
            @endforelse
        </div>
    </div>

    {{-- Riwayat anomali --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3">Riwayat Anomali (30 Hari)</h3>
        <p class="text-pandora-text text-sm mb-3">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['pola']) !!}</p>
        @if($riwayat->count() > 0)
            <div class="space-y-1.5">
                @foreach($riwayat as $rw)
                    <div class="flex items-center gap-3 px-3 py-2 rounded-lg {{ $rw->id == $anomaly->id ? 'bg-'.$color.'/10' : 'bg-pandora-dark/30' }}">
                        <span class="text-xs text-pandora-muted">{{ $rw->tanggal }}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-pandora-surface-light text-pandora-muted">{{ str_replace('_', ' ', $rw->jenis_anomali) }}</span>
                        <span class="ml-auto text-xs font-medium {{ $rw->confidence >= 0.7 ? 'text-pandora-danger' : 'text-pandora-muted' }}">{{ round($rw->confidence * 100) }}%</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Rekomendasi --}}
<div class="bg-gradient-to-r from-{{ $color }}/10 to-transparent rounded-xl border border-{{ $color }}/20 p-5 mb-5">
    <h3 class="text-xs uppercase tracking-wider text-{{ $color }} mb-2 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        Rekomendasi Tindak Lanjut
    </h3>
    <p class="text-pandora-text text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi['rekomendasi']) !!}</p>
</div>

{{-- Metadata teknis (collapsible) --}}
<div x-data="{ showTech: false }" class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <button @click="showTech = !showTech" class="w-full flex items-center justify-between px-5 py-3 text-xs text-pandora-muted hover:text-pandora-text transition-colors">
        <span class="uppercase tracking-wider">Data Teknis</span>
        <svg class="w-4 h-4 transition-transform" :class="showTech && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="showTech" x-collapse class="px-5 pb-4">
        <pre class="text-[11px] text-pandora-muted bg-pandora-dark rounded-lg p-3 overflow-x-auto">{{ json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @if($features)
            <p class="text-[11px] text-pandora-muted mt-3 mb-1">Feature Engineering:</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[11px]">
                @foreach(['velocity_berangkat_pulang','velocity_vs_kemarin','jarak_dari_geofence_berangkat','jarak_dari_geofence_pulang','deviasi_masuk_vs_jadwal_ekspektasi','deviasi_pulang_vs_jadwal_ekspektasi','deviasi_waktu_masuk_vs_median_personal','deviasi_waktu_masuk_vs_median_unit'] as $fk)
                    @if($features->$fk !== null)
                        <div class="flex justify-between py-0.5 border-b border-white/5">
                            <span class="text-pandora-muted">{{ str_replace('_', ' ', $fk) }}</span>
                            <span class="text-pandora-text font-mono">{{ round($features->$fk, 2) }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Aksi --}}
<div class="flex items-center gap-3">
    <a href="/analitik/anomali" class="px-4 py-2 rounded-lg text-sm text-pandora-muted hover:text-pandora-text bg-pandora-surface border border-white/5 hover:border-white/10 transition-colors">
        &larr; Kembali
    </a>
    @if($anomaly->status_review === 'belum_direview')
        <form method="POST" action="{{ route('analitik.anomali.review', $anomaly->id) }}" class="flex gap-2">
            @csrf
            @method('PATCH')
            <button type="submit" name="status_review" value="valid"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-pandora-danger/20 text-pandora-danger hover:bg-pandora-danger/30 transition-colors">
                Tandai Valid
            </button>
            <button type="submit" name="status_review" value="false_positive"
                    class="px-4 py-2 rounded-lg text-sm font-medium bg-pandora-success/20 text-pandora-success hover:bg-pandora-success/30 transition-colors">
                False Positive
            </button>
        </form>
    @else
        <span class="px-4 py-2 rounded-lg text-sm {{ $anomaly->status_review === 'valid' ? 'bg-pandora-danger/10 text-pandora-danger' : 'bg-pandora-success/10 text-pandora-success' }}">
            Direview: {{ $anomaly->status_review }}
        </span>
    @endif
</div>

{{-- Leaflet Map --}}
@if($rekap && $rekap->lat_berangkat)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lat = {{ $rekap->lat_berangkat }};
    const lng = {{ $rekap->long_berangkat }};
    const map = L.map('map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OSM', maxZoom: 19
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup('<b>Check-in</b><br>{{ $rekap->nama_lokasi_berangkat ?? "Lokasi" }}');

    @if($geofenceInfo)
        @foreach($geofenceInfo as $gz)
            L.circle([{{ $rekap->lat_berangkat }}, {{ $rekap->long_berangkat }}], {
                radius: {{ $gz->radius_meter }}, color: '{{ $loop->first ? "#ff4757" : "#8899aa" }}',
                fillOpacity: 0.08, weight: 1
            }).addTo(map).bindPopup('{{ $gz->nama_zona }} ({{ $gz->radius_meter }}m)');
        @endforeach
    @endif

    @if($rekap->lat_pulang)
        L.marker([{{ $rekap->lat_pulang }}, {{ $rekap->long_pulang }}], {
            icon: L.divIcon({className: '', html: '<div style="background:#00b4d8;width:12px;height:12px;border-radius:50%;border:2px solid white;"></div>'})
        }).addTo(map).bindPopup('<b>Check-out</b><br>{{ $rekap->nama_lokasi_pulang ?? "Lokasi" }}');
    @endif
});
</script>
@endif
@endsection
