@extends('layouts.app')

@section('title', 'Pulang Cepat — ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')
@php $tgl = \Carbon\Carbon::parse($tanggal); @endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="/analitik/tren" class="hover:text-pandora-accent transition-colors">Tren Kehadiran</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('analitik.tren.detail', $tanggal) }}" class="hover:text-pandora-accent transition-colors">{{ $tgl->translatedFormat('d F Y') }}</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">Pulang Cepat</span>
</div>

{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-1">Pegawai Pulang Lebih Cepat</h1>
    <p class="text-pandora-muted text-sm">{{ $tgl->translatedFormat('l, d F Y') }} — <span class="text-pandora-gold font-medium">{{ $pegawai->count() }} pegawai</span> pulang sebelum {{ \Carbon\Carbon::parse($batasPulang)->format('H:i') }} WITA.</p>
</div>

{{-- Ranking Instansi --}}
@if($perInstansi->count() > 0)
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Instansi Paling Banyak Pulang Cepat
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-center w-10">#</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Jumlah</th>
                    <th class="px-4 py-3 text-center">Rata-rata</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($perInstansi as $i => $inst)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5 text-center text-pandora-muted text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-2.5 text-pandora-text text-sm">{{ $inst->nama_unit }}</td>
                        <td class="px-4 py-2.5 text-center font-medium text-pandora-gold">{{ $inst->jumlah_pulang_cepat }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-muted text-xs">{{ $inst->rata_menit }} mnt lebih awal</td>
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
        <span class="text-xs text-pandora-gold bg-pandora-gold/10 px-2 py-0.5 rounded-full font-medium">{{ $pegawai->count() }} orang</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Pegawai</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Jam Masuk</th>
                    <th class="px-4 py-3 text-center">Jam Pulang</th>
                    <th class="px-4 py-3 text-center">Lebih Awal</th>
                    <th class="px-4 py-3 text-left">Lokasi Check-out</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($pegawai as $p)
                    @php $menit = round($p->menit_lebih_awal); @endphp
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5">
                            <p class="text-pandora-text text-sm">{{ $p->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $p->nip }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($p->nama_unit ?? '-', 30) }}</td>
                        <td class="px-4 py-2.5 text-center font-mono text-pandora-muted text-sm">{{ $p->jam_masuk ? \Carbon\Carbon::parse($p->jam_masuk)->format('H:i') : '-' }}</td>
                        <td class="px-4 py-2.5 text-center font-mono text-pandora-text text-sm">{{ \Carbon\Carbon::parse($p->jam_pulang)->format('H:i') }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $menit >= 120 ? 'bg-pandora-danger/20 text-pandora-danger' : ($menit >= 60 ? 'bg-pandora-gold/20 text-pandora-gold' : 'bg-pandora-accent/20 text-pandora-accent') }}">
                                {{ $menit >= 60 ? floor($menit/60) . 'j ' . ($menit%60) . 'm' : $menit . ' mnt' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($p->nama_lokasi_pulang ?? '-', 35) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-pandora-muted">Tidak ada yang pulang lebih cepat</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Back --}}
<div class="mt-6">
    <a href="/analitik/tren" class="inline-flex items-center gap-1.5 text-xs text-pandora-muted hover:text-pandora-accent transition-colors px-3 py-2 rounded-lg hover:bg-pandora-accent/5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Tren
    </a>
</div>
@endsection
