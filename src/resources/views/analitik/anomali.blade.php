@extends('layouts.app')

@section('title', 'Deteksi Anomali')

@section('content')
@if(session('success'))
    <div class="mb-5 px-4 py-3 rounded-lg bg-pandora-success/10 border border-pandora-success/20 text-pandora-success text-sm">
        {{ session('success') }}
    </div>
@endif

<!-- Ringkasan -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    @php
        $tingkatLabels = [1 => 'T1 — Fisik', 2 => 'T2 — Rule', 3 => 'T3 — ML', 4 => 'T4 — FP'];
        $tingkatColors = [1 => 'pandora-danger', 2 => 'pandora-gold', 3 => 'pandora-accent', 4 => 'pandora-muted'];
        $tingkatDesc = [
            1 => 'Ketidakmungkinan fisik — tidak mungkin terjadi secara nyata. Contoh: koordinat GPS identik persis berhari-hari (indikasi fake GPS), perpindahan lokasi antar sesi absensi yang mustahil secara jarak dan waktu, absen dari luar wilayah Kaltara.',
            2 => 'Pelanggaran aturan formal — melanggar rule tertulis setelah mempertimbangkan status. Contoh: absen di lokasi yang tidak sesuai jadwal, absen sore di hari pertama dinas luar.',
            3 => 'Anomali statistik — terdeteksi ML (Isolation Forest / DBSCAN) sebagai pola tidak biasa dibanding pegawai lain. Belum tentu kecurangan, perlu verifikasi konteks.',
            4 => 'Kandidat false positive — terdeteksi sistem tapi kemungkinan besar ada konteks legitimate. Contoh: pegawai bebas lokasi, SK dinas luar retroaktif.',
        ];
    @endphp
    @foreach([1,2,3,4] as $t)
        <a href="?tingkat={{ $t }}&status=belum_direview"
           class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center hover:border-{{ $tingkatColors[$t] }}/30 transition-colors {{ $tingkat == $t ? 'ring-1 ring-'.$tingkatColors[$t] : '' }} info-tip">
            <p class="text-xl font-bold text-{{ $tingkatColors[$t] }}">{{ $statsByTingkat[$t] ?? 0 }}</p>
            <p class="text-xs text-pandora-muted mt-1 flex items-center justify-center gap-1">
                {{ $tingkatLabels[$t] }}
                <span class="inline-flex w-3 h-3 rounded-full bg-{{ $tingkatColors[$t] }}/20 text-{{ $tingkatColors[$t] }} items-center justify-center text-[8px]">?</span>
            </p>
            <span class="tip-text">
                <strong class="text-{{ $tingkatColors[$t] }}">{{ $tingkatLabels[$t] }}</strong><br>
                {{ $tingkatDesc[$t] }}
            </span>
        </a>
    @endforeach
</div>

<!-- Filter -->
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Tingkat</label>
            <select name="tingkat" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="">Semua</option>
                @foreach([1,2,3,4] as $t)
                    <option value="{{ $t }}" {{ $tingkat == $t ? 'selected' : '' }}>Tingkat {{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Jenis</label>
            <select name="jenis" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="">Semua</option>
                @foreach(['fake_gps','geofence_violation','velocity_outlier','temporal_outlier','combination'] as $j)
                    <option value="{{ $j }}" {{ $jenis == $j ? 'selected' : '' }}>{{ str_replace('_', ' ', $j) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Status</label>
            <select name="status" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="">Semua</option>
                <option value="belum_direview" {{ $status == 'belum_direview' ? 'selected' : '' }}>Belum Direview</option>
                <option value="valid" {{ $status == 'valid' ? 'selected' : '' }}>Valid</option>
                <option value="false_positive" {{ $status == 'false_positive' ? 'selected' : '' }}>False Positive</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Urutkan</label>
            <select name="sort" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="detected_at" {{ $sort == 'detected_at' ? 'selected' : '' }}>Waktu Deteksi</option>
                <option value="confidence" {{ $sort == 'confidence' ? 'selected' : '' }}>Confidence</option>
                <option value="tingkat" {{ $sort == 'tingkat' ? 'selected' : '' }}>Tingkat</option>
                <option value="tanggal" {{ $sort == 'tanggal' ? 'selected' : '' }}>Tanggal Kejadian</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Arah</label>
            <select name="dir" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="desc" {{ $dir == 'desc' ? 'selected' : '' }}>Tertinggi</option>
                <option value="asc" {{ $dir == 'asc' ? 'selected' : '' }}>Terendah</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Filter</button>
        <a href="{{ route('analitik.anomali.export', request()->query()) }}"
           class="px-4 py-2 bg-pandora-surface-light text-pandora-muted text-sm rounded-lg hover:text-pandora-accent hover:bg-pandora-accent/10 transition-colors flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export PDF
        </a>
    </form>
</div>

@php
    // Helper untuk sort link di header tabel
    $sortUrl = function($col) use ($sort, $dir, $tingkat, $jenis, $status) {
        $newDir = ($sort === $col && $dir === 'desc') ? 'asc' : 'desc';
        return '?' . http_build_query(array_filter([
            'tingkat' => $tingkat, 'jenis' => $jenis, 'status' => $status,
            'sort' => $col, 'dir' => $newDir,
        ], fn($v) => $v !== null && $v !== ''));
    };
    $sortIcon = function($col) use ($sort, $dir) {
        if ($sort !== $col) return '';
        return $dir === 'desc' ? ' &darr;' : ' &uarr;';
    };
@endphp

<!-- Tooltip style -->
<style>
    .info-tip { position: relative; }
    .info-tip .tip-text {
        display: none; position: fixed; width: 280px;
        background: #182742; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 10px 14px;
        font-size: 11px; line-height: 1.6; color: #e0e6ed; white-space: normal;
        text-transform: none; letter-spacing: normal; font-weight: 400;
        z-index: 99999; pointer-events: none;
        box-shadow: 0 12px 32px rgba(0,0,0,0.6), 0 0 0 1px rgba(0,180,216,0.1);
    }
    .info-tip:hover .tip-text { display: block; }
</style>

<!-- Tabel Anomali -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-center">
                        <a href="{{ $sortUrl('tingkat') }}" class="hover:text-pandora-accent transition-colors">T{!! $sortIcon('tingkat') !!}</a>
                    </th>
                    <th class="px-4 py-3 text-left">Pegawai</th>
                    <th class="px-4 py-3 text-left">Unit</th>
                    <th class="px-4 py-3 text-center">
                        <a href="{{ $sortUrl('tanggal') }}" class="hover:text-pandora-accent transition-colors">Tanggal{!! $sortIcon('tanggal') !!}</a>
                    </th>
                    <th class="px-4 py-3 text-left">
                        <span class="inline-flex items-center gap-1 info-tip">
                            Jenis
                            <span class="inline-flex w-3.5 h-3.5 rounded-full bg-pandora-accent/20 text-pandora-accent items-center justify-center text-[9px] cursor-help">?</span>
                            <span class="tip-text">
                                <strong class="text-pandora-accent">Kategori anomali:</strong><br>
                                <b>fake_gps</b> — pemalsuan lokasi GPS<br>
                                <b>geofence_violation</b> — absen di luar zona yang diizinkan<br>
                                <b>velocity_outlier</b> — perpindahan lokasi terlalu cepat<br>
                                <b>temporal_outlier</b> — waktu absen tidak biasa<br>
                                <b>combination</b> — gabungan beberapa faktor
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-3 text-center">
                        <span class="inline-flex items-center gap-1 info-tip justify-center">
                            <a href="{{ $sortUrl('confidence') }}" class="hover:text-pandora-accent transition-colors">Confidence{!! $sortIcon('confidence') !!}</a>
                            <span class="inline-flex w-3.5 h-3.5 rounded-full bg-pandora-accent/20 text-pandora-accent items-center justify-center text-[9px] cursor-help">?</span>
                            <span class="tip-text">
                                <strong class="text-pandora-accent">Tingkat keyakinan sistem (0–100%)</strong><br>
                                Semakin tinggi, semakin yakin sistem bahwa ini anomali nyata.<br><br>
                                <span class="text-pandora-danger">&gt;80%</span> — Sangat yakin, prioritas tinggi<br>
                                <span class="text-pandora-gold">60–80%</span> — Cukup yakin, perlu verifikasi<br>
                                <span class="text-pandora-muted">&lt;60%</span> — Rendah, mungkin false positive
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-3 text-left">
                        <span class="inline-flex items-center gap-1 info-tip">
                            Metode
                            <span class="inline-flex w-3.5 h-3.5 rounded-full bg-pandora-accent/20 text-pandora-accent items-center justify-center text-[9px] cursor-help">?</span>
                            <span class="tip-text">
                                <strong class="text-pandora-accent">Cara sistem mendeteksi:</strong><br>
                                <b>rule_engine</b> — aturan pasti (misal: koordinat identik berhari-hari)<br>
                                <b>isolation_forest</b> — ML mendeteksi kombinasi fitur yang tidak biasa<br>
                                <b>dbscan</b> — clustering spasial, lokasi yang terisolasi dari pola umum
                            </span>
                        </span>
                    </th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($anomalies as $a)
                    @php $meta = json_decode($a->metadata, true) ?? []; @endphp
                    <tr class="hover:bg-pandora-dark/30 transition-colors" x-data="{ showDetail: false }">
                        <td class="px-4 py-3 text-center">
                            @if($a->tingkat === 1) <span class="inline-flex w-6 h-6 rounded-full bg-pandora-danger/20 text-pandora-danger items-center justify-center text-xs font-bold">1</span>
                            @elseif($a->tingkat === 2) <span class="inline-flex w-6 h-6 rounded-full bg-pandora-gold/20 text-pandora-gold items-center justify-center text-xs font-bold">2</span>
                            @else <span class="inline-flex w-6 h-6 rounded-full bg-pandora-accent/20 text-pandora-accent items-center justify-center text-xs font-bold">{{ $a->tingkat }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-pandora-text text-sm">{{ $a->nama }}</p>
                            <p class="text-pandora-muted text-xs font-mono">{{ $a->nip }}</p>
                        </td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($a->nama_unit ?? '-', 20) }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text text-xs">{{ $a->tanggal }}</td>
                        <td class="px-4 py-3">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-primary-light/30 text-pandora-muted">{{ str_replace('_', ' ', $a->jenis_anomali) }}</span>
                            @if(isset($meta['rule']))
                                <p class="text-[10px] text-pandora-muted/60 mt-0.5">{{ $meta['rule'] }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs font-medium {{ $a->confidence >= 0.8 ? 'text-pandora-danger' : ($a->confidence >= 0.6 ? 'text-pandora-gold' : 'text-pandora-muted') }}">{{ round($a->confidence * 100) }}%</span>
                        </td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ str_replace('_', ' ', $a->metode_deteksi) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($a->status_review === 'belum_direview') <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-gold/20 text-pandora-gold">Pending</span>
                            @elseif($a->status_review === 'valid') <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-danger/20 text-pandora-danger">Valid</span>
                            @elseif($a->status_review === 'false_positive') <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-muted/20 text-pandora-muted">FP</span>
                            @else <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/20 text-pandora-accent">{{ $a->status_review }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                            <a href="{{ route('analitik.anomali.detail', $a->id) }}"
                               class="px-2 py-1 rounded text-xs bg-pandora-surface-light text-pandora-muted hover:text-pandora-accent hover:bg-pandora-accent/10 transition-colors">
                                Detail
                            </a>
                            @if($a->status_review === 'belum_direview')
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" class="px-2 py-1 rounded text-xs bg-pandora-accent/10 text-pandora-accent hover:bg-pandora-accent/20">
                                        Review
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-transition
                                         class="absolute right-0 mt-1 bg-pandora-surface border border-white/10 rounded-lg p-3 shadow-xl z-20 w-64">
                                        <form method="POST" action="{{ route('analitik.anomali.review', $a->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <textarea name="catatan_review" placeholder="Catatan (opsional)..."
                                                      class="w-full bg-pandora-dark border border-white/10 rounded px-2 py-1 text-xs text-pandora-text mb-2" rows="2"></textarea>
                                            <div class="flex gap-2">
                                                <button type="submit" name="status_review" value="valid"
                                                        class="flex-1 px-2 py-1.5 rounded text-xs font-medium bg-pandora-danger/20 text-pandora-danger hover:bg-pandora-danger/30">
                                                    Valid
                                                </button>
                                                <button type="submit" name="status_review" value="false_positive"
                                                        class="flex-1 px-2 py-1.5 rounded text-xs font-medium bg-pandora-success/20 text-pandora-success hover:bg-pandora-success/30">
                                                    False Positive
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="text-[10px] px-1.5 py-0.5 rounded {{ $a->status_review === 'valid' ? 'bg-pandora-danger/20 text-pandora-danger' : 'bg-pandora-success/20 text-pandora-success' }}">
                                    {{ $a->status_review }}
                                </span>
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-pandora-muted">Tidak ada anomali ditemukan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($anomalies->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $anomalies->withQueryString()->links() }}
        </div>
    @endif
</div>
<script>
document.querySelectorAll('.info-tip').forEach(tip => {
    const text = tip.querySelector('.tip-text');
    if (!text) return;
    tip.addEventListener('mouseenter', e => {
        const rect = tip.getBoundingClientRect();
        text.style.left = Math.min(rect.left, window.innerWidth - 300) + 'px';
        text.style.top = (rect.top - text.offsetHeight - 8) + 'px';
        // If too high, show below instead
        if (rect.top - text.offsetHeight - 8 < 0) {
            text.style.top = (rect.bottom + 8) + 'px';
        }
    });
});
</script>
@endsection
