@extends('layouts.app')

@section('title', 'Evaluasi Model')

@section('content')
@php
    $metodeLabels = [
        'rule_engine' => 'Rule Engine (T1+T2)',
        'isolation_forest' => 'Isolation Forest (T3)',
        'dbscan' => 'DBSCAN (T3)',
        'combination' => 'Combination',
    ];
    $tingkatLabels = [1 => 'Tingkat 1 — Ketidakmungkinan Fisik', 2 => 'Tingkat 2 — Pelanggaran Aturan', 3 => 'Tingkat 3 — Anomali Statistik'];
    $fmt = fn($v, $d = 1) => $v !== null ? number_format($v * 100, $d) . '%' : '—';
@endphp

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white">Evaluasi Model</h1>
        <p class="text-sm text-pandora-muted mt-1">Semi-supervised validation — anomali yang direview sebagai ground truth</p>
    </div>
</div>

{{-- Row 1: Stat Cards + Inline Doughnut --}}
<div class="grid grid-cols-5 gap-4 mb-5">
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-4">
        <p class="text-[10px] text-pandora-muted uppercase tracking-wider">Total Anomali</p>
        <p class="text-2xl font-bold text-white mt-1">{{ number_format($overall->total) }}</p>
        <p class="text-[10px] text-pandora-muted">Evaluable: {{ number_format($overall->total_evaluable) }}</p>
    </div>
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-4">
        <p class="text-[10px] text-pandora-muted uppercase tracking-wider">Sudah Direview</p>
        <p class="text-2xl font-bold text-pandora-accent mt-1">{{ number_format($overall->review_pct, 1) }}%</p>
        <p class="text-[10px] text-pandora-muted">{{ number_format($overall->reviewed) }} / {{ number_format($overall->total) }}</p>
    </div>
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-4">
        <p class="text-[10px] text-pandora-muted uppercase tracking-wider">Precision</p>
        <p class="text-2xl font-bold {{ $overall->precision !== null && $overall->precision >= 0.5 ? 'text-pandora-success' : 'text-pandora-gold' }} mt-1">{{ $fmt($overall->precision) }}</p>
        <p class="text-[10px] text-pandora-muted">TP: {{ $overall->tp }} &middot; FP: {{ $overall->fp }}</p>
        <p class="text-[10px] text-pandora-accent/80">+{{ number_format($overall->policy_exception) }} pengecualian</p>
    </div>
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-4">
        <p class="text-[10px] text-pandora-muted uppercase tracking-wider">F1-Score</p>
        <p class="text-2xl font-bold text-pandora-accent mt-1">{{ $fmt($overall->f1) }}</p>
        <p class="text-[10px] text-pandora-muted">Recall est.: {{ $fmt($overall->recall) }}</p>
    </div>
    {{-- Mini doughnut inline --}}
    <div class="bg-pandora-surface rounded-xl border border-white/5 p-3 flex items-center gap-3">
        <canvas id="chartPie" class="w-16 h-16 flex-shrink-0"></canvas>
        <div class="text-[10px] leading-relaxed">
            <p class="text-pandora-danger">{{ number_format($overall->tp) }} valid</p>
            <p class="text-pandora-success">{{ number_format($overall->fp) }} FP</p>
            <p class="text-pandora-accent">{{ number_format($overall->policy_exception) }} pengec.</p>
            <p class="text-pandora-muted">{{ number_format($overall->belum_direview) }} belum</p>
        </div>
    </div>
</div>

{{-- Row 2: Bar chart compact --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 p-4 mb-5">
    <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3">Precision & F1 per Metode</h3>
    <div class="h-40">
        <canvas id="chartMetode"></canvas>
    </div>
</div>

{{-- Tabel per Metode --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-white/5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted">Metrik per Metode Deteksi</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-pandora-muted text-xs uppercase border-b border-white/5">
                    <th class="px-5 py-3 text-left">Metode</th>
                    <th class="px-4 py-3 text-center">Total</th>
                    <th class="px-4 py-3 text-center">TP</th>
                    <th class="px-4 py-3 text-center">FP</th>
                    <th class="px-4 py-3 text-center" title="Pengecualian Kebijakan — model benar, kebijakan izinkan">Pengec.</th>
                    <th class="px-4 py-3 text-center">Belum</th>
                    <th class="px-4 py-3 text-center">Review %</th>
                    <th class="px-4 py-3 text-center">Precision</th>
                    <th class="px-4 py-3 text-center">Recall (est.)</th>
                    <th class="px-4 py-3 text-center">F1-Score</th>
                    <th class="px-4 py-3 text-center">Avg Conf.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perMetode as $m)
                <tr class="border-b border-white/5 hover:bg-pandora-dark/30">
                    <td class="px-5 py-3 text-pandora-text font-medium">{{ $metodeLabels[$m->metode_deteksi] ?? $m->metode_deteksi }}</td>
                    <td class="px-4 py-3 text-center text-pandora-muted">{{ number_format($m->total) }}</td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=valid&metode={{ $m->metode_deteksi }}" class="text-pandora-success hover:underline">{{ $m->tp }}</a></td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=false_positive&metode={{ $m->metode_deteksi }}" class="text-pandora-danger hover:underline">{{ $m->fp }}</a></td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=policy_exception&metode={{ $m->metode_deteksi }}" class="text-pandora-accent hover:underline">{{ number_format($m->policy_exception) }}</a></td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=belum_direview&metode={{ $m->metode_deteksi }}" class="text-pandora-muted hover:underline hover:text-pandora-text">{{ number_format($m->belum_direview) }}</a></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center gap-2 justify-center">
                            <div class="w-16 h-1.5 bg-pandora-dark rounded-full overflow-hidden">
                                <div class="h-full bg-pandora-accent rounded-full" style="width: {{ min(100, $m->review_pct) }}%"></div>
                            </div>
                            <span class="text-xs text-pandora-muted">{{ number_format($m->review_pct, 0) }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-mono font-medium {{ $m->precision !== null && $m->precision >= 0.5 ? 'text-pandora-success' : 'text-pandora-gold' }}">{{ $fmt($m->precision) }}</td>
                    <td class="px-4 py-3 text-center font-mono text-pandora-muted">{{ $fmt($m->recall) }}</td>
                    <td class="px-4 py-3 text-center font-mono font-medium text-pandora-accent">{{ $fmt($m->f1) }}</td>
                    <td class="px-4 py-3 text-center font-mono text-pandora-muted">{{ $m->avg_confidence ? number_format($m->avg_confidence * 100, 1) . '%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Tabel per Tingkat --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-white/5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted">Metrik per Tingkat Anomali</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-pandora-muted text-xs uppercase border-b border-white/5">
                    <th class="px-5 py-3 text-left">Tingkat</th>
                    <th class="px-4 py-3 text-center">Total</th>
                    <th class="px-4 py-3 text-center">TP</th>
                    <th class="px-4 py-3 text-center">FP</th>
                    <th class="px-4 py-3 text-center" title="Pengecualian Kebijakan — model benar, kebijakan izinkan">Pengec.</th>
                    <th class="px-4 py-3 text-center">Belum</th>
                    <th class="px-4 py-3 text-center">Review %</th>
                    <th class="px-4 py-3 text-center">Precision</th>
                    <th class="px-4 py-3 text-center">Recall (est.)</th>
                    <th class="px-4 py-3 text-center">F1-Score</th>
                    <th class="px-4 py-3 text-center">Avg Conf.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perTingkat as $t)
                <tr class="border-b border-white/5 hover:bg-pandora-dark/30">
                    <td class="px-5 py-3 text-pandora-text font-medium">{{ $tingkatLabels[$t->tingkat] ?? 'Tingkat ' . $t->tingkat }}</td>
                    <td class="px-4 py-3 text-center text-pandora-muted">{{ number_format($t->total) }}</td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=valid&tingkat={{ $t->tingkat }}" class="text-pandora-success hover:underline">{{ $t->tp }}</a></td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=false_positive&tingkat={{ $t->tingkat }}" class="text-pandora-danger hover:underline">{{ $t->fp }}</a></td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=policy_exception&tingkat={{ $t->tingkat }}" class="text-pandora-accent hover:underline">{{ number_format($t->policy_exception) }}</a></td>
                    <td class="px-4 py-3 text-center"><a href="/analitik/anomali?status=belum_direview&tingkat={{ $t->tingkat }}" class="text-pandora-muted hover:underline hover:text-pandora-text">{{ number_format($t->belum_direview) }}</a></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center gap-2 justify-center">
                            <div class="w-16 h-1.5 bg-pandora-dark rounded-full overflow-hidden">
                                <div class="h-full bg-pandora-accent rounded-full" style="width: {{ min(100, $t->review_pct) }}%"></div>
                            </div>
                            <span class="text-xs text-pandora-muted">{{ number_format($t->review_pct, 0) }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-mono font-medium {{ $t->precision !== null && $t->precision >= 0.5 ? 'text-pandora-success' : 'text-pandora-gold' }}">{{ $fmt($t->precision) }}</td>
                    <td class="px-4 py-3 text-center font-mono text-pandora-muted">{{ $fmt($t->recall) }}</td>
                    <td class="px-4 py-3 text-center font-mono font-medium text-pandora-accent">{{ $fmt($t->f1) }}</td>
                    <td class="px-4 py-3 text-center font-mono text-pandora-muted">{{ $t->avg_confidence ? number_format($t->avg_confidence * 100, 1) . '%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Ground Truth Sample --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-white/5">
        <h3 class="text-xs uppercase tracking-wider text-pandora-muted">Ground Truth — Anomali yang Sudah Direview</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-pandora-muted text-xs uppercase border-b border-white/5">
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">NIP</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-center">Metode</th>
                    <th class="px-4 py-3 text-center">Jenis</th>
                    <th class="px-4 py-3 text-center">T</th>
                    <th class="px-4 py-3 text-center">Conf.</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-left">Reviewer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groundTruth as $gt)
                <tr class="border-b border-white/5 hover:bg-pandora-dark/30">
                    <td class="px-4 py-2.5 text-pandora-muted text-xs">{{ $gt->tanggal }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs text-pandora-muted">{{ $gt->nip }}</td>
                    <td class="px-4 py-2.5 text-pandora-text">
                        <a href="{{ route('analitik.anomali.detail', $gt->id) }}" class="hover:text-pandora-accent transition-colors">{{ \Illuminate\Support\Str::limit($gt->nama, 25) }}</a>
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-pandora-surface-light text-pandora-muted">{{ str_replace('_', ' ', $gt->metode_deteksi) }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-pandora-surface-light text-pandora-muted">{{ str_replace('_', ' ', $gt->jenis_anomali) }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-center text-xs font-bold text-pandora-muted">{{ $gt->tingkat }}</td>
                    <td class="px-4 py-2.5 text-center font-mono text-xs {{ $gt->confidence >= 0.7 ? 'text-pandora-danger' : 'text-pandora-muted' }}">{{ round($gt->confidence * 100) }}%</td>
                    <td class="px-4 py-2.5 text-center">
                        @if($gt->status_review === 'valid')
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-danger/15 text-pandora-danger">Valid</span>
                        @elseif($gt->status_review === 'policy_exception')
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-accent/15 text-pandora-accent" title="Model benar, ada kebijakan yang izinkan">Pengecualian</span>
                        @elseif($gt->status_review === 'false_positive_resolved_by_status_update')
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-success/15 text-pandora-success" title="Auto-resolved oleh koreksi data SIKARA">FP-Auto</span>
                        @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-success/15 text-pandora-success">False Positive</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs text-pandora-muted">{{ $gt->reviewer ?? 'System' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-6 text-center text-pandora-muted">Belum ada anomali yang direview</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($groundTruth->hasPages())
    <div class="px-5 py-3 border-t border-white/5">
        {{ $groundTruth->links() }}
    </div>
    @endif
</div>

{{-- Metodologi --}}
<div class="bg-pandora-surface rounded-xl border border-white/5 p-5 mb-5">
    <h3 class="text-xs uppercase tracking-wider text-pandora-muted mb-3 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Catatan Metodologi
    </h3>
    <div class="text-sm text-pandora-muted leading-relaxed space-y-2">
        <p>Evaluasi menggunakan <strong class="text-pandora-text">semi-supervised validation</strong>: subset anomali yang telah direview oleh operator/validator BKD dijadikan ground truth. Precision dihitung dari proporsi anomali valid terhadap total yang sudah direview.</p>
        <p>Recall merupakan <strong class="text-pandora-text">estimasi</strong> karena tidak semua anomali dapat dilabeli manual. Dihitung sebagai TP / total anomali per metode — anomali yang belum direview dianggap sebagai positif yang belum terverifikasi. F1-Score dihitung dari harmonic mean Precision dan Recall estimasi.</p>
        <p class="text-pandora-muted/60 text-xs">Status <em>false_positive_resolved_by_status_update</em> (auto-resolved saat data SIKARA terkoreksi retroaktif, mis. DSP terlambat input) dihitung sebagai false positive — koreksi data tetap menandakan deteksi yang tidak relevan.</p>
        <p class="text-pandora-muted/60 text-xs">Status <em>policy_exception</em> (model benar mendeteksi outlier, namun ada kebijakan yang mengizinkan — WFA, dinas luar, dispensasi resmi) <strong>tidak</strong> dihitung sebagai FP karena bukan kegagalan model. Direkam terpisah untuk audit kebijakan.</p>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colors = {
        accent: '#00b4d8',
        success: '#2ecc71',
        danger: '#ff4757',
        gold: '#f0a500',
        muted: '#8899aa',
        surface: '#1a2332',
    };

    // Bar Chart — compact, fixed height via container h-40
    const metodeLabels = {!! json_encode($perMetode->pluck('metode_deteksi')->map(fn($m) => $metodeLabels[$m] ?? $m)->values()) !!};
    const precisionData = {!! json_encode($perMetode->pluck('precision')->map(fn($v) => $v !== null ? round($v * 100, 1) : 0)->values()) !!};
    const f1Data = {!! json_encode($perMetode->pluck('f1')->map(fn($v) => $v !== null ? round($v * 100, 1) : 0)->values()) !!};

    new Chart(document.getElementById('chartMetode'), {
        type: 'bar',
        data: {
            labels: metodeLabels,
            datasets: [
                { label: 'Precision (%)', data: precisionData, backgroundColor: colors.success + '99', borderColor: colors.success, borderWidth: 1 },
                { label: 'F1-Score (%)', data: f1Data, backgroundColor: colors.accent + '99', borderColor: colors.accent, borderWidth: 1 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: colors.muted, font: { size: 10 }, boxWidth: 12 } } },
            scales: {
                x: { ticks: { color: colors.muted, font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { beginAtZero: true, max: 100, ticks: { color: colors.muted, font: { size: 10 }, callback: v => v + '%' }, grid: { color: 'rgba(255,255,255,0.05)' } },
            }
        }
    });

    // Mini doughnut — no legend, fits in stat card
    new Chart(document.getElementById('chartPie'), {
        type: 'doughnut',
        data: {
            labels: ['Valid', 'FP', 'Pengecualian', 'Belum'],
            datasets: [{
                data: [{{ $overall->tp }}, {{ $overall->fp }}, {{ $overall->policy_exception }}, {{ $overall->belum_direview }}],
                backgroundColor: [colors.danger + 'cc', colors.success + 'cc', colors.accent + 'cc', colors.surface],
                borderColor: [colors.danger, colors.success, colors.accent, colors.muted + '33'],
                borderWidth: 1,
            }]
        },
        options: {
            responsive: false,
            cutout: '60%',
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
        }
    });
});
</script>
@endsection
