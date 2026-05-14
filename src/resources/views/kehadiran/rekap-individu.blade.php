@extends('layouts.app')

@section('title', $pegawai ? 'Rekap Individu — ' . $pegawai->nama : 'Rekap Individu')

@section('content')
@php
    $bulanNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $currentYear = (int) date('Y');
@endphp

{{-- Search --}}
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[250px]">
            <label class="block text-xs text-pandora-muted mb-1">Cari Pegawai (Nama atau NIP)</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Ketik nama atau NIP..."
                   class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Cari</button>
    </form>
</div>

{{-- Search Results --}}
@if($results->count() > 0 && !$pegawai)
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text">Hasil Pencarian — {{ $results->count() }} ditemukan</h2>
    </div>
    <div class="divide-y divide-white/5">
        @foreach($results as $r)
            <a href="{{ route('kehadiran.rekap-individu', ['pegawai' => $r->id_pegawai, 'mode' => 'bulan', 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="flex items-center justify-between px-5 py-3 hover:bg-pandora-dark/30 transition-colors">
                <div>
                    <p class="text-sm text-pandora-text">{{ $r->nama }}</p>
                    <p class="text-xs text-pandora-muted font-mono">{{ $r->nip }}</p>
                </div>
                <span class="text-xs text-pandora-muted">{{ \Illuminate\Support\Str::limit($r->nama_unit ?? '-', 35) }}</span>
            </a>
        @endforeach
    </div>
</div>
@elseif($search && !$pegawai && $results->isEmpty())
<div class="bg-pandora-surface rounded-xl border border-white/5 p-8 text-center mb-5">
    <p class="text-pandora-muted text-sm">Tidak ditemukan pegawai dengan pencarian "{{ $search }}"</p>
</div>
@endif

{{-- Pegawai Selected --}}
@if($pegawai)
    {{-- Info Pegawai --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-5 mb-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-white">{{ $pegawai->nama }}</h1>
                <p class="text-pandora-muted text-sm font-mono mt-1">{{ $pegawai->nip }}</p>
                <p class="text-pandora-accent text-xs mt-1">{{ $pegawai->nama_unit ?? '-' }}</p>
            </div>
            <a href="{{ route('kehadiran.rekap-individu', ['search' => $search]) }}"
               class="text-xs text-pandora-muted hover:text-pandora-accent transition-colors px-3 py-1.5 rounded-lg hover:bg-pandora-accent/5">
                Ganti Pegawai
            </a>
        </div>
    </div>

    {{-- Filter Periode --}}
    <div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="pegawai" value="{{ $pegawai->id_pegawai }}">
            <div>
                <label class="block text-xs text-pandora-muted mb-1">Tampilan</label>
                <select name="mode" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                    <option value="bulan" {{ $mode === 'bulan' ? 'selected' : '' }}>Bulanan</option>
                    <option value="tahun" {{ $mode === 'tahun' ? 'selected' : '' }}>Tahunan</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-pandora-muted mb-1">Tahun</label>
                <select name="tahun" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                    @for($y = $currentYear; $y >= 2018; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @if($mode === 'bulan')
            <div>
                <label class="block text-xs text-pandora-muted mb-1">Bulan</label>
                <select name="bulan" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>{{ $bulanNames[$m] }}</option>
                    @endfor
                </select>
            </div>
            @endif
            <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Tampilkan</button>
        </form>
    </div>

    {{-- Summary --}}
    @if($summary)
    <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-10 gap-3 mb-5">
        @php
            $stats = [
                ['label' => 'Hari Kerja', 'value' => $summary->hari_kerja, 'color' => 'text-pandora-text'],
                ['label' => 'Hadir', 'value' => $summary->hadir, 'color' => 'text-pandora-success'],
                ['label' => 'Tepat Waktu', 'value' => $summary->tw, 'color' => 'text-pandora-success'],
                ['label' => 'Terlambat', 'value' => $summary->terlambat, 'color' => 'text-pandora-gold'],
                ['label' => 'Pulang Cepat', 'value' => $summary->pulang_cepat, 'color' => $summary->pulang_cepat > 0 ? 'text-pandora-gold' : 'text-pandora-muted'],
                ['label' => 'TK', 'value' => $summary->tk, 'color' => $summary->tk > 0 ? 'text-pandora-danger' : 'text-pandora-muted'],
                ['label' => 'Izin', 'value' => $summary->ijin, 'color' => 'text-pandora-accent'],
                ['label' => 'Sakit', 'value' => $summary->sakit, 'color' => 'text-pandora-accent'],
                ['label' => 'Cuti', 'value' => $summary->cuti, 'color' => 'text-pandora-accent'],
                ['label' => 'DL/Diklat', 'value' => $summary->dl, 'color' => 'text-pandora-accent'],
                ['label' => '% Hadir', 'value' => $summary->persen_hadir . '%', 'color' => $summary->persen_hadir >= 80 ? 'text-pandora-success' : ($summary->persen_hadir >= 60 ? 'text-pandora-gold' : 'text-pandora-danger')],
            ];
        @endphp
        @foreach($stats as $stat)
            <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
                <p class="text-lg font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
                <p class="text-[10px] text-pandora-muted mt-1">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Tabel Rekap --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
        <div class="px-5 py-3 border-b border-white/5">
            <h2 class="text-sm font-semibold text-pandora-text">
                @if($mode === 'bulan')
                    Rekap {{ $bulanNames[$bulan] }} {{ $tahun }}
                @else
                    Rekap Tahun {{ $tahun }}
                @endif
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-center">Hari</th>
                        <th class="px-4 py-3 text-center">Masuk</th>
                        <th class="px-4 py-3 text-left">Koordinat Masuk</th>
                        <th class="px-4 py-3 text-center">Pulang</th>
                        <th class="px-4 py-3 text-left">Koordinat Pulang</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @php $prevMonth = null; @endphp
                    @foreach($rekap as $r)
                        @php
                            $rMonth = \Carbon\Carbon::parse($r->tanggal)->month;
                            $showMonthHeader = $mode === 'tahun' && $prevMonth !== $rMonth;
                            $prevMonth = $rMonth;
                        @endphp
                        @if($showMonthHeader)
                            <tr class="bg-pandora-dark/70">
                                <td colspan="8" class="px-4 py-2 text-xs font-semibold text-pandora-accent uppercase tracking-wider">
                                    {{ $bulanNames[$rMonth] }} {{ $tahun }}
                                </td>
                            </tr>
                        @endif
                        <tr class="{{ !$r->is_hari_kerja ? 'bg-pandora-dark/20 opacity-60' : ($r->status === 'TK' ? 'bg-pandora-danger/5' : '') }} hover:bg-pandora-dark/30 transition-colors">
                            <td class="px-4 py-2 text-pandora-text text-xs">{{ \Carbon\Carbon::parse($r->tanggal)->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-center text-pandora-muted text-xs">{{ $r->hari }}</td>
                            <td class="px-4 py-2 text-center font-mono text-xs {{ $r->jam_masuk ? 'text-pandora-text' : 'text-pandora-muted' }}">
                                {{ $r->jam_masuk ? substr($r->jam_masuk, 0, 5) : '-' }}
                            </td>
                            <td class="px-4 py-2 text-xs">
                                @if($r->lat_berangkat)
                                    <span class="font-mono text-pandora-muted/60">{{ rtrim(rtrim(number_format($r->lat_berangkat, 7), '0'), '.') }}, {{ rtrim(rtrim(number_format($r->long_berangkat, 7), '0'), '.') }}</span>
                                    @if($r->nama_lokasi_berangkat)
                                        <br><span class="text-pandora-muted">{{ \Illuminate\Support\Str::limit($r->nama_lokasi_berangkat, 30) }}</span>
                                    @endif
                                @else
                                    <span class="text-pandora-muted">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center font-mono text-xs {{ $r->jam_pulang ? 'text-pandora-text' : 'text-pandora-muted' }}">
                                {{ $r->jam_pulang ? substr($r->jam_pulang, 0, 5) : '-' }}
                            </td>
                            <td class="px-4 py-2 text-xs">
                                @if($r->lat_pulang)
                                    <span class="font-mono text-pandora-muted/60">{{ rtrim(rtrim(number_format($r->lat_pulang, 7), '0'), '.') }}, {{ rtrim(rtrim(number_format($r->long_pulang, 7), '0'), '.') }}</span>
                                    @if($r->nama_lokasi_pulang)
                                        <br><span class="text-pandora-muted">{{ \Illuminate\Support\Str::limit($r->nama_lokasi_pulang, 30) }}</span>
                                    @endif
                                @else
                                    <span class="text-pandora-muted">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($r->status !== '-')
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $r->status_color }}/20 text-{{ $r->status_color }}">{{ $r->status }}</span>
                                    @if($r->status_pulang === 'PLC')
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-gold/20 text-pandora-gold ml-1">PLC</span>
                                    @endif
                                @else
                                    <span class="text-pandora-muted text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-pandora-muted text-xs">
                                @if($r->keterangan_libur)
                                    {{ $r->keterangan_libur }}
                                @elseif($r->keterangan_ijin)
                                    {{ \Illuminate\Support\Str::limit($r->keterangan_ijin, 40) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif(!$search)
    {{-- Empty State --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-12 text-center">
        <svg class="w-12 h-12 text-pandora-muted/30 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <p class="text-pandora-muted text-sm">Cari pegawai berdasarkan nama atau NIP untuk melihat rekap kehadiran individu.</p>
    </div>
@endif
@endsection
