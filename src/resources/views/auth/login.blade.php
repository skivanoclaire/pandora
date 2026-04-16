<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - PANDORA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .grid-pattern {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .radial-glow {
            background:
                radial-gradient(ellipse 600px 400px at 50% 0%, rgba(var(--color-pandora-accent-raw, 99, 102, 241), 0.12) 0%, transparent 70%),
                radial-gradient(ellipse 400px 300px at 80% 50%, rgba(var(--color-pandora-primary-raw, 168, 85, 247), 0.08) 0%, transparent 70%);
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .top-line-shimmer {
            position: relative;
            overflow: hidden;
        }

        .top-line-shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        .btn-shimmer {
            position: relative;
            overflow: hidden;
        }

        .btn-shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-pandora-dark text-pandora-text min-h-screen antialiased">
    <div class="grid-pattern radial-glow min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">

            {{-- Brand Section --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-pandora-accent to-pandora-primary mb-4 shadow-lg shadow-pandora-accent/20">
                    <span class="text-2xl font-extrabold text-white">P</span>
                </div>
                <h1 class="text-2xl font-bold tracking-wide text-pandora-text">PANDORA</h1>
                <p class="text-sm text-pandora-muted mt-1">Portal Analitik Data Kehadiran ASN</p>
            </div>

            {{-- Login Card --}}
            <div class="bg-pandora-surface rounded-2xl border border-white/5 shadow-xl overflow-hidden">

                {{-- Top gradient line with shimmer --}}
                <div class="h-1 bg-gradient-to-r from-pandora-accent to-pandora-primary-light top-line-shimmer"></div>

                <div class="p-8">
                    <h2 class="text-xl font-semibold text-pandora-text">Selamat Datang</h2>
                    <p class="text-sm text-pandora-muted mt-1 mb-6">Masuk dengan akun Anda untuk mengakses portal analitik.</p>

                    {{-- Error Messages --}}
                    @if($errors->any())
                        <div class="mb-4 p-3 rounded-lg bg-pandora-danger/10 border border-pandora-danger/30">
                            <ul class="text-sm text-pandora-danger space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Status Message --}}
                    @if(session('status'))
                        <div class="mb-4 p-3 rounded-lg bg-pandora-success/10 border border-pandora-success/30">
                            <p class="text-sm text-pandora-success">{{ session('status') }}</p>
                        </div>
                    @endif

                    {{-- Login Form --}}
                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        {{-- NIP Field --}}
                        <div>
                            <label for="nip" class="block text-sm font-medium text-pandora-text mb-1.5">NIP / Username</label>
                            <input
                                type="text"
                                id="nip"
                                name="nip"
                                value="{{ old('nip') }}"
                                placeholder="Masukkan NIP atau username"
                                required
                                autofocus
                                class="w-full px-4 py-2.5 rounded-lg bg-pandora-dark border border-white/10 text-pandora-text placeholder-pandora-muted/50 focus:border-pandora-accent focus:ring-1 focus:ring-pandora-accent/50 outline-none transition-colors duration-200 text-sm"
                            >
                        </div>

                        {{-- Password Field --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-pandora-text mb-1.5">Password</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                required
                                class="w-full px-4 py-2.5 rounded-lg bg-pandora-dark border border-white/10 text-pandora-text placeholder-pandora-muted/50 focus:border-pandora-accent focus:ring-1 focus:ring-pandora-accent/50 outline-none transition-colors duration-200 text-sm"
                            >
                        </div>

                        {{-- Remember Me --}}
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="w-4 h-4 rounded border-white/10 bg-pandora-dark text-pandora-accent focus:ring-pandora-accent/50 focus:ring-offset-0"
                            >
                            <label for="remember" class="ml-2 text-sm text-pandora-muted cursor-pointer">Ingat saya</label>
                        </div>

                        {{-- Submit Button --}}
                        <button
                            type="submit"
                            class="btn-shimmer w-full py-2.5 px-4 rounded-lg bg-gradient-to-r from-pandora-primary to-pandora-primary-light text-white font-semibold text-sm hover:brightness-110 active:brightness-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-pandora-primary/50"
                        >
                            Masuk
                        </button>
                    </form>

                    {{-- Back Link --}}
                    <div class="mt-6 text-center">
                        <a href="/" class="text-sm text-pandora-muted hover:text-pandora-accent transition-colors duration-200">
                            Kembali ke halaman utama
                        </a>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <p class="text-center text-xs text-pandora-muted mt-8">
                DKISP Prov. Kalimantan Utara &copy; {{ date('Y') }}
            </p>

        </div>
    </div>
</body>
</html>
