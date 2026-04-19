@extends('layouts.app')

@section('title', 'Tanpa Keterangan — ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')
@php $tgl = \Carbon\Carbon::parse($tanggal); @endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="/analitik/tren" class="hover:text-pandora-accent transition-colors">Tren Kehadiran</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('analitik.tren.detail', $tanggal) }}" class="hover:text-pandora-accent transition-colors">{{ $tgl->translatedFormat('d F Y') }}</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">Tanpa Keterangan</span>
</div>

{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-1">Tanpa Keterangan</h1>
    <p class="text-pandora-muted text-sm">{{ $tgl->translatedFormat('l, d F Y') }} — <span class="text-pandora-danger font-medium">{{ $pegawai->count() }} pegawai</span> tidak hadir dan tidak memiliki ijin/cuti/DL di SIKARA.</p>
</div>

{{-- Per Instansi --}}
@if($perInstansi->count() > 0)
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Instansi dengan Tanpa Keterangan Terbanyak
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-center w-10">#</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($perInstansi as $i => $inst)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5 text-center text-pandora-muted text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-2.5 text-pandora-text text-sm">{{ $inst->nama_unit }}</td>
                        <td class="px-4 py-2.5 text-center font-medium text-pandora-danger">{{ $inst->jumlah }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Daftar Pegawai --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-pandora-text">Daftar Pegawai</h2>
        <span class="text-xs text-pandora-danger bg-pandora-danger/10 px-2 py-0.5 rounded-full font-medium">{{ $pegawai->count() }} orang</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Pegawai</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($pegawai as $p)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5">
                            <p class="text-pandora-text text-sm">{{ $p->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $p->nip }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ $p->nama_unit ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-6 text-center text-pandora-muted">Semua pegawai memiliki keterangan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Back --}}
<div class="mt-6">
    <a href="{{ route('analitik.tren.detail', $tanggal) }}" class="inline-flex items-center gap-1.5 text-xs text-pandora-muted hover:text-pandora-accent transition-colors px-3 py-2 rounded-lg hover:bg-pandora-accent/5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Detail Kehadiran
    </a>
</div>
@endsection
