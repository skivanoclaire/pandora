@extends('layouts.app')

@section('title', 'Master Instansi')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-2 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ number_format($totalUnit) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Unit</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-success">{{ number_format($totalPegawai) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Pegawai</p>
    </div>
</div>

<!-- Filter -->
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-pandora-muted mb-1">Cari Instansi</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Ketik nama instansi..."
                   class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Level</label>
            <select name="level" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="">Semua</option>
                @foreach($levels as $l)
                    <option value="{{ $l }}" {{ $level == $l ? 'selected' : '' }}>Level {{ $l }}</option>
                @endforeach
            </select>
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
                    <th class="px-4 py-3 text-left">Kode</th>
                    <th class="px-4 py-3 text-left">Nama Instansi / Unit</th>
                    <th class="px-4 py-3 text-center">Level</th>
                    <th class="px-4 py-3 text-center">Pegawai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($instansi as $i)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-muted font-mono text-xs">{{ $i->kode_unit ?: '-' }}</td>
                        <td class="px-4 py-3 text-pandora-text">{{ $i->nama_unit ?: '(tanpa nama)' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($i->level)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent">{{ $i->level }}</span>
                            @else
                                <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ $i->jml_pegawai > 0 ? $i->jml_pegawai : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-pandora-muted">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($instansi->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $instansi->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
