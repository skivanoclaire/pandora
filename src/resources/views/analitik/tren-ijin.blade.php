@extends('layouts.app')

@section('title', $label . ' — ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')
@php $tgl = \Carbon\Carbon::parse($tanggal); @endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="/analitik/tren" class="hover:text-pandora-accent transition-colors">Tren Kehadiran</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('analitik.tren.detail', $tanggal) }}" class="hover:text-pandora-accent transition-colors">{{ $tgl->translatedFormat('d F Y') }}</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">{{ $label }}</span>
</div>

{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-1">{{ $label }}</h1>
    <p class="text-pandora-muted text-sm">{{ $tgl->translatedFormat('l, d F Y') }} — {{ $pegawai->count() }} pegawai tercatat {{ strtolower($label) }} berdasarkan data ijin SIKARA.</p>
</div>

{{-- Per Instansi --}}
@if($perInstansi->count() > 0)
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text">Ringkasan per Instansi</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($perInstansi as $inst)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5 text-pandora-text text-sm">{{ $inst->nama_unit }}</td>
                        <td class="px-4 py-2.5 text-center font-medium text-pandora-accent">{{ $inst->jumlah }}</td>
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
        <span class="text-xs text-pandora-muted">{{ $pegawai->count() }} orang</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Pegawai</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Periode</th>
                    <th class="px-4 py-3 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($pegawai as $p)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5">
                            <p class="text-pandora-text text-sm">{{ $p->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $p->nip }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($p->nama_unit ?? '-', 30) }}</td>
                        <td class="px-4 py-2.5 text-center text-xs text-pandora-muted">
                            {{ \Carbon\Carbon::parse($p->tanggal_mulai)->format('d M') }} — {{ \Carbon\Carbon::parse($p->tanggal_selesai)->format('d M') }}
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($p->keterangan ?? '-', 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-pandora-muted">Tidak ada data</td></tr>
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
