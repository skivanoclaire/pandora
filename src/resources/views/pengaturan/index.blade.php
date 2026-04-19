@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-2">Pengaturan Sistem</h1>
    <p class="text-pandora-muted">Konfigurasi sistem, manajemen pengguna, dan pengaturan aplikasi PANDORA.</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-pandora-accent/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ number_format($userCount) }}</p>
                <p class="text-xs text-pandora-muted">Total Pengguna</p>
            </div>
        </div>
    </div>
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-pandora-success/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-pandora-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ number_format($auditCount) }}</p>
                <p class="text-xs text-pandora-muted">Total Audit Trail</p>
            </div>
        </div>
    </div>
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-pandora-gold/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">
                    @if($lastAudit)
                        {{ \Carbon\Carbon::parse($lastAudit->created_at)->diffForHumans() }}
                    @else
                        -
                    @endif
                </p>
                <p class="text-xs text-pandora-muted">Audit Terakhir</p>
            </div>
        </div>
    </div>
</div>

<!-- Menu -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <a href="{{ route('pengaturan.users') }}" class="bg-pandora-surface rounded-xl p-5 border border-white/5 hover:border-pandora-accent/30 transition-colors group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-pandora-accent/10 flex items-center justify-center group-hover:bg-pandora-accent/20 transition-colors">
                <svg class="w-6 h-6 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white group-hover:text-pandora-accent transition-colors">Manajemen Pengguna</h3>
                <p class="text-xs text-pandora-muted mt-1">Kelola akun pengguna, role, dan hak akses sistem.</p>
            </div>
        </div>
    </a>
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-pandora-success/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-pandora-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white">Audit Trail</h3>
                <p class="text-xs text-pandora-muted mt-1">{{ number_format($auditCount) }} entri tercatat. Lihat di halaman <a href="/integritas" class="text-pandora-accent hover:underline">Integritas</a>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
