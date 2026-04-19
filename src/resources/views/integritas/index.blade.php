@extends('layouts.app')

@section('title', 'Integritas Ledger')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-2">Integritas Ledger</h1>
    <p class="text-pandora-muted">Bukti anchoring harian ke Bitcoin via OpenTimestamps. File <code class="text-pandora-accent">.ots</code> dapat diverifikasi secara independen di <a href="https://opentimestamps.org" target="_blank" rel="noopener" class="text-pandora-accent hover:underline">opentimestamps.org</a>.</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-success">{{ $anchors->total() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Anchor</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ $anchors->getCollection()->where('status', 'confirmed')->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Confirmed (Bitcoin)</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-gold">{{ $anchors->getCollection()->where('status', 'anchored')->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Menunggu Konfirmasi</p>
    </div>
</div>

<!-- Tabel -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Merkle Root</th>
                    <th class="px-4 py-3 text-center">Record</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">BTC Block</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($anchors as $anchor)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-text font-medium">{{ \Carbon\Carbon::parse($anchor->tanggal)->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-pandora-muted">
                            @if($anchor->merkle_root)
                                {{ substr(bin2hex($anchor->merkle_root), 0, 16) }}&hellip;
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-pandora-text">{{ number_format($anchor->jumlah_record) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($anchor->status === 'confirmed')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-pandora-success/20 text-pandora-success">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Confirmed
                                </span>
                            @elseif($anchor->status === 'anchored')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-pandora-gold/20 text-pandora-gold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-pandora-gold animate-pulse"></span>
                                    Anchored
                                </span>
                            @elseif($anchor->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-pandora-muted/20 text-pandora-muted">
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-pandora-danger/20 text-pandora-danger">
                                    Failed
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-pandora-muted">
                            @if($anchor->btc_block_height)
                                #{{ number_format($anchor->btc_block_height) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($anchor->ots_proof_complete || $anchor->ots_proof_incomplete)
                                    <a href="{{ route('integritas.download', $anchor->tanggal) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-pandora-accent/10 text-pandora-accent hover:bg-pandora-accent/20 transition-colors"
                                       title="Download .ots">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        .ots
                                    </a>
                                @endif
                                <a href="{{ route('integritas.verify', $anchor->tanggal) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-pandora-surface-light text-pandora-muted hover:text-pandora-text transition-colors"
                                   title="Verifikasi">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                    Cek
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-pandora-muted">
                            Belum ada data anchoring. Data akan muncul setelah schedule <code class="text-pandora-accent">ledger:anchor-daily</code> berjalan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($anchors->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $anchors->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Info -->
<div class="mt-5 bg-pandora-surface rounded-xl border border-white/5 p-4">
    <h3 class="text-sm font-semibold text-pandora-text mb-2">Cara Verifikasi Manual</h3>
    <ol class="text-xs text-pandora-muted space-y-1 list-decimal list-inside">
        <li>Download file <code class="text-pandora-accent">.ots</code> dari tabel di atas</li>
        <li>Buka <a href="https://opentimestamps.org" target="_blank" rel="noopener" class="text-pandora-accent hover:underline">opentimestamps.org</a></li>
        <li>Upload file <code class="text-pandora-accent">.ots</code> ke halaman tersebut</li>
        <li>Website akan menampilkan konfirmasi bahwa data ter-anchor di Bitcoin block tertentu</li>
    </ol>
</div>
@endsection
