@extends('layouts.app')

@section('title', 'Rekap Kehadiran Harian')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-2">Rekap Kehadiran Harian</h1>
    <p class="text-pandora-muted">Rekapitulasi kehadiran harian seluruh pegawai per instansi dan unit kerja.</p>
</div>

<span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-pandora-accent/10 text-pandora-accent text-sm font-medium mb-6">
    <span class="w-2 h-2 rounded-full bg-pandora-accent animate-pulse"></span>
    Dalam Pengembangan
</span>

<div class="bg-pandora-surface rounded-xl border border-white/5 p-8 text-center">
    <p class="text-pandora-muted">Modul rekap kehadiran harian sedang dalam tahap pengembangan.</p>
</div>
@endsection
