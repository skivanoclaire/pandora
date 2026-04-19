@extends('layouts.app')

@section('title', 'Tren Kehadiran')

@section('content')
<!-- Filter -->
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Periode</label>
            <select name="days" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                @foreach([7 => '7 hari', 14 => '14 hari', 30 => '30 hari', 60 => '60 hari', 90 => '90 hari', 180 => '6 bulan', 365 => '1 tahun'] as $d => $label)
                    <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Tampilkan</button>
    </form>
</div>

<!-- Chart Area -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-5 mb-5">
    <!-- Line Chart -->
    <div class="lg:col-span-2 bg-pandora-surface rounded-xl p-5 border border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text mb-4">Persentase Kehadiran</h2>
        <div class="h-64 md:h-80">
            <canvas id="trenLineChart"></canvas>
        </div>
    </div>

    <!-- Stacked Bar -->
    <div class="bg-pandora-surface rounded-xl p-5 border border-white/5">
        <h2 class="text-sm font-semibold text-pandora-text mb-4">Komposisi Harian</h2>
        <div class="h-64 md:h-80">
            <canvas id="trenBarChart"></canvas>
        </div>
    </div>
</div>

<!-- Tabel Detail -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-center">Total</th>
                    <th class="px-4 py-3 text-center">Hadir</th>
                    <th class="px-4 py-3 text-center">Terlambat</th>
                    <th class="px-4 py-3 text-center">Tidak Hadir</th>
                    <th class="px-4 py-3 text-center">DL/DD</th>
                    <th class="px-4 py-3 text-center">Cuti</th>
                    <th class="px-4 py-3 text-center">Sakit</th>
                    <th class="px-4 py-3 text-center">Tanpa Ket.</th>
                    <th class="px-4 py-3 text-center">% Hadir</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($tren->reverse() as $t)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-2.5 text-pandora-text">{{ \Carbon\Carbon::parse($t['tanggal'])->translatedFormat('d M Y (l)') }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-muted">{{ $t['total'] }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-success">{{ $t['hadir'] }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-gold">{{ $t['terlambat'] }}</td>
                        <td class="px-4 py-2.5 text-center text-pandora-danger">{{ $t['tidak_hadir'] }}</td>
                        <td class="px-4 py-2.5 text-center text-xs">
                            @if($t['dinas_luar'] > 0)
                                <a href="{{ route('analitik.tren.dinas', $t['tanggal']) }}" class="text-pandora-accent hover:underline">{{ $t['dinas_luar'] }}</a>
                            @else
                                <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs">
                            @if($t['cuti'] > 0)
                                <a href="{{ route('analitik.tren.ijin', [$t['tanggal'], 'cuti']) }}" class="text-pandora-muted hover:text-pandora-accent hover:underline">{{ $t['cuti'] }}</a>
                            @else
                                <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs">
                            @if($t['sakit'] > 0)
                                <a href="{{ route('analitik.tren.ijin', [$t['tanggal'], 'sakit']) }}" class="text-pandora-muted hover:text-pandora-accent hover:underline">{{ $t['sakit'] }}</a>
                            @else
                                <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center text-xs">
                            @if($t['tanpa_keterangan'] > 0)
                                <a href="{{ route('analitik.tren.tanpa-keterangan', $t['tanggal']) }}" class="text-pandora-danger font-medium hover:underline">{{ $t['tanpa_keterangan'] }}</a>
                            @else
                                <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $t['persen_hadir'] >= 80 ? 'bg-pandora-success/20 text-pandora-success' : ($t['persen_hadir'] >= 60 ? 'bg-pandora-gold/20 text-pandora-gold' : 'bg-pandora-danger/20 text-pandora-danger') }}">{{ $t['persen_hadir'] }}%</span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <a href="{{ route('analitik.tren.detail', $t['tanggal']) }}"
                               class="px-2.5 py-1 rounded text-xs bg-pandora-surface-light text-pandora-muted hover:text-pandora-accent hover:bg-pandora-accent/10 transition-colors">
                                Detail
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = @json($tren);

    // Line chart
    new Chart(document.getElementById('trenLineChart'), {
        type: 'line',
        data: {
            labels: data.map(d => d.label),
            datasets: [{
                label: 'Kehadiran (%)',
                data: data.map(d => d.persen_hadir),
                borderColor: '#00b4d8',
                backgroundColor: 'rgba(0,180,216,0.08)',
                borderWidth: 2, fill: true, tension: 0.3,
                pointRadius: data.length > 30 ? 0 : 3,
                pointBackgroundColor: '#00b4d8',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 10 }, maxTicksLimit: 10 } },
                y: { min: 0, max: 100, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', callback: v => v+'%' } }
            }
        }
    });

    // Stacked bar (last 7 visible)
    const barData = data.slice(-7);
    new Chart(document.getElementById('trenBarChart'), {
        type: 'bar',
        data: {
            labels: barData.map(d => d.label),
            datasets: [
                { label: 'Hadir', data: barData.map(d => d.hadir - d.terlambat), backgroundColor: '#00c48c', borderRadius: 2 },
                { label: 'Terlambat', data: barData.map(d => d.terlambat), backgroundColor: '#f0a500', borderRadius: 2 },
                { label: 'Tidak Hadir', data: barData.map(d => d.tidak_hadir), backgroundColor: '#ff4757', borderRadius: 2 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#64748b', boxWidth: 10, font: { size: 10 } } } },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } },
                y: { stacked: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b' } }
            }
        }
    });
});
</script>
@endpush
