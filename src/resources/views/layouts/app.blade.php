<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - PANDORA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="font-['Inter'] bg-pandora-dark text-pandora-text antialiased"
    x-data="{
        sidebarOpen: false,
        openSubmenu: {
            masterData: {{ request()->is('master/*') ? 'true' : 'false' }},
            kehadiran: {{ request()->is('kehadiran/*') ? 'true' : 'false' }},
            analitik: {{ request()->is('analitik/*') ? 'true' : 'false' }}
        }
    }"
>

    {{-- Mobile Overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 w-[260px] bg-pandora-surface border-r border-white/5 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0"
    >
        {{-- Sidebar Header --}}
        <div class="flex items-center justify-between h-16 px-5 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-pandora-accent to-pandora-primary flex items-center justify-center font-bold text-white">
                    P
                </div>
                <span class="font-bold tracking-wider text-pandora-text">PANDORA</span>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-pandora-muted hover:text-pandora-text">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

            {{-- Dashboard --}}
            <a href="/dashboard"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->is('dashboard*') ? 'bg-pandora-accent/10 border-l-2 border-pandora-accent text-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            {{-- Master Data --}}
            <div>
                <button @click="openSubmenu.masterData = !openSubmenu.masterData"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                               {{ request()->is('master/*') ? 'bg-pandora-accent/10 border-l-2 border-pandora-accent text-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zm0 5h16"/>
                        </svg>
                        <span>Master Data</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="openSubmenu.masterData ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="openSubmenu.masterData"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-1 space-y-1">
                    <a href="/master/instansi"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('master/instansi*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Instansi
                    </a>
                    <a href="/master/pegawai"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('master/pegawai*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Pegawai
                    </a>
                    <a href="/master/geofence"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('master/geofence*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Zona Geofence
                    </a>
                </div>
            </div>

            {{-- Kehadiran --}}
            <div>
                <button @click="openSubmenu.kehadiran = !openSubmenu.kehadiran"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                               {{ request()->is('kehadiran/*') ? 'bg-pandora-accent/10 border-l-2 border-pandora-accent text-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Kehadiran</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="openSubmenu.kehadiran ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="openSubmenu.kehadiran"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-1 space-y-1">
                    <a href="/kehadiran/rekap"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('kehadiran/rekap*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Rekap Harian
                    </a>
                    <a href="/kehadiran/log"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('kehadiran/log*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Log Presensi
                    </a>
                </div>
            </div>

            {{-- Analitik --}}
            <div>
                <button @click="openSubmenu.analitik = !openSubmenu.analitik"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                               {{ request()->is('analitik/*') ? 'bg-pandora-accent/10 border-l-2 border-pandora-accent text-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Analitik</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="openSubmenu.analitik ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="openSubmenu.analitik"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-1 space-y-1">
                    <a href="/analitik/tren"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('analitik/tren*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Tren Kehadiran
                    </a>
                    <a href="/analitik/anomali"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('analitik/anomali*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Anomali
                    </a>
                    <a href="/analitik/clustering"
                       class="block pl-12 pr-3 py-2 rounded-lg text-sm transition-colors
                              {{ request()->is('analitik/clustering*') ? 'text-pandora-accent bg-pandora-accent/10 border-l-2 border-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                        Clustering
                    </a>
                </div>
            </div>

            {{-- Sinkronisasi --}}
            <a href="/sinkronisasi"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->is('sinkronisasi*') ? 'bg-pandora-accent/10 border-l-2 border-pandora-accent text-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Sinkronisasi</span>
            </a>

            {{-- Pengaturan (admin only) --}}
            @if(auth()->user()->role === 'admin')
            <a href="/pengaturan"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->is('pengaturan*') ? 'bg-pandora-accent/10 border-l-2 border-pandora-accent text-pandora-accent' : 'text-pandora-muted hover:text-pandora-text hover:bg-pandora-surface-light' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Pengaturan</span>
            </a>
            @endif

        </nav>

        {{-- Sidebar Footer --}}
        <div class="flex-shrink-0 px-5 py-4 border-t border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-pandora-surface-light flex items-center justify-center text-sm font-semibold text-pandora-text">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-pandora-text truncate">{{ Auth::user()->name }}</p>
                    @php $role = auth()->user()->role; @endphp
                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-xs font-medium capitalize
                        {{ $role === 'admin' ? 'bg-pandora-accent/20 text-pandora-accent' : '' }}
                        {{ $role === 'hr' ? 'bg-pandora-gold/20 text-pandora-gold' : '' }}
                        {{ $role === 'pimpinan' ? 'bg-pandora-success/20 text-pandora-success' : '' }}
                    ">{{ $role }}</span>
                </div>
            </div>
        </div>
    </aside>

    {{-- Topbar --}}
    <header class="fixed top-0 right-0 left-0 lg:left-[260px] z-30 h-16 bg-pandora-surface/80 backdrop-blur border-b border-white/5 flex items-center justify-between px-4 md:px-6">
        {{-- Left side --}}
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true" class="lg:hidden text-pandora-muted hover:text-pandora-text">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-pandora-text">@yield('title', 'Dashboard')</h1>
        </div>

        {{-- Right side --}}
        <div class="flex items-center gap-4">
            <div class="hidden sm:flex items-center gap-2">
                <span class="text-sm text-pandora-muted">{{ Auth::user()->name }}</span>
                @php $topbarRole = auth()->user()->role; @endphp
                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium capitalize
                    {{ $topbarRole === 'admin' ? 'bg-pandora-accent/20 text-pandora-accent' : '' }}
                    {{ $topbarRole === 'hr' ? 'bg-pandora-gold/20 text-pandora-gold' : '' }}
                    {{ $topbarRole === 'pimpinan' ? 'bg-pandora-success/20 text-pandora-success' : '' }}
                ">{{ $topbarRole }}</span>
            </div>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-pandora-muted hover:text-pandora-danger hover:bg-pandora-danger/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </form>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="lg:ml-[260px] pt-16 min-h-screen bg-pandora-dark">
        <div class="p-4 md:p-6 lg:p-8">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
