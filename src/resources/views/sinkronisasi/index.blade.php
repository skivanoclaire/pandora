@extends('layouts.app')

@section('title', 'Sinkronisasi Data SIMPEG')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-2">Sinkronisasi Data SIMPEG</h1>
    <p class="text-pandora-muted">Monitoring sinkronisasi data kehadiran dari sistem SIMPEG.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ $latestSyncs->pluck('tabel_sumber')->unique()->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Tabel Sync</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        @php
            $lastSuccess = $latestSyncs->where('status', 'success')->sortByDesc('finished_at')->first();
        @endphp
        <p class="text-lg font-bold text-pandora-success">
            {{ $lastSuccess ? \Carbon\Carbon::parse($lastSuccess->finished_at)->format('d/m H:i') : '-' }}
        </p>
        <p class="text-xs text-pandora-muted mt-1">Sync Sukses Terakhir</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold {{ $changeStats['total'] > 0 ? 'text-yellow-400' : 'text-pandora-success' }}">{{ number_format($changeStats['total']) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Data Change Alerts</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold {{ $changeStats['critical'] > 0 ? 'text-red-400' : 'text-pandora-success' }}">{{ number_format($changeStats['critical']) }}</p>
        <p class="text-xs text-pandora-muted mt-1">Critical Alerts</p>
    </div>
</div>

<!-- Sync Status Per Table -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <div class="px-4 py-3 border-b border-white/5">
        <h2 class="text-sm font-semibold text-white">Status Sinkronisasi Per Tabel</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Tabel Sumber</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Fetched</th>
                    <th class="px-4 py-3 text-center">Inserted</th>
                    <th class="px-4 py-3 text-center">Updated</th>
                    <th class="px-4 py-3 text-left">Started At</th>
                    <th class="px-4 py-3 text-center">Duration</th>
                    <th class="px-4 py-3 text-left">Error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($latestSyncs as $sync)
                    <tr class="hover:bg-pandora-dark/30 transition-colors
                        {{ $sync->status === 'failed' ? 'bg-red-900/10' : ($sync->status === 'running' ? 'bg-yellow-900/10' : '') }}">
                        <td class="px-4 py-3 text-pandora-text font-mono text-xs">{{ $sync->tabel_sumber }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($sync->status === 'success')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-500/20 text-green-400">success</span>
                            @elseif($sync->status === 'failed')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-500/20 text-red-400">failed</span>
                            @elseif($sync->status === 'running')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-500/20 text-yellow-400 animate-pulse">running</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-500/20 text-gray-400">{{ $sync->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ number_format($sync->rows_fetched ?? 0) }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ number_format($sync->rows_inserted ?? 0) }}</td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ number_format($sync->rows_updated ?? 0) }}</td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">
                            {{ $sync->started_at ? \Carbon\Carbon::parse($sync->started_at)->format('d/m/Y H:i:s') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-pandora-muted text-xs">
                            @if($sync->started_at && $sync->finished_at)
                                @php
                                    $start = \Carbon\Carbon::parse($sync->started_at);
                                    $end = \Carbon\Carbon::parse($sync->finished_at);
                                    $diff = $start->diff($end);
                                @endphp
                                {{ $diff->i > 0 ? $diff->i . 'm ' : '' }}{{ $diff->s }}s
                            @elseif($sync->status === 'running')
                                <span class="text-yellow-400 animate-pulse">...</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-red-400 max-w-[200px] truncate" title="{{ $sync->error_message }}">
                            {{ $sync->error_message ? \Illuminate\Support\Str::limit($sync->error_message, 50) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-pandora-muted">Belum ada data sinkronisasi</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Data Change Alerts -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden mb-5">
    <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-white">Deteksi Perubahan Data</h2>
        @if($changeStats['total'] > 0)
            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-500/20 text-yellow-400">{{ $changeStats['total'] }} belum ditinjau</span>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Tabel</th>
                    <th class="px-4 py-3 text-left">PK</th>
                    <th class="px-4 py-3 text-center">Severity</th>
                    <th class="px-4 py-3 text-left">Old Checksum</th>
                    <th class="px-4 py-3 text-left">New Checksum</th>
                    <th class="px-4 py-3 text-left">Detected At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($dataChanges as $change)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ $change->tanggal ? \Carbon\Carbon::parse($change->tanggal)->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-3 text-pandora-text font-mono text-xs">{{ $change->tabel_sumber }}</td>
                        <td class="px-4 py-3 text-pandora-text text-xs">{{ $change->pk_value }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($change->severity === 'critical')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-500/20 text-red-400">critical</span>
                            @elseif($change->severity === 'warning')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-500/20 text-yellow-400">warning</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-500/20 text-blue-400">info</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-pandora-muted font-mono text-[10px]">{{ \Illuminate\Support\Str::limit($change->old_checksum, 16) }}</td>
                        <td class="px-4 py-3 text-pandora-muted font-mono text-[10px]">{{ \Illuminate\Support\Str::limit($change->new_checksum, 16) }}</td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Carbon\Carbon::parse($change->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-pandora-muted">Tidak ada perubahan data yang mencurigakan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Sync History (Collapsible) -->
<div x-data="{ showHistory: false }" class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-white">Riwayat Sinkronisasi Terakhir</h2>
        <button @click="showHistory = !showHistory"
                class="px-3 py-1 text-xs rounded-lg bg-pandora-dark border border-white/10 text-pandora-muted hover:text-white transition-colors">
            <span x-text="showHistory ? 'Sembunyikan' : 'Tampilkan ({{ $recentSyncs->count() }} entri)'"></span>
        </button>
    </div>
    <div x-show="showHistory" x-collapse>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                        <th class="px-4 py-2 text-left">Tabel</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-center">Rows</th>
                        <th class="px-4 py-2 text-left">Started</th>
                        <th class="px-4 py-2 text-center">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($recentSyncs as $sync)
                        <tr class="hover:bg-pandora-dark/30 transition-colors">
                            <td class="px-4 py-2 text-pandora-text font-mono text-xs">{{ $sync->tabel_sumber }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($sync->status === 'success')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-500/20 text-green-400">success</span>
                                @elseif($sync->status === 'failed')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-500/20 text-red-400">failed</span>
                                @elseif($sync->status === 'running')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-500/20 text-yellow-400 animate-pulse">running</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-500/20 text-gray-400">{{ $sync->status }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center text-pandora-muted text-xs">
                                {{ number_format($sync->rows_fetched ?? 0) }} / {{ number_format($sync->rows_inserted ?? 0) }} / {{ number_format($sync->rows_updated ?? 0) }}
                            </td>
                            <td class="px-4 py-2 text-pandora-muted text-xs">
                                {{ $sync->started_at ? \Carbon\Carbon::parse($sync->started_at)->format('d/m H:i:s') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-center text-pandora-muted text-xs">
                                @if($sync->started_at && $sync->finished_at)
                                    @php
                                        $start = \Carbon\Carbon::parse($sync->started_at);
                                        $end = \Carbon\Carbon::parse($sync->finished_at);
                                        $diff = $start->diff($end);
                                    @endphp
                                    {{ $diff->i > 0 ? $diff->i . 'm ' : '' }}{{ $diff->s }}s
                                @elseif($sync->status === 'running')
                                    <span class="text-yellow-400 animate-pulse">...</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
