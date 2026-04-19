@extends('layouts.app')

@section('title', 'Whitelist Pegawai')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-2 gap-3 mb-5">
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-accent">{{ $whitelists->total() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Total Whitelist</p>
    </div>
    <div class="bg-pandora-surface rounded-lg p-3 border border-white/5 text-center">
        <p class="text-xl font-bold text-pandora-gold">{{ $whitelists->where('berlaku_sampai', '>=', now())->count() + $whitelists->whereNull('berlaku_sampai')->count() }}</p>
        <p class="text-xs text-pandora-muted mt-1">Aktif (halaman ini)</p>
    </div>
</div>

<!-- Tabel -->
<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-hidden" x-data="{ showAdd: false, search: '', filtered: [] }" x-init="filtered = {{ Js::from($pegawaiList) }}">
    <div class="px-5 py-3 border-b border-white/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-pandora-text">Daftar Whitelist Pegawai</h2>
        <button @click="showAdd = true" class="px-3 py-1.5 bg-pandora-accent text-white text-xs rounded-lg hover:bg-pandora-accent-light transition-colors">+ Tambah Whitelist</button>
    </div>

    <!-- Modal Tambah Whitelist -->
    <div x-show="showAdd" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showAdd = false" style="display: none;">
        <div class="bg-pandora-surface rounded-xl border border-white/10 p-6 w-full max-w-lg">
            <h3 class="text-lg font-semibold text-pandora-text mb-4">Tambah Whitelist Pegawai</h3>
            <form method="POST" action="{{ route('master.whitelist.store') }}" class="space-y-3">
                @csrf
                <div x-data="{ open: false, search: '', selected: '', selectedName: '' }">
                    <label class="block text-xs text-pandora-muted mb-1">Pegawai (NIP / Nama)</label>
                    <input type="hidden" name="id_pegawai" :value="selected">
                    <div class="relative">
                        <input type="text" x-model="search" @focus="open = true" @click.away="open = false"
                               :placeholder="selectedName || 'Ketik NIP atau nama...'"
                               class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                        <div x-show="open && search.length >= 2" class="absolute z-10 mt-1 w-full bg-pandora-dark border border-white/10 rounded-lg max-h-48 overflow-y-auto shadow-xl">
                            <template x-for="p in filtered.filter(p => (p.nama && p.nama.toLowerCase().includes(search.toLowerCase())) || (p.nip && p.nip.includes(search))).slice(0, 20)" :key="p.id_pegawai">
                                <button type="button" @click="selected = p.id_pegawai; selectedName = p.nip + ' - ' + p.nama; search = ''; open = false"
                                        class="w-full text-left px-3 py-2 text-sm text-pandora-text hover:bg-pandora-surface-light transition-colors">
                                    <span class="font-mono text-xs text-pandora-muted" x-text="p.nip"></span>
                                    <span class="ml-2" x-text="p.nama"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <p x-show="selectedName" class="text-xs text-pandora-accent mt-1" x-text="selectedName"></p>
                </div>
                <div>
                    <label class="block text-xs text-pandora-muted mb-1">Jenis Whitelist</label>
                    <select name="jenis_whitelist" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="bebas_lokasi">Bebas Lokasi</option>
                        <option value="dispensasi_khusus">Dispensasi Khusus</option>
                        <option value="tugas_lapangan">Tugas Lapangan</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-pandora-muted mb-1">Alasan</label>
                    <textarea name="alasan" required rows="2" class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-pandora-muted mb-1">Berlaku Mulai</label>
                        <input type="date" name="berlaku_mulai" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-pandora-muted mb-1">Berlaku Sampai (opsional)</label>
                        <input type="date" name="berlaku_sampai" class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Simpan</button>
                    <button type="button" @click="showAdd = false" class="px-4 py-2 bg-pandora-dark text-pandora-muted text-sm rounded-lg hover:bg-pandora-surface-light transition-colors">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">Pegawai</th>
                    <th class="px-4 py-3 text-left">Instansi</th>
                    <th class="px-4 py-3 text-center">Jenis</th>
                    <th class="px-4 py-3 text-left">Alasan</th>
                    <th class="px-4 py-3 text-center">Berlaku</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($whitelists as $w)
                    <tr class="hover:bg-pandora-dark/30 transition-colors">
                        <td class="px-4 py-3">
                            <div class="text-pandora-text">{{ $w->nama }}</div>
                            <div class="text-pandora-muted font-mono text-xs">{{ $w->nip }}</div>
                        </td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($w->nama_unit ?? '-', 30) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $jenisColor = match($w->jenis_whitelist) {
                                    'bebas_lokasi' => 'pandora-gold',
                                    'dispensasi_khusus' => 'pandora-accent',
                                    'tugas_lapangan' => 'pandora-success',
                                    default => 'pandora-muted',
                                };
                                $jenisLabel = str_replace('_', ' ', $w->jenis_whitelist);
                            @endphp
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $jenisColor }}/20 text-{{ $jenisColor }} capitalize">{{ $jenisLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-pandora-muted text-xs">{{ \Illuminate\Support\Str::limit($w->alasan, 40) }}</td>
                        <td class="px-4 py-3 text-center text-xs text-pandora-muted">
                            {{ \Carbon\Carbon::parse($w->berlaku_mulai)->format('d/m/Y') }}
                            @if($w->berlaku_sampai)
                                <br>s/d {{ \Carbon\Carbon::parse($w->berlaku_sampai)->format('d/m/Y') }}
                            @else
                                <br><span class="text-pandora-gold">Permanen</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('master.whitelist.destroy', $w->id) }}" onsubmit="return confirm('Hapus whitelist ini?')">
                                @csrf @method('DELETE')
                                <button class="text-pandora-danger/60 hover:text-pandora-danger text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-pandora-muted">Belum ada data whitelist</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($whitelists->hasPages())
        <div class="px-4 py-3 border-t border-white/5">
            {{ $whitelists->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
