<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PANDORA - Portal Analitik Data Kehadiran ASN Pemerintah Provinsi Kalimantan Utara">
    <title>PANDORA - Portal Analitik Data Kehadiran ASN</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Grid pattern background */
        .grid-pattern {
            background-image:
                linear-gradient(rgba(0, 180, 216, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 180, 216, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 60px 60px; }
        }

        /* Floating particles */
        .particle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 180, 216, 0.4), transparent);
            pointer-events: none;
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(0) translateX(0) scale(1);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) translateX(40px) scale(0.5);
                opacity: 0;
            }
        }

        /* Shimmer effect on buttons */
        .shimmer {
            position: relative;
            overflow: hidden;
        }

        .shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            animation: shimmerSlide 3s ease-in-out infinite;
        }

        @keyframes shimmerSlide {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #00b4d8, #00d4ff, #f0a500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Scroll reveal */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        /* Pulse dot */
        .pulse-dot {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.5); }
        }

        /* Timeline line */
        .timeline-line {
            background: linear-gradient(to bottom, #00b4d8, #1e3a5f, transparent);
        }

        /* Card hover accent line */
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00b4d8, transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        /* Radial glow */
        .hero-glow {
            background: radial-gradient(ellipse 60% 50% at 50% 40%, rgba(0, 180, 216, 0.08) 0%, rgba(30, 58, 95, 0.04) 50%, transparent 80%);
        }

        /* Counter animation */
        .counter-value {
            font-variant-numeric: tabular-nums;
        }

        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0a1628;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e3a5f;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #2a5298;
        }
    </style>
</head>
<body class="bg-pandora-dark text-pandora-text font-sans antialiased overflow-x-hidden">

    {{-- ==================== NAVBAR ==================== --}}
    <nav x-data="{ scrolled: false, mobileOpen: false }"
         x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 })"
         :class="scrolled ? 'bg-pandora-dark/80 backdrop-blur-xl shadow-lg shadow-black/20 border-b border-white/5' : 'bg-transparent'"
         class="fixed top-0 left-0 right-0 z-50 transition-all duration-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-pandora-accent to-pandora-primary flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-pandora-accent/20 group-hover:shadow-pandora-accent/40 transition-shadow">
                        P
                    </div>
                    <span class="text-white font-bold text-lg tracking-[2px]">PANDORA</span>
                </a>

                {{-- Desktop nav --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-pandora-muted hover:text-pandora-accent transition-colors text-sm font-medium">Fitur</a>
                    <a href="#workflow" class="text-pandora-muted hover:text-pandora-accent transition-colors text-sm font-medium">Alur Kerja</a>
                    <a href="#tech" class="text-pandora-muted hover:text-pandora-accent transition-colors text-sm font-medium">Teknologi</a>
                    <a href="/login"
                       class="shimmer inline-flex items-center px-5 py-2.5 rounded-lg bg-gradient-to-r from-pandora-primary-light to-pandora-accent text-white text-sm font-semibold hover:shadow-lg hover:shadow-pandora-accent/25 transition-all duration-300 hover:-translate-y-0.5">
                        Masuk
                    </a>
                </div>

                {{-- Mobile hamburger --}}
                <button @click="mobileOpen = !mobileOpen" class="md:hidden text-pandora-text p-2">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Mobile menu --}}
            <div x-show="mobileOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="md:hidden pb-4 border-t border-white/5 mt-2 pt-4 space-y-3">
                <a href="#features" @click="mobileOpen = false" class="block text-pandora-muted hover:text-pandora-accent transition-colors text-sm font-medium py-2">Fitur</a>
                <a href="#workflow" @click="mobileOpen = false" class="block text-pandora-muted hover:text-pandora-accent transition-colors text-sm font-medium py-2">Alur Kerja</a>
                <a href="#tech" @click="mobileOpen = false" class="block text-pandora-muted hover:text-pandora-accent transition-colors text-sm font-medium py-2">Teknologi</a>
                <a href="/login" class="shimmer inline-flex items-center px-5 py-2.5 rounded-lg bg-gradient-to-r from-pandora-primary-light to-pandora-accent text-white text-sm font-semibold mt-2">Masuk</a>
            </div>
        </div>
    </nav>

    {{-- ==================== HERO ==================== --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
        {{-- Background layers --}}
        <div class="absolute inset-0 grid-pattern"></div>
        <div class="absolute inset-0 hero-glow"></div>

        {{-- Particles container --}}
        <div id="particles" class="absolute inset-0 overflow-hidden"></div>

        {{-- Content --}}
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center pt-24 pb-16">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-pandora-surface/80 border border-pandora-primary/30 backdrop-blur-sm mb-8">
                <span class="w-2 h-2 rounded-full bg-pandora-success pulse-dot"></span>
                <span class="text-pandora-muted text-xs sm:text-sm font-medium">Pemerintah Provinsi Kalimantan Utara</span>
            </div>

            {{-- Title --}}
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold leading-tight mb-6">
                <span class="text-white">Portal Analitik</span>
                <br>
                <span class="gradient-text">Data Kehadiran ASN</span>
            </h1>

            {{-- Subtitle --}}
            <p class="text-pandora-muted text-base sm:text-lg md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
                Pantau, analisis, dan optimalkan kehadiran 6.000+ ASN secara real-time dengan kecerdasan data dan visualisasi interaktif.
            </p>

            {{-- Buttons --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/login"
                   class="shimmer inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-r from-pandora-primary-light to-pandora-accent text-white font-semibold text-base hover:shadow-xl hover:shadow-pandora-accent/20 transition-all duration-300 hover:-translate-y-0.5">
                    <span>Masuk ke Portal</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="#features"
                   class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl border border-pandora-primary-light/50 text-pandora-text font-semibold text-base hover:border-pandora-accent/50 hover:text-pandora-accent transition-all duration-300 hover:-translate-y-0.5">
                    <span>Pelajari Lebih Lanjut</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </a>
            </div>
        </div>

        {{-- Bottom gradient fade --}}
        <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-pandora-dark to-transparent"></div>
    </section>

    {{-- ==================== STATS BAR ==================== --}}
    <section class="relative -mt-16 z-20 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-24">
        <div id="stats-bar" class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 p-6 sm:p-8 rounded-2xl bg-pandora-surface/80 backdrop-blur-xl border border-pandora-primary/20 shadow-2xl shadow-black/20">
            <div class="text-center">
                <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white counter-value" data-target="6000" data-suffix="+">0</div>
                <div class="text-pandora-muted text-xs sm:text-sm mt-1">ASN Terpantau</div>
            </div>
            <div class="text-center">
                <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white counter-value" data-target="139" data-suffix="">0</div>
                <div class="text-pandora-muted text-xs sm:text-sm mt-1">Instansi</div>
            </div>
            <div class="text-center">
                <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white counter-value" data-target="44" data-suffix="">0</div>
                <div class="text-pandora-muted text-xs sm:text-sm mt-1">Perangkat Daerah</div>
            </div>
            <div class="text-center">
                <div class="text-2xl sm:text-3xl lg:text-4xl font-bold text-pandora-accent counter-value" data-target="24" data-suffix="/7">0</div>
                <div class="text-pandora-muted text-xs sm:text-sm mt-1">Monitoring Real-time</div>
            </div>
        </div>
    </section>

    {{-- ==================== FEATURES ==================== --}}
    <section id="features" class="relative py-24 sm:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Section header --}}
            <div class="text-center mb-16 reveal">
                <span class="inline-block px-4 py-1.5 rounded-full bg-pandora-accent/10 text-pandora-accent text-xs font-semibold tracking-wider uppercase mb-4">Fitur Unggulan</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">Analitik Kehadiran yang Komprehensif</h2>
                <p class="text-pandora-muted text-lg max-w-2xl mx-auto">Dilengkapi dengan teknologi terkini untuk memastikan monitoring kehadiran ASN yang akurat dan efisien.</p>
            </div>

            {{-- Cards grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Card 1: Dashboard Interaktif --}}
                <div class="feature-card reveal relative group p-6 sm:p-8 rounded-2xl bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 hover:-translate-y-2 transition-all duration-500" style="transition-delay: 0ms">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-3">Dashboard Interaktif</h3>
                    <p class="text-pandora-muted text-sm leading-relaxed">Visualisasi kehadiran per instansi, unit kerja, dan individu dengan grafik real-time yang mudah dipahami.</p>
                </div>

                {{-- Card 2: Analisis Geofencing --}}
                <div class="feature-card reveal relative group p-6 sm:p-8 rounded-2xl bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 hover:-translate-y-2 transition-all duration-500" style="transition-delay: 100ms">
                    <div class="w-12 h-12 rounded-xl bg-pandora-gold/10 flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-pandora-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-3">Analisis Geofencing</h3>
                    <p class="text-pandora-muted text-sm leading-relaxed">Validasi lokasi absensi dengan peta interaktif, deteksi anomali koordinat, dan monitoring zona yang diizinkan.</p>
                </div>

                {{-- Card 3: Deteksi Anomali --}}
                <div class="feature-card reveal relative group p-6 sm:p-8 rounded-2xl bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 hover:-translate-y-2 transition-all duration-500" style="transition-delay: 200ms">
                    <div class="w-12 h-12 rounded-xl bg-pandora-success/10 flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-pandora-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-3">Deteksi Anomali</h3>
                    <p class="text-pandora-muted text-sm leading-relaxed">Machine learning mendeteksi pola tidak wajar: fake GPS, keterlambatan berulang, dan absensi mencurigakan.</p>
                </div>

                {{-- Card 4: Tren & Prediksi --}}
                <div class="feature-card reveal relative group p-6 sm:p-8 rounded-2xl bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 hover:-translate-y-2 transition-all duration-500" style="transition-delay: 300ms">
                    <div class="w-12 h-12 rounded-xl bg-pandora-danger/10 flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-pandora-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-3">Tren & Prediksi</h3>
                    <p class="text-pandora-muted text-sm leading-relaxed">Analisis time-series untuk memprediksi pola ketidakhadiran dan membantu perencanaan sumber daya manusia.</p>
                </div>

                {{-- Card 5: Clustering Perilaku --}}
                <div class="feature-card reveal relative group p-6 sm:p-8 rounded-2xl bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 hover:-translate-y-2 transition-all duration-500" style="transition-delay: 400ms">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-3">Clustering Perilaku</h3>
                    <p class="text-pandora-muted text-sm leading-relaxed">Kelompokkan pegawai berdasarkan pola kehadiran menggunakan K-Means untuk identifikasi segmen yang memerlukan perhatian.</p>
                </div>

                {{-- Card 6: Laporan Otomatis --}}
                <div class="feature-card reveal relative group p-6 sm:p-8 rounded-2xl bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 hover:-translate-y-2 transition-all duration-500" style="transition-delay: 500ms">
                    <div class="w-12 h-12 rounded-xl bg-teal-500/10 flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-3">Laporan Otomatis</h3>
                    <p class="text-pandora-muted text-sm leading-relaxed">Generate laporan berkala per instansi, rekap bulanan, dan notifikasi otomatis untuk pimpinan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== HOW IT WORKS ==================== --}}
    <section id="workflow" class="relative py-24 sm:py-32">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Section header --}}
            <div class="text-center mb-16 reveal">
                <span class="inline-block px-4 py-1.5 rounded-full bg-pandora-gold/10 text-pandora-gold text-xs font-semibold tracking-wider uppercase mb-4">Alur Kerja</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">Bagaimana PANDORA Bekerja</h2>
                <p class="text-pandora-muted text-lg max-w-2xl mx-auto">Dari data mentah hingga insight yang actionable, dalam empat langkah terintegrasi.</p>
            </div>

            {{-- Timeline --}}
            <div class="relative">
                {{-- Vertical line --}}
                <div class="absolute left-6 sm:left-8 top-0 bottom-0 w-0.5 timeline-line"></div>

                {{-- Step 1 --}}
                <div class="reveal relative flex gap-6 sm:gap-8 mb-12">
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-pandora-accent to-pandora-primary-light flex items-center justify-center text-white font-bold text-lg sm:text-xl shadow-lg shadow-pandora-accent/20">
                        1
                    </div>
                    <div class="pt-1 sm:pt-3">
                        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">Sinkronisasi Data</h3>
                        <p class="text-pandora-muted text-sm sm:text-base leading-relaxed">Data kehadiran dari SIMPEG disinkronkan secara berkala ke database PANDORA melalui koneksi aman dan terenkripsi.</p>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="reveal relative flex gap-6 sm:gap-8 mb-12" style="transition-delay: 150ms">
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-pandora-accent to-pandora-primary-light flex items-center justify-center text-white font-bold text-lg sm:text-xl shadow-lg shadow-pandora-accent/20">
                        2
                    </div>
                    <div class="pt-1 sm:pt-3">
                        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">Pembersihan & Transformasi</h3>
                        <p class="text-pandora-muted text-sm sm:text-base leading-relaxed">Data mentah dibersihkan, divalidasi, dan ditransformasi menggunakan pipeline ETL untuk memastikan kualitas analisis.</p>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="reveal relative flex gap-6 sm:gap-8 mb-12" style="transition-delay: 300ms">
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-pandora-accent to-pandora-primary-light flex items-center justify-center text-white font-bold text-lg sm:text-xl shadow-lg shadow-pandora-accent/20">
                        3
                    </div>
                    <div class="pt-1 sm:pt-3">
                        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">Analisis & Machine Learning</h3>
                        <p class="text-pandora-muted text-sm sm:text-base leading-relaxed">Engine Python/FastAPI menjalankan algoritma clustering, anomaly detection, dan time-series analysis pada data yang telah diolah.</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="reveal relative flex gap-6 sm:gap-8" style="transition-delay: 450ms">
                    <div class="relative z-10 flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-pandora-accent to-pandora-primary-light flex items-center justify-center text-white font-bold text-lg sm:text-xl shadow-lg shadow-pandora-accent/20">
                        4
                    </div>
                    <div class="pt-1 sm:pt-3">
                        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">Visualisasi & Aksi</h3>
                        <p class="text-pandora-muted text-sm sm:text-base leading-relaxed">Hasil analisis ditampilkan dalam dashboard interaktif dengan grafik, peta, dan notifikasi untuk pengambilan keputusan cepat.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== TECH STACK ==================== --}}
    <section id="tech" class="relative py-24 sm:py-32">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Section header --}}
            <div class="text-center mb-12 reveal">
                <span class="inline-block px-4 py-1.5 rounded-full bg-pandora-primary-light/20 text-pandora-accent text-xs font-semibold tracking-wider uppercase mb-4">Teknologi</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">Dibangun dengan Stack Modern</h2>
                <p class="text-pandora-muted text-lg max-w-2xl mx-auto">Teknologi enterprise-grade untuk keandalan dan performa tinggi.</p>
            </div>

            {{-- Tech pills --}}
            <div class="reveal flex flex-wrap justify-center gap-3 sm:gap-4">
                <div class="inline-flex items-center gap-2.5 px-5 py-3 rounded-full bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <span class="text-pandora-text text-sm font-medium">Laravel 13</span>
                </div>
                <div class="inline-flex items-center gap-2.5 px-5 py-3 rounded-full bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-900"></span>
                    <span class="text-pandora-text text-sm font-medium">PostgreSQL 16 + PostGIS</span>
                </div>
                <div class="inline-flex items-center gap-2.5 px-5 py-3 rounded-full bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span class="text-pandora-text text-sm font-medium">Python FastAPI</span>
                </div>
                <div class="inline-flex items-center gap-2.5 px-5 py-3 rounded-full bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <span class="text-pandora-text text-sm font-medium">Redis 7</span>
                </div>
                <div class="inline-flex items-center gap-2.5 px-5 py-3 rounded-full bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span class="text-pandora-text text-sm font-medium">Docker Compose</span>
                </div>
                <div class="inline-flex items-center gap-2.5 px-5 py-3 rounded-full bg-pandora-surface border border-pandora-primary/20 hover:border-pandora-accent/30 transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full bg-pandora-success"></span>
                    <span class="text-pandora-text text-sm font-medium">Nginx + SSL</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== CTA ==================== --}}
    <section class="relative py-24 sm:py-32">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="reveal relative p-8 sm:p-12 lg:p-16 rounded-3xl bg-gradient-to-br from-pandora-surface to-pandora-primary/20 border border-pandora-primary/30 overflow-hidden">
                {{-- Decorative glow --}}
                <div class="absolute top-0 right-0 w-64 h-64 bg-pandora-accent/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-pandora-gold/5 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>

                <div class="relative z-10">
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white mb-4">Siap Meningkatkan Analitik Kehadiran ASN?</h2>
                    <p class="text-pandora-muted text-lg mb-8 max-w-xl mx-auto">Akses dashboard, laporan, dan insight kehadiran ASN Provinsi Kalimantan Utara sekarang.</p>
                    <a href="/login"
                       class="shimmer inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-pandora-primary-light to-pandora-accent text-white font-semibold text-lg hover:shadow-xl hover:shadow-pandora-accent/25 transition-all duration-300 hover:-translate-y-0.5">
                        <span>Masuk ke Portal</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FOOTER ==================== --}}
    <footer class="relative border-t border-pandora-primary/20 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center text-center gap-4">
                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-pandora-accent to-pandora-primary flex items-center justify-center text-white font-bold text-lg">
                        P
                    </div>
                    <span class="text-white font-bold text-lg tracking-[2px]">PANDORA</span>
                </div>
                <p class="text-pandora-muted text-sm">Portal Analitik Data Kehadiran ASN</p>
                <div class="text-pandora-muted text-xs space-y-1">
                    <p>Dinas Komunikasi, Informatika, Statistik, dan Persandian</p>
                    <p>Pemerintah Provinsi Kalimantan Utara</p>
                </div>
                <p class="text-pandora-muted/60 text-xs mt-2">&copy; 2026 PANDORA. Hak cipta dilindungi.</p>
            </div>
        </div>
    </footer>

    {{-- ==================== SCRIPTS ==================== --}}
    <script>
        // --- Particles ---
        (function() {
            const container = document.getElementById('particles');
            if (!container) return;
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                const size = Math.random() * 4 + 2;
                const left = Math.random() * 100;
                const duration = Math.random() * 30 + 15;
                const delay = Math.random() * 20;
                particle.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    left: ${left}%;
                    bottom: -10px;
                    animation-duration: ${duration}s;
                    animation-delay: ${delay}s;
                `;
                container.appendChild(particle);
            }
        })();

        // --- Counter Animation ---
        (function() {
            const statsBar = document.getElementById('stats-bar');
            if (!statsBar) return;
            let counted = false;

            function animateCounters() {
                if (counted) return;
                counted = true;
                const counters = statsBar.querySelectorAll('.counter-value');
                counters.forEach(function(counter) {
                    const target = parseInt(counter.getAttribute('data-target'), 10);
                    const suffix = counter.getAttribute('data-suffix') || '';
                    const duration = 2000;
                    const startTime = performance.now();

                    function update(currentTime) {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const current = Math.floor(eased * target);

                        if (target >= 1000) {
                            counter.textContent = current.toLocaleString('id-ID') + suffix;
                        } else {
                            counter.textContent = current + suffix;
                        }

                        if (progress < 1) {
                            requestAnimationFrame(update);
                        } else {
                            if (target >= 1000) {
                                counter.textContent = target.toLocaleString('id-ID') + suffix;
                            } else {
                                counter.textContent = target + suffix;
                            }
                        }
                    }

                    requestAnimationFrame(update);
                });
            }

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        animateCounters();
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.3 });

            observer.observe(statsBar);
        })();

        // --- Scroll Reveal ---
        (function() {
            const reveals = document.querySelectorAll('.reveal');
            if (!reveals.length) return;

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const delay = entry.target.style.transitionDelay;
                        if (delay) {
                            setTimeout(function() {
                                entry.target.classList.add('revealed');
                            }, parseInt(delay));
                        } else {
                            entry.target.classList.add('revealed');
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

            reveals.forEach(function(el) {
                observer.observe(el);
            });
        })();
    </script>
</body>
</html>
