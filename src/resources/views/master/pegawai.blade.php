@extends('layouts.app')

@section('title', 'Master Pegawai')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-2 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ number_format($totalPegawai) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Pegawai</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-gold">{{ number_format($bebasLokasi) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Bebas Lokasi</p>
    </div>
</div>

<!-- Filter -->
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-pandora-muted mb-1">Cari (NIP / Nama)</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Ketik NIP atau nama..."
                   class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
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
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Cari</button>
    </form>
</div>

<style>
    .info-tip { position: relative; }
    .info-tip .tip-text {
        display: none; position: fixed; width: 240px;
        background: #182742; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 10px 14px;
        font-size: 11px; line-height: 1.6; color: #e0e6ed; white-space: normal;
        text-transform: none; letter-spacing: normal; font-weight: 400;
        z-index: 99999; pointer-events: none;
        box-shadow: 0 12px 32px rgba(0,0,0,0.6);
    }
    .info-tip:hover .tip-text { display: block; }
</style>

<!-- Tabel -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">NIP</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Unit</th>
                    <th class="px-4 py-3 text-center">
                        <span class="inline-flex items-center gap-1 info-tip justify-center">
                            Status
                            <span class="inline-flex w-3.5 h-3.5 rounded-full bg-pandora-accent/20 text-pandora-accent items-center justify-center text-[9px] cursor-help">?</span>
                            <span class="tip-text">
                                <strong class="text-pandora-accent">Status Kepegawaian</strong><br>
                                Diambil dari jabatan aktif terakhir di SIKARA.<br><br>
                                <b>1</b> — CPNS<br>
                                <b>2</b> — PNS<br>
                                <b>17</b> — Pegawai Dipekerjakan<br>
                                <b>19</b> — PPPK<br>
                                <b>21</b> — PPPK Paruh Waktu
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-3 text-center">Bebas Lokasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($pegawai as $p)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-muted font-mono text-xs">{{ $p->nip ?: '-' }}</td>
                        <td class="px-4 py-3 text-pandora-text">{{ $p->nama ?: '(tanpa nama)' }}</td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($p->nama_unit ?? '-', 30) }}</td>
                        @php
                            $statusMap = [1 => 'CPNS', 2 => 'PNS', 10 => 'Kontrak', 11 => 'Tugas Belajar', 16 => 'Perbantuan', 17 => 'Dipekerjakan', 19 => 'PPPK', 21 => 'PPPK Paruh Waktu'];
                            $statusLabel = $statusMap[$p->status] ?? $p->status;
                            $statusColor = match((int)($p->status ?? 0)) {
                                2 => 'pandora-success',
                                1 => 'pandora-accent',
                                19, 21 => 'pandora-gold',
                                default => 'pandora-muted',
                            };
                        @endphp
                        <td class="px-4 py-3 text-center">
                            @if($p->status)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $statusColor }}/20 text-{{ $statusColor }}">{{ $statusLabel }}</span>
                            @else
                                <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($p->bebas_lokasi)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-gold/20 text-pandora-gold">Ya</span>
                            @else
                                <span class="text-pandora-muted/50">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-pandora-muted">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pegawai->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $pegawai->withQueryString()->links() }}
        </div>
    @endif
</div>
<script>
document.querySelectorAll('.info-tip').forEach(tip => {
    const text = tip.querySelector('.tip-text');
    if (!text) return;
    tip.addEventListener('mouseenter', () => {
        const rect = tip.getBoundingClientRect();
        text.style.left = Math.min(rect.left, window.innerWidth - 260) + 'px';
        text.style.top = (rect.top - text.offsetHeight - 8) + 'px';
        if (rect.top - text.offsetHeight - 8 < 0) text.style.top = (rect.bottom + 8) + 'px';
    });
});
</script>
@endsection
