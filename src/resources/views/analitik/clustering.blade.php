@extends('layouts.app')

@section('title', 'Clustering & Pola Spasial')

@section('content')
{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-2">Clustering & Pola Spasial</h1>
    <p class="text-pandora-muted text-sm">Analisis pola lokasi absensi menggunakan machine learning (DBSCAN + Isolation Forest) untuk menemukan lokasi mencurigakan, pegawai terisolasi, dan absensi di luar wilayah.</p>
</div>

{{-- Filter --}}
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Dari</label>
            <input type="date" name="dari" value="{{ $tanggalAwal }}"
                   class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Sampai</label>
            <input type="date" name="sampai" value="{{ $tanggalAkhir }}"
                   class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Tampilkan</button>
    </form>
</div>

{{-- Narasi ringkasan --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 p-5 mb-5">
    <h3 class="text-xs uppercase tracking-wider text-pandora-accent mb-3 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Ringkasan Temuan
    </h3>
    <div class="text-pandora-text text-sm leading-relaxed space-y-1">
        @foreach($narasi as $n)
            <p>{!! \Illuminate\Support\Str::inlineMarkdown($n) !!}</p>
        @endforeach
    </div>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-danger">{{ $noisePoints->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Titik Terisolasi</p>
        <p class="text-[10px] text-pandora-muted/60">DBSCAN noise</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ $ifOutliers->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Outlier Multivariate</p>
        <p class="text-[10px] text-pandora-muted/60">Isolation Forest</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-gold">{{ $hotspots->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Hotspot Lokasi</p>
        <p class="text-[10px] text-pandora-muted/60">Lokasi berulang</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold {{ $diluarKaltara->count() > 0 ? 'text-pandora-danger' : 'text-pandora-success' }}">{{ $diluarKaltara->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Di Luar Kaltara</p>
        <p class="text-[10px] text-pandora-muted/60">Absen dari luar provinsi</p>
    </div>
</div>

{{-- Alert: absensi di luar Kaltara --}}
@if($diluarKaltara->count() > 0)
<div class="bg-pandora-danger/10 rounded-xl border border-pandora-danger/30 overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-pandora-danger/20">
        <h2 class="text-sm font-semibold text-pandora-danger flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Absensi dari Luar Wilayah Kalimantan Utara
        </h2>
        <p class="text-xs text-pandora-danger/70 mt-1">Pegawai terdaftar di OPD Kaltara tetapi melakukan check-in dari kota lain. Bisa mengindikasikan WFA tidak sah atau pemalsuan lokasi.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-danger/5 text-pandora-danger/80 text-xs uppercase tracking-wider">
                    <th class="px-4 py-2.5 text-left">Pegawai</th>
                    <th class="px-4 py-2.5 text-left">Instansi</th>
                    <th class="px-4 py-2.5 text-center">Tanggal</th>
                    <th class="px-4 py-2.5 text-left">Lokasi Terdeteksi</th>
                    <th class="px-4 py-2.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-pandora-danger/10">
                @foreach($diluarKaltara as $dl)
                    <tr class="hover:bg-pandora-danger/5 transition-colors">
                        <td class="px-4 py-2.5">
                            <p class="text-pandora-text text-sm">{{ $dl->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $dl->nip }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($dl->nama_unit ?? '-', 25) }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-text text-xs">{{ $dl->tanggal }}</td>
                        <td class="px-4 py-2.5">
                            <p class="text-pandora-danger text-sm font-medium">{{ $dl->kota }}</p>
                            <p class="text-pandora-muted/60 text-[10px] font-mono">{{ number_format($dl->lat, 4) }}, {{ number_format($dl->lng, 4) }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <a href="{{ route('analitik.anomali.detail', $dl->id) }}" class="px-2 py-1 rounded text-xs bg-pandora-danger/20 text-pandora-danger hover:bg-pandora-danger/30 transition-colors">Detail</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Peta --}}
<div class="bg-pandora-surface rounded-xl p-5 border border-white/5 mb-5">
    <h2 class="text-sm font-semibold text-pandora-text mb-3">Peta Anomali Spasial</h2>
    <div class="flex flex-wrap items-center gap-4 mb-3">
        <span class="flex items-center gap-1.5 text-xs text-pandora-muted"><span class="w-3 h-3 rounded-full bg-pandora-danger inline-block"></span> Titik terisolasi (DBSCAN)</span>
        <span class="flex items-center gap-1.5 text-xs text-pandora-muted"><span class="w-3 h-3 rounded-full bg-pandora-accent inline-block"></span> Outlier multivariate (IF)</span>
        <span class="flex items-center gap-1.5 text-xs text-pandora-muted"><span class="w-3 h-3 rounded-full bg-pandora-gold inline-block"></span> Hotspot lokasi</span>
    </div>
    <div id="clusterMap" class="h-80 md:h-[500px] rounded-lg overflow-hidden"></div>
</div>

{{-- Grid: Hotspot + Instansi --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    {{-- Hotspot lokasi --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
        <div class="px-5 py-3 border-b border-white/5">
            <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
                <svg class="w-4 h-4 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Hotspot Anomali per Lokasi
            </h2>
            <p class="text-[11px] text-pandora-muted mt-0.5">Lokasi dengan anomali berulang dari beberapa pegawai — kemungkinan ada pola sistemik.</p>
        </div>
        <div class="divide-y divide-white/5">
            @forelse($hotspots as $hs)
                <div class="px-5 py-3 hover:bg-pandora-dark/30 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-pandora-text">{{ $hs->lokasi }}</p>
                            <p class="text-[11px] text-pandora-muted">{{ $hs->jumlah_pegawai }} pegawai berbeda</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-xs font-bold {{ $hs->jumlah_anomali >= 10 ? 'bg-pandora-danger/20 text-pandora-danger' : 'bg-pandora-gold/20 text-pandora-gold' }}">{{ $hs->jumlah_anomali }}x</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-6 text-center text-pandora-muted text-sm">Tidak ada hotspot ditemukan</div>
            @endforelse
        </div>
    </div>

    {{-- Instansi teratas --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
        <div class="px-5 py-3 border-b border-white/5">
            <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
                <svg class="w-4 h-4 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Instansi dengan Anomali ML Terbanyak
            </h2>
            <p class="text-[11px] text-pandora-muted mt-0.5">OPD yang paling sering muncul di hasil clustering dan outlier detection.</p>
        </div>
        <div class="divide-y divide-white/5">
            @forelse($instansiAnomali as $ia)
                <div class="px-5 py-3 hover:bg-pandora-dark/30 transition-colors flex items-center justify-between">
                    <div>
                        <p class="text-sm text-pandora-text">{{ \Illuminate\Support\Str::limit($ia->nama_unit, 35) }}</p>
                        <p class="text-[11px] text-pandora-muted">{{ $ia->pegawai_unik }} pegawai unik</p>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-pandora-accent/20 text-pandora-accent">{{ $ia->total_anomali }}</span>
                </div>
            @empty
                <div class="px-5 py-6 text-center text-pandora-muted text-sm">Tidak ada data</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Detail noise points --}}
@if($noisePoints->count() > 0)
<div x-data="{ show: true }" class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <button @click="show = !show" class="w-full px-5 py-3 flex items-center justify-between border-b border-white/5 hover:bg-pandora-dark/30 transition-colors">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Titik Terisolasi — DBSCAN Noise Points ({{ $noisePoints->count() }})
        </h2>
        <svg class="w-4 h-4 text-pandora-muted transition-transform" :class="show && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="show" x-collapse>
        <p class="px-5 py-2 text-xs text-pandora-muted border-b border-white/5 bg-pandora-dark/20">
            Pegawai yang absen dari lokasi yang tidak mirip dengan siapapun — terisolasi dari semua cluster. Bisa mengindikasikan lokasi tidak umum atau koordinat bermasalah.
        </p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Pegawai</th>
                        <th class="px-4 py-3 text-left">Instansi</th>
                        <th class="px-4 py-3 text-center">Tanggal</th>
                        <th class="px-4 py-3 text-left">Lokasi SIKARA</th>
                        <th class="px-4 py-3 text-center">Confidence</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($noisePoints->take(50) as $np)
                        <tr class="hover:bg-pandora-dark/30 transition-colors">
                            <td class="px-4 py-2.5">
                                <p class="text-pandora-text text-sm">{{ $np->nama }}</p>
                                <p class="text-pandora-muted text-xs font-mono">{{ $np->nip }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($np->nama_unit ?? '-', 25) }}</td>
                            <td class="px-4 py-2.5 text-center text-xs text-pandora-text">{{ $np->tanggal }}</td>
                            <td class="px-4 py-2.5 text-xs text-pandora-muted">{{ \Illuminate\Support\Str::limit($np->nama_lokasi_berangkat ?? '-', 30) }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="text-xs font-medium {{ $np->confidence >= 0.7 ? 'text-pandora-danger' : 'text-pandora-gold' }}">{{ round($np->confidence * 100) }}%</span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <a href="{{ route('analitik.anomali.detail', $np->id) }}" class="px-2 py-1 rounded text-xs bg-pandora-surface-light text-pandora-muted hover:text-pandora-accent hover:bg-pandora-accent/10 transition-colors">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Penjelasan metode --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 p-5">
    <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3">Tentang Metode Analisis</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-pandora-muted leading-relaxed">
        <div>
            <p class="text-pandora-danger font-semibold mb-1">DBSCAN (Density-Based Spatial Clustering)</p>
            <p>Mengelompokkan titik-titik check-in berdasarkan kepadatan spasial. Titik yang tidak masuk ke cluster manapun ditandai sebagai <b class="text-pandora-text">noise point</b> — artinya pegawai ini absen dari lokasi yang tidak mirip dengan siapapun. Bisa normal (tugas lapangan) atau mencurigakan (GPS palsu di lokasi acak).</p>
        </div>
        <div>
            <p class="text-pandora-accent font-semibold mb-1">Isolation Forest (Outlier Detection)</p>
            <p>Mendeteksi anomali berdasarkan <b class="text-pandora-text">kombinasi banyak fitur</b> sekaligus (jarak geofence, kecepatan, deviasi waktu). Pegawai yang nilainya secara bersamaan tidak biasa ditandai sebagai outlier. Berbeda dari rule-based: tidak ada aturan tunggal yang dilanggar, tapi kombinasinya mencurigakan.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dbscanData = @json($clusters);
    const ifData = @json($ifOutliers);
    const hotspotData = @json($hotspots);

    const map = L.map('clusterMap', { zoomControl: false }).setView([3.0, 117.4], 9);
    L.control.zoom({ position: 'topright' }).addTo(map);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OSM &copy; CARTO', maxZoom: 18,
    }).addTo(map);

    const bounds = [];

    // DBSCAN noise (merah)
    dbscanData.forEach(function(p) {
        const lat = parseFloat(p.lat), lng = parseFloat(p.lng);
        if (isNaN(lat) || isNaN(lng)) return;
        bounds.push([lat, lng]);
        L.circleMarker([lat, lng], {
            radius: 6, fillColor: '#ff4757', color: '#ff4757', weight: 1, opacity: 0.8, fillOpacity: 0.5,
        }).addTo(map).bindPopup(`<b>${p.nama}</b><br><small>${p.nip}</small><br><small>${p.nama_unit||''}</small><br>Noise point · ${Math.round(p.confidence*100)}%`);
    });

    // IF outliers (biru)
    ifData.forEach(function(p) {
        const lat = parseFloat(p.lat), lng = parseFloat(p.lng);
        if (isNaN(lat) || isNaN(lng)) return;
        bounds.push([lat, lng]);
        L.circleMarker([lat, lng], {
            radius: 5, fillColor: '#00b4d8', color: '#00b4d8', weight: 1, opacity: 0.7, fillOpacity: 0.4,
        }).addTo(map).bindPopup(`<b>${p.nama}</b><br><small>${p.nama_unit||''}</small><br>IF outlier · ${Math.round(p.confidence*100)}%`);
    });

    // Hotspots (kuning, lingkaran besar)
    hotspotData.forEach(function(h) {
        const lat = parseFloat(h.avg_lat), lng = parseFloat(h.avg_lng);
        if (isNaN(lat) || isNaN(lng)) return;
        L.circle([lat, lng], {
            radius: 500, fillColor: '#f0a500', color: '#f0a500', weight: 2, opacity: 0.6, fillOpacity: 0.15,
        }).addTo(map).bindPopup(`<b>Hotspot: ${h.lokasi}</b><br>${h.jumlah_anomali} anomali · ${h.jumlah_pegawai} pegawai`);
    });

    if (bounds.length > 0) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
});
</script>
@endpush
