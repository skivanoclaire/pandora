@extends('layouts.app')

@section('title', 'Detail Kehadiran — ' . \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'))

@section('content')
@php $tgl = \Carbon\Carbon::parse($tanggal); @endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs text-pandora-muted mb-6">
    <a href="/analitik/tren" class="hover:text-pandora-accent transition-colors">Tren Kehadiran</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-pandora-text">{{ $tgl->translatedFormat('d F Y') }}</span>
</div>

{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-1">{{ $tgl->translatedFormat('l, d F Y') }}</h1>
    <p class="text-pandora-muted text-sm">Rincian kehadiran per pegawai dan instansi. Jadwal masuk: {{ \Carbon\Carbon::parse($jamMasuk)->format('H:i') }} WITA.</p>
</div>

{{-- Stats Row 1 --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-text">{{ number_format($summary->total) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Pegawai</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-success">{{ number_format($summary->hadir) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Hadir</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-gold">{{ number_format($summary->terlambat) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Terlambat</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-danger">{{ number_format($summary->tidak_hadir) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Tidak Hadir</p>
    </div>
</div>

{{-- Stats Row 2: Rincian ketidakhadiran --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 p-4 mb-6">
    <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3">Rincian {{ number_format($summary->tidak_hadir) }} Pegawai Tidak Hadir</h3>
    <div class="grid grid-cols-3 sm:grid-cols-7 gap-2 text-center">
        <div class="p-2 rounded-lg bg-pandora-dark/50">
            <p class="text-lg font-bold text-pandora-accent">{{ $summary->dinas_luar }}</p>
            <p class="text-[10px] text-pandora-muted">Dinas Luar</p>
        </div>
        <div class="p-2 rounded-lg bg-pandora-dark/50">
            <p class="text-lg font-bold text-pandora-accent">{{ $summary->dinas_dalam }}</p>
            <p class="text-[10px] text-pandora-muted">Dinas Dalam</p>
        </div>
        <div class="p-2 rounded-lg bg-pandora-dark/50">
            <p class="text-lg font-bold text-pandora-muted">{{ $summary->cuti }}</p>
            <p class="text-[10px] text-pandora-muted">Cuti</p>
        </div>
        <div class="p-2 rounded-lg bg-pandora-dark/50">
            <p class="text-lg font-bold text-pandora-muted">{{ $summary->sakit }}</p>
            <p class="text-[10px] text-pandora-muted">Sakit</p>
        </div>
        <div class="p-2 rounded-lg bg-pandora-dark/50">
            <p class="text-lg font-bold text-pandora-muted">{{ $summary->dispensasi }}</p>
            <p class="text-[10px] text-pandora-muted">Dispensasi</p>
        </div>
        <div class="p-2 rounded-lg bg-pandora-dark/50">
            <p class="text-lg font-bold text-pandora-muted">{{ $summary->diklat }}</p>
            <p class="text-[10px] text-pandora-muted">Diklat</p>
        </div>
        <div class="p-2 rounded-lg {{ $summary->tanpa_keterangan > 0 ? 'bg-pandora-danger/10 border border-pandora-danger/20' : 'bg-pandora-dark/50' }}">
            <p class="text-lg font-bold {{ $summary->tanpa_keterangan > 0 ? 'text-pandora-danger' : 'text-pandora-muted' }}">{{ $summary->tanpa_keterangan }}</p>
            <p class="text-[10px] text-pandora-muted">Tanpa Ket.</p>
        </div>
    </div>
</div>

{{-- Ranking Instansi Paling Banyak Terlambat --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Instansi Paling Banyak Terlambat
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-center w-10">#</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Terlambat</th>
                    <th class="px-4 py-3 text-center">Total Pegawai</th>
                    <th class="px-4 py-3 text-center">% Terlambat</th>
                    <th class="px-4 py-3 text-center">Rata-rata</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($rankInstansi as $i => $inst)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5 text-center text-pandora-muted text-xs">{{ $i + 1 }}</td>
                        <td class="px-4 py-2.5 text-pandora-text text-sm">{{ $inst->nama_unit }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="text-pandora-gold font-semibold">{{ $inst->jumlah_terlambat }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-center text-pandora-muted">{{ $inst->total_pegawai }}</td>
                        <td class="px-4 py-2.5 text-center">
                            @php $pct = $inst->persen_terlambat; @endphp
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 h-1.5 rounded-full bg-pandora-dark overflow-hidden">
                                    <div class="h-full rounded-full {{ $pct >= 50 ? 'bg-pandora-danger' : ($pct >= 25 ? 'bg-pandora-gold' : 'bg-pandora-accent') }}" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-xs {{ $pct >= 50 ? 'text-pandora-danger' : ($pct >= 25 ? 'text-pandora-gold' : 'text-pandora-muted') }}">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-center text-pandora-muted text-xs">{{ $inst->rata_menit }} mnt</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-pandora-muted">Tidak ada yang terlambat</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Daftar Pegawai Terlambat --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Pegawai Terlambat
        </h2>
        <span class="text-xs text-pandora-gold bg-pandora-gold/10 px-2 py-0.5 rounded-full font-medium">{{ $terlambat->count() }}{{ $terlambat->count() >= 100 ? '+' : '' }} orang</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Pegawai</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Jam Masuk</th>
                    <th class="px-4 py-3 text-center">Terlambat</th>
                    <th class="px-4 py-3 text-left">Lokasi Check-in</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($terlambat as $p)
                    @php $menit = round($p->menit_terlambat); @endphp
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5">
                            <p class="text-pandora-text text-sm">{{ $p->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $p->nip }}</p>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($p->nama_unit ?? '-', 30) }}</td>
                        <td class="px-4 py-2.5 text-center font-mono text-pandora-text text-sm">{{ \Carbon\Carbon::parse($p->jam_masuk)->format('H:i') }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $menit >= 60 ? 'bg-pandora-danger/20 text-pandora-danger' : ($menit >= 30 ? 'bg-pandora-gold/20 text-pandora-gold' : 'bg-pandora-accent/20 text-pandora-accent') }}">
                                {{ $menit >= 60 ? floor($menit/60) . 'j ' . ($menit%60) . 'm' : $menit . ' mnt' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($p->nama_lokasi_berangkat ?? '-', 35) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-pandora-muted">Tidak ada yang terlambat</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Daftar Tidak Hadir --}}
<div x-data="{ show: false }" class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <button @click="show = !show" class="w-full px-5 py-3 flex items-center justify-between hover:bg-pandora-dark/30 transition-colors">
        <h2 class="text-sm font-semibold text-pandora-text flex items-center gap-2">
            <svg class="w-4 h-4 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Tidak Hadir ({{ $tidakHadir->count() }}{{ $tidakHadir->count() >= 100 ? '+' : '' }})
        </h2>
        <svg class="w-4 h-4 text-pandora-muted transition-transform" :class="show && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="show" x-collapse>
        <div class="overflow-x-auto border-t border-white/5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Pegawai</th>
                        <th class="px-4 py-3 text-left">Instansi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($tidakHadir as $p)
                        <tr class="hover:bg-pandora-dark/30 transition-colors">
                            <td class="px-4 py-2">
                                <span class="text-pandora-text text-sm">{{ $p->nama }}</span>
                                <span class="text-pandora-muted text-xs font-mono ml-2">{{ $p->nip }}</span>
                            </td>
                            <td class="px-4 py-2 text-pandora-muted text-xs">{{ $p->nama_unit ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-pandora-muted">Semua pegawai hadir</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
