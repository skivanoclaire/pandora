@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-pandora-muted mb-2">
        <a href="{{ route('pengaturan.index') }}" class="hover:text-pandora-accent transition-colors">Pengaturan</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-pandora-text">Audit Trail</span>
    </div>
    <h1 class="text-2xl font-bold text-white">Audit Trail</h1>
    <p class="text-pandora-muted text-sm mt-1">Riwayat seluruh aktivitas pengguna dalam sistem PANDORA.</p>
</div>

<!-- Filter -->
<div class="bg-pandora-surface rounded-xl p-4 md:p-5 border border-white/5 mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-pandora-muted mb-1">Aksi</label>
            <select name="aksi" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="">Semua</option>
                @foreach($aksiList as $a)
                    <option value="{{ $a }}" {{ request('aksi') == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-pandora-muted mb-1">User</label>
            <select name="user" class="bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                <option value="">Semua</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Filter</button>
        @if(request('aksi') || request('user'))
            <a href="{{ route('pengaturan.audit-trail') }}" class="px-4 py-2 bg-pandora-surface-light text-pandora-muted text-sm rounded-lg hover:text-pandora-accent transition-colors">Reset</a>
        @endif
    </form>
</div>

<!-- Tabel -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Waktu</th>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                    <th class="px-4 py-3 text-left">Entitas</th>
                    <th class="px-4 py-3 text-left">IP</th>
                    <th class="px-4 py-3 text-left">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($logs as $log)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3 text-pandora-muted text-xs whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-pandora-text text-sm">{{ $log->user_name }}</p>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $log->user_role === 'admin' ? 'bg-pandora-accent/20 text-pandora-accent' : 'bg-pandora-muted/20 text-pandora-muted' }}">{{ $log->user_role }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-pandora-primary-light/30 text-pandora-muted">{{ $log->aksi }}</span>
                        </td>
                        <td class="px-4 py-3 text-pandora-text text-xs">
                            @if($log->entitas ?? null)
                                {{ $log->entitas }}
                                @if($log->entitas_id ?? null)
                                    <span class="text-pandora-muted">#{{ $log->entitas_id }}</span>
                                @endif
                            @else
                                <span class="text-pandora-muted">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-pandora-muted text-xs font-mono">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-4 py-3" x-data="{ show: false }">
                            @if($log->metadata ?? null)
                                <button @click="show = !show" class="px-2 py-1 rounded text-xs bg-pandora-surface-light text-pandora-muted hover:text-pandora-accent hover:bg-pandora-accent/10 transition-colors">
                                    <span x-text="show ? 'Tutup' : 'Lihat'"></span>
                                </button>
                                <div x-show="show" x-transition class="mt-2 p-2 bg-pandora-dark rounded text-[10px] text-pandora-muted font-mono max-w-xs overflow-x-auto whitespace-pre-wrap">{{ is_string($log->metadata) ? $log->metadata : json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
                            @else
                                <span class="text-pandora-muted text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-pandora-muted">Tidak ada data audit trail</td></tr>
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
