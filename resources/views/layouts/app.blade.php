<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $title ?? 'CarRental — Layanan Rental Mobil Terpercaya')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN Fallback -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        .glass-header {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
    </style>

    @livewireStyles
</head>
<body class="font-sans bg-slate-50 text-slate-800 min-h-screen flex flex-col antialiased selection:bg-blue-600 selection:text-white">
    
    {{-- Navigation Bar --}}
    <header class="sticky top-0 z-50 glass-header border-b border-slate-200/80 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                {{-- Brand Logo --}}
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform duration-200">
                        <span class="text-xl">🚗</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-extrabold text-xl tracking-tight text-slate-900 group-hover:text-blue-600 transition-colors">
                            Car<span class="text-blue-600">Rental</span>
                        </span>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 -mt-1">Sumatera Utara</span>
                    </div>
                </a>

                {{-- Navigation Links --}}
                <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                    <a href="/" class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/80 transition-all">
                        Beranda
                    </a>
                    <a href="/cars" class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/80 transition-all">
                        Katalog Kendaraan
                    </a>
                </nav>

                {{-- Auth / User Area --}}
                <div class="flex items-center gap-3">
                    @auth
                        <a href="/my-rentals" class="hidden sm:inline-flex px-3.5 py-2 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 border border-slate-200 transition-all">
                            📋 Pemesanan Saya
                        </a>
                        <a href="/profile" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200/70 hover:bg-blue-100 transition-all">
                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-black">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden md:inline">{{ auth()->user()->name }}</span>
                        </a>
                        <a href="/logout" class="px-3 py-2 text-xs font-bold text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all">
                            Keluar
                        </a>
                    @else
                        <a href="/login" class="px-4 py-2 text-sm font-bold text-slate-700 hover:text-blue-600 transition-all">
                            Masuk
                        </a>
                        <a href="/register" class="px-5 py-2.5 rounded-xl text-sm font-extrabold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all transform active:scale-95 inline-flex items-center justify-center" style="background-color: #2563eb; color: #ffffff !important;">
                            Daftar Sekarang
                        </a>
                    @endauth
                </div>

            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-400 border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-2xl">🚗</span>
                        <span class="font-black text-xl text-white tracking-tight">CarRental</span>
                    </div>
                    <p class="text-sm text-slate-400 max-w-md leading-relaxed">
                        Layanan sewa mobil terpercaya di Sumatera Utara. Menyediakan berbagai kelas armada mulai dari kendaraan keluarga hingga mobil kemewahan untuk kebutuhan bisnis & liburan Anda.
                    </p>
                </div>

                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-200 mb-4">Navigasi</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="/" class="hover:text-white transition-colors">Beranda</a></li>
                        <li><a href="/cars" class="hover:text-white transition-colors">Katalog Mobil</a></li>
                        <li><a href="/my-rentals" class="hover:text-white transition-colors">Riwayat Sewa</a></li>
                        <li><a href="/profile" class="hover:text-white transition-colors">Verifikasi KTP</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-200 mb-4">Layanan & Layanan</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex items-center gap-2"><span>⚡</span> Pemesanan Instan Online</li>
                        <li class="flex items-center gap-2"><span>🛡️</span> Jaminan Unit Terawat</li>
                        <li class="flex items-center gap-2"><span>✨</span> Paket Luxury Multiplier</li>
                        <li class="flex items-center gap-2"><span>📞</span> Bantuan 24 Jam</li>
                    </ul>
                </div>

            </div>

            <div class="pt-8 border-t border-slate-800/80 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs">
                <p>&copy; {{ date('Y') }} CarRental Application. All rights reserved.</p>
                <p class="text-slate-500">Dikembangkan untuk kenyamanan perjalanan di Sumatera Utara.</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

