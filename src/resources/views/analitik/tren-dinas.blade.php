@extends('layouts.app')

@section('title', 'Dinas Luar/Dalam — ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')
@php $tgl = \Carbon\Carbon::parse($tanggal); @endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="/analitik/tren" class="hover:text-pandora-accent transition-colors">Tren Kehadiran</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('analitik.tren.detail', $tanggal) }}" class="hover:text-pandora-accent transition-colors">{{ $tgl->translatedFormat('d F Y') }}</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">Dinas Luar / Dalam</span>
</div>

{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-1">Dinas Luar & Dinas Dalam</h1>
    <p class="text-pandora-muted text-sm">{{ $tgl->translatedFormat('l, d F Y') }} — Pegawai yang tercatat melaksanakan dinas berdasarkan data ijin SIKARA.</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-3 mb-6">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ $totalDL }}</p>
        <p class="text-xs text-pandora-muted mt-1">Dinas Luar</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-gold">{{ $totalDD }}</p>
        <p class="text-xs text-pandora-muted mt-1">Dinas Dalam</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-text">{{ $totalDL + $totalDD }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total</p>
    </div>
</div>

{{-- Per Instansi --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Ringkasan per Instansi
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">DL</th>
                    <th class="px-4 py-3 text-center">DD</th>
                    <th class="px-4 py-3 text-center">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($perInstansi as $inst)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5 text-pandora-text text-sm">{{ $inst->nama_unit }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-accent">{{ $inst->dl ?: '-' }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-gold">{{ $inst->dd ?: '-' }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-text font-medium">{{ $inst->jumlah }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Daftar Pegawai --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Daftar Pegawai
        </h2>
        <span class="text-xs text-pandora-muted">{{ $dinas->count() }} orang</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Pegawai</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Jenis</th>
                    <th class="px-4 py-3 text-center">Periode</th>
                    <th class="px-4 py-3 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($dinas as $d)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5">
                            <p class="text-pandora-text text-sm">{{ $d->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $d->nip }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($d->nama_unit ?? '-', 30) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $d->kategori === 'Dinas Luar' ? 'bg-pandora-accent/20 text-pandora-accent' : 'bg-pandora-gold/20 text-pandora-gold' }}">
                                {{ $d->kategori === 'Dinas Luar' ? 'DL' : 'DD' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs text-pandora-muted">
                            {{ \Carbon\Carbon::parse($d->tanggal_mulai)->format('d M') }} — {{ \Carbon\Carbon::parse($d->tanggal_selesai)->format('d M') }}
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($d->keterangan ?? '-', 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-pandora-muted">Tidak ada pegawai dinas luar/dalam pada tanggal ini</td></tr>
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
