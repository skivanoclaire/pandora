@extends('layouts.app')

@section('title', 'Log Presensi')

@section('content')
<!-- Filter -->
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Tanggal</label>
            <input type="date" name="tanggal" value="{{ $tanggal }}"
                   class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-pandora-muted mb-1">Cari (NIP / Nama)</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Ketik NIP atau nama..."
                   class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Cari</button>
    </form>
</div>

<!-- Tabel -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Nama / NIP</th>
                    <th class="px-4 py-3 text-left">Unit</th>
                    <th class="px-4 py-3 text-center">Masuk</th>
                    <th class="px-4 py-3 text-center">Pulang</th>
                    <th class="px-4 py-3 text-left">Lokasi Masuk</th>
                    <th class="px-4 py-3 text-left">Lokasi Pulang</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($logs as $l)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3">
                            <p class="text-pandora-text text-sm">{{ $l->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $l->nip }}</p>
                        </td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($l->nama_unit ?? '-', 25) }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text font-mono text-xs">{{ $l->jam_masuk ? substr($l->jam_masuk, 0, 5) : '-' }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text font-mono text-xs">{{ $l->jam_pulang ? substr($l->jam_pulang, 0, 5) : '-' }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($l->lat_berangkat)
                                <p class="text-pandora-muted">{{ \Illuminate\Support\Str::limit($l->nama_lokasi_berangkat ?? 'Tidak dikenal', 20) }}</p>
                                <p class="text-pandora-muted/50 font-mono text-[10px]">{{ number_format($l->lat_berangkat, 5) }}, {{ number_format($l->long_berangkat, 5) }}</p>
                            @else
                                <span class="text-pandora-muted/50">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($l->lat_pulang)
                                <p class="text-pandora-muted">{{ \Illuminate\Support\Str::limit($l->nama_lokasi_pulang ?? 'Tidak dikenal', 20) }}</p>
                                <p class="text-pandora-muted/50 font-mono text-[10px]">{{ number_format($l->lat_pulang, 5) }}, {{ number_format($l->long_pulang, 5) }}</p>
                            @else
                                <span class="text-pandora-muted/50">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($l->tw) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-success/20 text-pandora-success">TW</span>
                            @elseif($l->mkttw) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-gold/20 text-pandora-gold">MKTTW</span>
                            @elseif($l->tk) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-danger/20 text-pandora-danger">TK</span>
                            @elseif($l->dl) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent">DL</span>
                            @elseif($l->dsp) <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-muted/20 text-pandora-muted">DSP</span>
                            @else <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-pandora-muted">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
