<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Car Rental - Layanan Rental Mobil Terpercaya' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; background: #f8fafc; color: #0f172a; font-size: 16px; line-height: 1.5; min-height: 100vh; display: flex; flex-direction: column; }
            .navbar { background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; height: 70px; position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
            .navbar-brand { color: #2563eb; text-decoration: none; font-weight: 900; font-size: 1.35rem; display: flex; align-items: center; gap: 0.5rem; }
            .navbar-links { display: flex; align-items: center; gap: 1.5rem; }
            .navbar-links a { color: #475569; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.2s; }
            .navbar-links a:hover { color: #2563eb; }
            .container { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem; width: 100%; }
            main { flex: 1; }
            footer { background: #0f172a; color: #94a3b8; padding: 2.5rem 1.5rem; margin-top: auto; font-size: 0.875rem; text-align: center; }
        </style>
    @endif

    @livewireStyles
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">
            🚗 <span>CarRental</span>
        </a>
        <div class="navbar-links">
            <a href="/">Beranda</a>
            <a href="/cars">Katalog Kendaraan</a>
            <a href="/admin" target="_blank" style="color:#0284c7; background:#e0f2fe; padding:0.3rem 0.7rem; border-radius:0.375rem;">🛡️ Admin Panel</a>
            @auth
                <a href="/my-rentals">Pemesanan Saya</a>
                <a href="/profile">Profil & KTP</a>
                <span style="font-size:0.85rem; background:#eff6ff; color:#1e40af; font-weight:700; padding:0.35rem 0.75rem; border-radius:999px;">
                    👤 {{ auth()->user()->name }}
                </span>
                <a href="/logout" style="color:#ef4444;">Keluar</a>
            @else
                <a href="/login" style="color:#2563eb;">Masuk</a>
                <a href="/register" style="background:#2563eb; color:#fff; padding:0.45rem 1.1rem; border-radius:0.375rem; transition:background 0.2s;">
                    Daftar
                </a>
            @endauth
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    <footer>
        <div style="max-width:1200px; margin:0 auto; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
            <div>
                <strong style="color:#f8fafc; font-size:1rem;">CarRental Application</strong> — Layanan Rental Mobil Terpercaya di Sumatera Utara
            </div>
            <div>
                &copy; {{ date('Y') }} CarRental Inc. Hak cipta dilindungi.
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
