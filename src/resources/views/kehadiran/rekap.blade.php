@extends('layouts.app')

@section('title', 'Rekap Kehadiran')

@section('content')
<!-- Filter -->
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Tanggal</label>
            <input type="date" name="tanggal" value="{{ $tanggal }}"
                   class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Unit / OPD</label>
            <select name="unit" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none min-w-[200px]">
                <option value="">Semua OPD</option>
                @foreach($units as $u)
                    <option value="{{ $u->id_unit }}" {{ $unitFilter == $u->id_unit ? 'selected' : '' }}>{{ \Illuminate\Support\Str::limit($u->nama_unit, 50) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Tampilkan</button>
    </form>
</div>

<!-- Ringkasan -->
@if($summary && $summary->total > 0)
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
    @php
        $stats = [
            ['label' => 'Tepat Waktu', 'value' => $summary->tw, 'color' => 'text-pandora-success'],
            ['label' => 'Terlambat', 'value' => $summary->mkttw, 'color' => 'text-pandora-gold'],
            ['label' => 'Tidak Hadir', 'value' => $summary->tk, 'color' => 'text-pandora-danger'],
            ['label' => 'Izin/Sakit/Cuti', 'value' => $summary->izin + $summary->sakit + $summary->cuti, 'color' => 'text-pandora-accent'],
            ['label' => 'DL/Dispensasi', 'value' => $summary->dl + $summary->dsp, 'color' => 'text-pandora-muted'],
        ];
    @endphp
    @foreach($stats as $stat)
        <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
            <p class="text-xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            <p class="text-xs text-pandora-muted mt-1">{{ $stat['label'] }}</p>
        </div>
    @endforeach
</div>
@endif

<!-- Tabel -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">NIP</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Unit</th>
                    <th class="px-4 py-3 text-center">Masuk</th>
                    <th class="px-4 py-3 text-center">Pulang</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-left">Lokasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($rekap as $r)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-muted font-mono text-xs">{{ $r->nip }}</td>
                        <td class="px-4 py-3 text-pandora-text">{{ $r->nama }}</td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($r->nama_unit ?? '-', 25) }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ $r->jam_masuk ? substr($r->jam_masuk, 0, 5) : '-' }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ $r->jam_pulang ? substr($r->jam_pulang, 0, 5) : '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($r->tw) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-success/20 text-pandora-success">TW</span>
                            @elseif($r->mkttw) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-gold/20 text-pandora-gold">MKTTW</span>
                            @elseif($r->tk) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-danger/20 text-pandora-danger">TK</span>
                            @elseif($r->dl) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent">DL</span>
                            @elseif($r->dsp) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-muted/20 text-pandora-muted">DSP</span>
                            @elseif($r->i) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent">I</span>
                            @elseif($r->s) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent">S</span>
                            @elseif($r->c) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent">C</span>
                            @elseif($r->ta) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-danger/20 text-pandora-danger">TA</span>
                            @else <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($r->nama_lokasi_berangkat ?? '-', 20) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-pandora-muted">Tidak ada data untuk tanggal ini</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rekap->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $rekap->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
