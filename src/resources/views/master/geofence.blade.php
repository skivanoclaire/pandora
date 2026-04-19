@extends('layouts.app')

@section('title', 'Zona Geofence')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ $totalLokasi }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Lokasi SIKARA</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-success">{{ $totalAktif }}</p>
        <p class="text-xs text-pandora-muted mt-1">Lokasi Aktif</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-gold">{{ $zonaPandora->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Zona PANDORA</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-muted">{{ $zonaPandora->sum('jml_rules') }}</p>
        <p class="text-xs text-pandora-muted mt-1">Aturan Hari/Jam</p>
    </div>
</div>

<style>
    .info-tip { position: relative; }
    .info-tip .tip-text {
        display: none; position: fixed; width: 300px;
        background: #182742; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 10px 14px;
        font-size: 11px; line-height: 1.6; color: #e0e6ed; white-space: normal;
        text-transform: none; letter-spacing: normal; font-weight: 400;
        z-index: 99999; pointer-events: none;
        box-shadow: 0 12px 32px rgba(0,0,0,0.6);
    }
    .info-tip:hover .tip-text { display: block; }
    .unit-tip { position: relative; cursor: help; }
    .unit-tip .unit-list {
        display: none; position: fixed; width: 320px; max-height: 240px; overflow-y: auto;
        background: #182742; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 8px 0;
        font-size: 11px; line-height: 1.5; color: #e0e6ed; white-space: normal;
        z-index: 99999; pointer-events: none;
        box-shadow: 0 12px 32px rgba(0,0,0,0.6);
    }
    .unit-tip:hover .unit-list { display: block; }
</style>

<!-- Tab: Lokasi SIKARA -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-pandora-text">Lokasi Geofence dari SIKARA</h2>
        <span class="text-xs text-pandora-muted">Data dari ref_lokasi_unit</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Nama Lokasi</th>
                    <th class="px-4 py-3 text-center">Latitude</th>
                    <th class="px-4 py-3 text-center">Longitude</th>
                    <th class="px-4 py-3 text-center">Radius (m)</th>
                    <th class="px-4 py-3 text-center">
                        <span class="inline-flex items-center gap-1 info-tip justify-center">
                            Unit
                            <span class="inline-flex w-3.5 h-3.5 rounded-full bg-pandora-accent/20 text-pandora-accent items-center justify-center text-[9px] cursor-help">?</span>
                            <span class="tip-text">
                                <strong class="text-pandora-accent">Jumlah OPD/Instansi Terkait</strong><br>
                                Satu lokasi geofence bisa dipakai oleh beberapa instansi.
                                Contoh: Gedung Gadis dipakai oleh DLH, Dinas Kelautan, BKAD, dll.<br><br>
                                Hover angka pada setiap baris untuk melihat daftar instansi yang terkait.
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-3 text-center">Aktif</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($lokasiSikara as $l)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-text">{{ $l->nama_lokasi ?: '(tanpa nama)' }}</td>
                        <td class="px-4 py-3 text-center text-pandora-muted font-mono text-xs">{{ $l->latitude ? number_format($l->latitude, 6) : '-' }}</td>
                        <td class="px-4 py-3 text-center text-pandora-muted font-mono text-xs">{{ $l->longitude ? number_format($l->longitude, 6) : '-' }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ $l->radius ?: '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $units = $unitPerLokasi[$l->id_lokasi] ?? collect(); @endphp
                            @if($units->count() > 0)
                                <span class="unit-tip">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent cursor-help">{{ $l->jml_unit }} unit</span>
                                    <span class="unit-list">
                                        <span class="block px-3 py-1.5 text-pandora-accent font-semibold border-b border-white/10 text-xs">{{ $l->jml_unit }} Instansi Terkait</span>
                                        @foreach($units as $u)
                                            <span class="block px-3 py-1 hover:bg-white/5 text-[11px]">{{ $u->nama_unit }}</span>
                                        @endforeach
                                    </span>
                                </span>
                            @else
                                <span class="text-pandora-muted text-[10px]">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($l->aktif)
                                <span class="w-2 h-2 rounded-full bg-pandora-success inline-block"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-pandora-danger inline-block"></span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-pandora-muted">Belum ada data lokasi dari SIKARA</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($lokasiSikara->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $lokasiSikara->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Peta Lokasi -->
<div class="bg-pandora-surface rounded-xl p-5 border border-white/5 mb-5">
    <h2 class="text-sm font-semibold text-pandora-text mb-4">Peta Lokasi Geofence</h2>
    <div id="geofenceMap" class="h-80 md:h-[450px] rounded-lg overflow-hidden"></div>
</div>

<!-- Tab: Zona PANDORA -->
@if($zonaPandora->count() > 0)
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="px-5 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text">Zona Geofence PANDORA (Aturan Hari/Jam)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Nama Zona</th>
                    <th class="px-4 py-3 text-center">Radius</th>
                    <th class="px-4 py-3 text-center">Aturan</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($zonaPandora as $z)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-text">{{ $z->nama_zona }}</td>
                        <td class="px-4 py-3 text-center text-pandora-muted">{{ $z->radius_meter ? $z->radius_meter.'m' : '-' }}</td>
                        <td class="px-4 py-3 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-gold/20 text-pandora-gold">{{ $z->jml_rules }} aturan</span></td>
                        <td class="px-4 py-3 text-center">
                            @if($z->aktif) <span class="w-2 h-2 rounded-full bg-pandora-success inline-block"></span>
                            @else <span class="w-2 h-2 rounded-full bg-pandora-danger inline-block"></span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapEl = document.getElementById('geofenceMap');
    if (!mapEl) return;

    const map = L.map('geofenceMap', { zoomControl: false }).setView([3.0, 117.4], 9);
    L.control.zoom({ position: 'topright' }).addTo(map);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OSM &copy; CARTO', maxZoom: 18,
    }).addTo(map);

    const lokasi = @json($lokasiSikara->items());
    const bounds = [];

    lokasi.forEach(function(l) {
        if (!l.latitude || !l.longitude) return;
        const lat = parseFloat(l.latitude), lng = parseFloat(l.longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        bounds.push([lat, lng]);

        // Radius circle
        if (l.radius) {
            L.circle([lat, lng], {
                radius: l.radius, color: '#00b4d8', fillColor: '#00b4d8',
                fillOpacity: 0.1, weight: 1,
            }).addTo(map);
        }

        L.circleMarker([lat, lng], {
            radius: 5, fillColor: l.aktif ? '#00c48c' : '#ff4757',
            color: '#fff', weight: 1, opacity: 0.8, fillOpacity: 0.8,
        }).addTo(map).bindPopup(
            `<div style="font-size:12px;line-height:1.6">
                <strong>${l.nama_lokasi || '(tanpa nama)'}</strong><br>
                Radius: ${l.radius || '-'}m<br>
                Unit terkait: ${l.jml_unit}<br>
                ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>`
        );
    });

    if (bounds.length > 0) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
});

// Tooltip positioning
document.querySelectorAll('.info-tip, .unit-tip').forEach(tip => {
    const text = tip.querySelector('.tip-text, .unit-list');
    if (!text) return;
    tip.addEventListener('mouseenter', () => {
        const rect = tip.getBoundingClientRect();
        const w = text.classList.contains('unit-list') ? 320 : 300;
        text.style.left = Math.min(rect.left, window.innerWidth - w - 16) + 'px';
        text.style.top = (rect.bottom + 8) + 'px';
        if (rect.bottom + 260 > window.innerHeight) {
            text.style.top = Math.max(8, rect.top - text.offsetHeight - 8) + 'px';
        }
    });
});
</script>
@endpush
