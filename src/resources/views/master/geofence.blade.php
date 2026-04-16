@extends('layouts.app')

@section('title', 'Zona Geofence')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-2">Zona Geofence</h1>
    <p class="text-pandora-muted">Konfigurasi zona geofence untuk validasi lokasi absensi pegawai.</p>
</div>

<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-pandora-accent/10 text-pandora-accent text-sm font-medium mb-6">
    <span class="w-2 h-2 rounded-full bg-pandora-accent animate-pulse"></span>
    Dalam Pengembangan
</span>

<div class="bg-pandora-surface rounded-xl border border-white/5 p-8 text-center">
    <p class="text-pandora-muted">Modul zona geofence sedang dalam tahap pengembangan.</p>
</div>
@endsection
