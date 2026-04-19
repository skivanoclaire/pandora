@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4" x-data="{ showAdd: false, editId: null }">
    <div>
        <h1 class="text-2xl font-bold text-white mb-1">Manajemen Pengguna</h1>
        <p class="text-pandora-muted text-sm">Kelola akun pengguna sistem PANDORA.</p>
    </div>
    <button @click="showAdd = true" class="px-4 py-2 bg-pandora-accent text-white text-sm font-medium rounded-lg hover:bg-pandora-accent-light transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Tambah User
    </button>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="w-full" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="px-4 py-3 rounded-lg bg-pandora-success/10 border border-pandora-success/20 text-pandora-success text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="w-full">
            <div class="px-4 py-3 rounded-lg bg-pandora-danger/10 border border-pandora-danger/20 text-pandora-danger text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Add User Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showAdd = false">
        <div class="fixed inset-0 bg-black/60" @click="showAdd = false"></div>
        <div class="relative bg-pandora-surface rounded-xl border border-white/10 w-full max-w-md p-6 shadow-xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h2 class="text-lg font-semibold text-white mb-4">Tambah User Baru</h2>
            <form method="POST" action="{{ route('pengaturan.users.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-pandora-muted mb-1">Nama Lengkap <span class="text-pandora-danger">*</span></label>
                    <input type="text" name="name" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-pandora-muted mb-1">NIP</label>
                    <input type="text" name="nip" maxlength="18" class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-pandora-muted mb-1">Email <span class="text-pandora-danger">*</span></label>
                    <input type="email" name="email" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-pandora-muted mb-1">Password <span class="text-pandora-danger">*</span></label>
                    <input type="password" name="password" required minlength="8" class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-pandora-muted mb-1">Role <span class="text-pandora-danger">*</span></label>
                    <select name="role" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-2 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                        <option value="hr">HR</option>
                        <option value="pimpinan">Pimpinan</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAdd = false" class="px-4 py-2 text-sm text-pandora-muted hover:text-pandora-text transition-colors">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-pandora-accent text-white text-sm font-medium rounded-lg hover:bg-pandora-accent-light transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="w-full bg-pandora-surface rounded-xl border border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-pandora-dark/50 text-pandora-muted text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">NIP</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-center">Role</th>
                        <th class="px-4 py-3 text-left">Dibuat</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $user)
                        <tr class="hover:bg-pandora-dark/30 transition-colors" x-data="{ editing: false }">
                            {{-- Display mode --}}
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-pandora-text font-medium">{{ $user->name }}</td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-pandora-muted font-mono text-xs">{{ $user->nip ?: '-' }}</td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-pandora-muted text-xs">{{ $user->email }}</td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-center">
                                    @php $r = $user->role; @endphp
                                    <span class="px-2 py-0.5 rounded text-xs font-medium capitalize
                                        {{ $r === 'admin' ? 'bg-pandora-danger/20 text-pandora-danger' : '' }}
                                        {{ $r === 'hr' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                        {{ $r === 'pimpinan' ? 'bg-pandora-gold/20 text-pandora-gold' : '' }}
                                    ">{{ $r }}</span>
                                </td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-pandora-muted text-xs">{{ $user->created_at?->format('d M Y') ?? '-' }}</td>
                            </template>
                            <template x-if="!editing">
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="editing = true" class="p-1.5 rounded-lg text-pandora-muted hover:text-pandora-accent hover:bg-pandora-accent/10 transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('pengaturan.users.destroy', $user) }}" onsubmit="return confirm('Yakin ingin menghapus user {{ $user->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded-lg text-pandora-muted hover:text-pandora-danger hover:bg-pandora-danger/10 transition-colors" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </template>

                            {{-- Edit mode --}}
                            <template x-if="editing">
                                <td colspan="6" class="px-4 py-3">
                                    <form method="POST" action="{{ route('pengaturan.users.update', $user) }}" class="flex flex-wrap items-end gap-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex-1 min-w-[140px]">
                                            <label class="block text-xs text-pandora-muted mb-1">Nama</label>
                                            <input type="text" name="name" value="{{ $user->name }}" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-1.5 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                                        </div>
                                        <div class="w-[140px]">
                                            <label class="block text-xs text-pandora-muted mb-1">NIP</label>
                                            <input type="text" name="nip" value="{{ $user->nip }}" maxlength="18" class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-1.5 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                                        </div>
                                        <div class="flex-1 min-w-[160px]">
                                            <label class="block text-xs text-pandora-muted mb-1">Email</label>
                                            <input type="email" name="email" value="{{ $user->email }}" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-1.5 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                                        </div>
                                        <div class="w-[140px]">
                                            <label class="block text-xs text-pandora-muted mb-1">Password <span class="text-pandora-muted text-[10px]">(kosongkan jika tidak diubah)</span></label>
                                            <input type="password" name="password" minlength="8" class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-1.5 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                                        </div>
                                        <div class="w-[120px]">
                                            <label class="block text-xs text-pandora-muted mb-1">Role</label>
                                            <select name="role" required class="w-full bg-pandora-dark border border-white/10 rounded-lg px-3 py-1.5 text-sm text-pandora-text focus:border-pandora-accent focus:outline-none">
                                                <option value="hr" {{ $user->role === 'hr' ? 'selected' : '' }}>HR</option>
                                                <option value="pimpinan" {{ $user->role === 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                                                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="submit" class="px-3 py-1.5 bg-pandora-accent text-white text-sm rounded-lg hover:bg-pandora-accent-light transition-colors">Simpan</button>
                                            <button type="button" @click="editing = false" class="px-3 py-1.5 text-sm text-pandora-muted hover:text-pandora-text transition-colors">Batal</button>
                                        </div>
                                    </form>
                                </td>
                            </template>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-pandora-muted">Tidak ada data pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-white/5">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
