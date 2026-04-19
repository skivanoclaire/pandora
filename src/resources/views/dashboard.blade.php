@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Welcome + Narasi -->
<div class="bg-gradient-to-r from-pandora-primary to-pandora-primary-light rounded-2xl p-5 md:p-7 mb-5 border border-white/5">
    <h1 class="text-xl md:text-2xl font-bold text-white mb-1">Selamat Datang, {{ Auth::user()->name }}</h1>
    <p class="text-pandora-muted text-sm leading-relaxed">{!! \Illuminate\Support\Str::inlineMarkdown($narasi) !!}</p>
    @if($lastSync)
        <p class="text-pandora-muted/50 text-xs mt-2">Terakhir sync: {{ \Carbon\Carbon::parse($lastSync->finished_at)->diffForHumans() }}</p>
    @endif
</div>

@if($isLibur)
{{-- Banner hari libur --}}
<div class="bg-pandora-gold/10 border border-pandora-gold/20 rounded-xl p-4 mb-5 flex items-start gap-3">
    <div class="w-10 h-10 rounded-lg bg-pandora-gold/20 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    </div>
    <div>
        <p class="text-pandora-gold font-semibold text-sm">{{ $liburKeterangan }}</p>
        <p class="text-pandora-muted text-xs mt-0.5">Hari ini bukan hari kerja. Statistik di bawah menampilkan data hari kerja terakhir ({{ \Carbon\Carbon::parse($tanggalStat)->translatedFormat('l, d F Y') }}).</p>
    </div>
</div>
@endif

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-5">
    <!-- Total Pegawai -->
    <div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-pandora-accent/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <span class="text-pandora-muted text-xs">Pegawai Aktif</span>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($totalPegawai) }}</p>
    </div>

    <!-- Hadir -->
    <div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-pandora-success/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-pandora-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-pandora-muted text-xs">Hadir Hari Ini</span>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($hadirHariIni) }}</p>
        <p class="text-xs mt-1 {{ $deltaTren >= 0 ? 'text-pandora-success' : 'text-pandora-danger' }}">
            {{ $deltaTren >= 0 ? '+' : '' }}{{ $deltaTren }}% vs kemarin
        </p>
    </div>

    <!-- Terlambat -->
    <div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-pandora-gold/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-pandora-muted text-xs">Terlambat</span>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($terlambatHariIni) }}</p>
    </div>

    <!-- Tanpa Keterangan -->
    <div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-8 h-8 rounded-lg bg-pandora-danger/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <span class="text-pandora-muted text-xs">Tanpa Keterangan</span>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-white">{{ number_format($tanpaKeterangan) }}</p>
    </div>
</div>

<!-- Tren 7 Hari + Ringkasan OPD -->
<div class="grid grid-cols-1 lg:grid-cols-5 gap-4 md:gap-5 mb-5">
    <!-- Tren Chart -->
    <div class="lg:col-span-3 bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-pandora-text">Tren Kehadiran 7 Hari</h2>
            <span class="text-xs text-pandora-muted">{{ $persenKehadiran }}% hari ini</span>
        </div>
        <div class="h-52 md:h-64">
            <canvas id="trenChart"></canvas>
        </div>
    </div>

    <!-- Peta Anomali + Anomali Terbaru (dipindah ke atas OPD) -->
    <div class="lg:col-span-1 bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-pandora-text">Peta Anomali</h2>
            <span class="text-xs text-pandora-muted">{{ $petaAnomali->count() }} titik</span>
        </div>
        <div id="anomalyMap" class="h-64 md:h-80 rounded-lg overflow-hidden"></div>
    </div>

    <div class="lg:col-span-1 bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-pandora-text">Anomali Terbaru</h2>
            @if($totalAnomali > 0)
                <a href="/analitik/anomali" class="text-xs text-pandora-accent hover:underline">Lihat semua ({{ $totalAnomali }})</a>
            @endif
        </div>
        <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1 custom-scrollbar">
            @forelse($anomaliTerbaru as $a)
                <div class="flex items-start gap-3 p-3 rounded-lg bg-pandora-dark/50 border border-white/5">
                    <div class="flex-shrink-0 mt-0.5">
                        @if($a->tingkat === 1)
                            <span class="inline-flex w-6 h-6 rounded-full bg-pandora-danger/20 text-pandora-danger items-center justify-center text-xs font-bold">1</span>
                        @elseif($a->tingkat === 2)
                            <span class="inline-flex w-6 h-6 rounded-full bg-pandora-gold/20 text-pandora-gold items-center justify-center text-xs font-bold">2</span>
                        @else
                            <span class="inline-flex w-6 h-6 rounded-full bg-pandora-accent/20 text-pandora-accent items-center justify-center text-xs font-bold">{{ $a->tingkat }}</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-pandora-text font-medium truncate">{{ $a->nama }}</p>
                        <p class="text-xs text-pandora-muted">{{ $a->nip }} &middot; {{ $a->tanggal }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-primary-light/30 text-pandora-muted">{{ str_replace('_', ' ', $a->jenis_anomali) }}</span>
                            <span class="text-[10px] text-pandora-muted">{{ $a->confidence_pct }}%</span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-pandora-muted text-xs text-center py-8">Tidak ada anomali yang menunggu review</p>
            @endforelse
        </div>
    </div>

    <!-- Ringkasan OPD (hidden untuk pimpinan) -->
    @unless(auth()->user()->isPimpinan())
    <div class="lg:col-span-2 bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-pandora-text">Kehadiran per OPD</h2>
            @if($opdAlert > 0)
                <span class="px-2 py-0.5 rounded-full bg-pandora-danger/10 text-pandora-danger text-xs font-medium">{{ $opdAlert }} alert</span>
            @endif
        </div>
        <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
            @forelse($perOpd as $opd)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs text-pandora-muted truncate max-w-[70%]" title="{{ $opd['nama_unit'] }}">{{ \Illuminate\Support\Str::limit($opd['nama_unit'], 35) }}</span>
                        <span class="text-xs font-medium {{ $opd['persen'] >= 80 ? 'text-pandora-success' : ($opd['persen'] >= 60 ? 'text-pandora-gold' : 'text-pandora-danger') }}">{{ $opd['persen'] }}%</span>
                    </div>
                    <div class="w-full bg-pandora-dark rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all duration-500 {{ $opd['persen'] >= 80 ? 'bg-pandora-success' : ($opd['persen'] >= 60 ? 'bg-pandora-gold' : 'bg-pandora-danger') }}"
                             style="width: {{ min($opd['persen'], 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-pandora-muted text-xs text-center py-4">Belum ada data kehadiran</p>
            @endforelse
        </div>
    </div>
    @endunless
</div>

<!-- Top 10 OPD Terbaik & Terburuk -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-5 mb-5">
    {{-- Terbaik (kiri) --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
        <div class="px-5 py-3 border-b border-white/5">
            <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
                <svg class="w-4 h-4 text-pandora-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Top 10 OPD Kehadiran Terbaik
            </h2>
        </div>
        <div class="divide-y divide-white/5">
            @forelse($opdTerbaik as $i => $opd)
                <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-pandora-dark/30 transition-colors">
                    <span class="w-5 text-xs font-bold text-pandora-success/70 text-right">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-pandora-muted truncate" title="{{ $opd->nama_unit }}">{{ \Illuminate\Support\Str::limit($opd->nama_unit, 35) }}</span>
                            <span class="text-xs font-semibold text-pandora-success ml-2">{{ $opd->persen }}%</span>
                        </div>
                        <div class="w-full bg-pandora-dark rounded-full h-1 mt-1">
                            <div class="h-1 rounded-full bg-pandora-success/60" style="width: {{ min($opd->persen, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-pandora-muted text-xs">Belum ada data</p>
            @endforelse
        </div>
    </div>

    {{-- Terburuk (kanan) --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
        <div class="px-5 py-3 border-b border-white/5">
            <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
                <svg class="w-4 h-4 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                Top 10 OPD Kehadiran Terendah
            </h2>
        </div>
        <div class="divide-y divide-white/5">
            @forelse($opdTerburuk as $i => $opd)
                <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-pandora-dark/30 transition-colors">
                    <span class="w-5 text-xs font-bold text-pandora-danger/70 text-right">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-pandora-muted truncate" title="{{ $opd->nama_unit }}">{{ \Illuminate\Support\Str::limit($opd->nama_unit, 35) }}</span>
                            <span class="text-xs font-semibold text-pandora-danger ml-2">{{ $opd->persen }}%</span>
                        </div>
                        <div class="w-full bg-pandora-dark rounded-full h-1 mt-1">
                            <div class="h-1 rounded-full bg-pandora-danger/60" style="width: {{ min($opd->persen, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-pandora-muted text-xs">Belum ada data</p>
            @endforelse
        </div>
    </div>
</div>


<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
</style>
@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Tren Chart ===
    const trenData = @json($tren7hari);
    const ctx = document.getElementById('trenChart');

    if (ctx && trenData.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trenData.map(d => d.tanggal),
                datasets: [{
                    label: 'Kehadiran (%)',
                    data: trenData.map(d => d.persen),
                    borderColor: '#00b4d8',
                    backgroundColor: 'rgba(0, 180, 216, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#00b4d8',
                    pointBorderColor: '#0a1628',
                    pointBorderWidth: 2,
                }, {
                    label: 'Hadir',
                    data: trenData.map(d => d.hadir),
                    borderColor: '#00c48c',
                    borderWidth: 1.5,
                    borderDash: [4, 4],
                    fill: false,
                    tension: 0.3,
                    pointRadius: 0,
                    yAxisID: 'y1',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111d33',
                        titleColor: '#e0e7ef',
                        bodyColor: '#a0aec0',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.datasetIndex === 0) return `Kehadiran: ${ctx.parsed.y}%`;
                                return `Hadir: ${ctx.parsed.y} orang`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { color: '#64748b', font: { size: 11 } }
                    },
                    y: {
                        position: 'left',
                        min: 0, max: 100,
                        grid: { color: 'rgba(255,255,255,0.04)' },
                        ticks: { color: '#64748b', font: { size: 11 }, callback: v => v + '%' }
                    },
                    y1: {
                        position: 'right',
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 11 } }
                    }
                }
            }
        });
    }

    // === Peta Anomali (Leaflet) ===
    const mapEl = document.getElementById('anomalyMap');
    const anomalyData = @json($petaAnomali);

    if (mapEl) {
        // Center: Kalimantan Utara (Tanjung Selor approx)
        const map = L.map('anomalyMap', {
            zoomControl: false,
        }).setView([3.0, 117.4], 9);

        L.control.zoom({ position: 'topright' }).addTo(map);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 18,
        }).addTo(map);

        if (anomalyData.length > 0) {
            const bounds = [];
            anomalyData.forEach(function(p) {
                const lat = parseFloat(p.lat);
                const lng = parseFloat(p.lng);
                if (isNaN(lat) || isNaN(lng)) return;

                bounds.push([lat, lng]);

                const color = p.tingkat === 1 ? '#ff4757' : (p.tingkat === 2 ? '#f0a500' : '#00b4d8');
                const radius = 5 + (p.confidence * 5);

                L.circleMarker([lat, lng], {
                    radius: radius,
                    fillColor: color,
                    color: color,
                    weight: 1,
                    opacity: 0.8,
                    fillOpacity: 0.5,
                }).addTo(map).bindPopup(
                    `<div style="font-size:12px;line-height:1.6">
                        <strong>T${p.tingkat}</strong> &mdash; ${p.jenis_anomali.replace(/_/g, ' ')}<br>
                        Confidence: ${Math.round(p.confidence * 100)}%<br>
                        Koordinat: ${lat.toFixed(5)}, ${lng.toFixed(5)}
                    </div>`
                );
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
            }
        }
    }
});
</script>
@endpush
