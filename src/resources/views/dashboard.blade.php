@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Welcome Card -->
<div class="bg-gradient-to-r from-pandora-primary to-pandora-primary-light rounded-2xl p-6 md:p-8 mb-6 border border-white/5">
    <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Selamat Datang, {{ Auth::user()->name }}</h1>
    <p class="text-pandora-muted">Portal Analitik Data Kehadiran ASN — Pemerintah Provinsi Kalimantan Utara</p>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-pandora-accent/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <span class="text-pandora-muted text-sm">Total Pegawai Aktif</span>
        </div>
        <p class="text-3xl font-bold text-white">&mdash;</p>
    </div>
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-pandora-success/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-pandora-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-pandora-muted text-sm">Hadir Hari Ini</span>
        </div>
        <p class="text-3xl font-bold text-white">&mdash;</p>
    </div>
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-pandora-gold/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-pandora-muted text-sm">Terlambat Hari Ini</span>
        </div>
        <p class="text-3xl font-bold text-white">&mdash;</p>
    </div>
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-pandora-danger/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <span class="text-pandora-muted text-sm">Tanpa Keterangan</span>
        </div>
        <p class="text-3xl font-bold text-white">&mdash;</p>
    </div>
</div>

<!-- Development Notice -->
<div class="bg-pandora-surface rounded-xl p-6 border border-white/5">
    <div class="flex items-center gap-3 mb-4">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-pandora-accent/10 text-pandora-accent text-sm font-medium">
            <span class="w-2 h-2 rounded-full bg-pandora-accent animate-pulse"></span>
            Dalam Pengembangan
        </span>
    </div>
    <p class="text-pandora-muted">Grafik kehadiran dan analitik akan segera tersedia di sini.</p>
</div>
@endsection
