@extends('layouts.app')

@section('title', 'Master Data Instansi')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-white mb-2">Master Data Instansi</h1>
    <p class="text-pandora-muted">Daftar instansi Pemerintah Provinsi Kalimantan Utara yang terhubung dengan sistem presensi.</p>
</div>

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-pandora-accent/10 text-pandora-accent text-sm font-medium">
        <span class="w-2 h-2 rounded-full bg-pandora-accent animate-pulse"></span>
        Dalam Pengembangan
    </span>
    <button disabled class="px-4 py-2 rounded-lg bg-pandora-primary text-pandora-muted cursor-not-allowed opacity-50 text-sm" title="Segera hadir">
        Sinkron dari SIMPEG
    </button>
</div>

<div class="bg-pandora-surface rounded-xl border border-white/5 overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-white/5">
                <th class="px-4 py-3 text-pandora-muted font-medium">No</th>
                <th class="px-4 py-3 text-pandora-muted font-medium">Nama Instansi</th>
                <th class="px-4 py-3 text-pandora-muted font-medium">Tipe</th>
                <th class="px-4 py-3 text-pandora-muted font-medium">Jumlah Pegawai</th>
                <th class="px-4 py-3 text-pandora-muted font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-pandora-muted">
                    Data akan tersedia setelah sinkronisasi pertama dengan SIMPEG
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
