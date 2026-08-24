<x-layouts.app title="Car Rental - Layanan Rental Mobil Terpercaya di Sumatera Utara">
    {{-- Hero Section --}}
    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color:#ffffff; padding:4rem 1.5rem; border-radius:1rem; margin-bottom:3rem; text-align:center; box-shadow:0 20px 25px -5px rgba(37,99,235,0.25);">
        <h1 style="font-size:2.5rem; font-weight:900; margin-bottom:1rem; letter-spacing:-0.025em;">
            Sewa Mobil Impian Anda dengan Mudah & Cepat
        </h1>
        <p style="font-size:1.125rem; color:#bfdbfe; max-width:650px; margin:0 auto 2rem auto; line-height:1.6;">
            Nikmati perjalanan aman dan nyaman di Sumatera Utara dengan armada mobil terawat, harga kompetitif, dan pelayanan profesional 24/7.
        </p>
        <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap;">
            <a href="/cars" style="background:#ffffff; color:#1e3a8a; font-weight:800; padding:0.85rem 2rem; border-radius:0.5rem; text-decoration:none; font-size:1rem; transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.03)';" onmouseout="this.style.transform='scale(1)';">
                🚗 Jelajahi Kendaraan
            </a>
            @guest
                <a href="/register" style="background:rgba(255,255,255,0.15); color:#ffffff; font-weight:700; padding:0.85rem 2rem; border-radius:0.5rem; text-decoration:none; font-size:1rem; backdrop-filter:blur(5px); border:1px solid rgba(255,255,255,0.3);">
                    Daftar Sekarang
                </a>
            @endguest
        </div>
    </div>

    <div class="container">
        {{-- Task 7.1: Featured Cars Carousel Livewire Component --}}
        <livewire:featured-cars-carousel />

        {{-- Features Grid --}}
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem; margin-bottom:3rem;">
            <div style="background:#fff; padding:1.75rem; border-radius:0.75rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); border:1px solid #f1f5f9;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">🛡️</div>
                <h3 style="font-size:1.125rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">Kondisi Terawat & Aman</h3>
                <p style="color:#64748b; font-size:0.9rem; line-height:1.5;">Seluruh kendaraan diperiksa secara berkala dan siap tempuh jarak jauh dengan jaminan keselamatan.</p>
            </div>
            <div style="background:#fff; padding:1.75rem; border-radius:0.75rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); border:1px solid #f1f5f9;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">⚡</div>
                <h3 style="font-size:1.125rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">Pemesanan Instant</h3>
                <p style="color:#64748b; font-size:0.9rem; line-height:1.5;">Proses sewa langsung online dengan estimasi harga transparan tanpa biaya tersembunyi.</p>
            </div>
            <div style="background:#fff; padding:1.75rem; border-radius:0.75rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); border:1px solid #f1f5f9;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">💎</div>
                <h3 style="font-size:1.125rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">Pilihan Brand Mewah</h3>
                <p style="color:#64748b; font-size:0.9rem; line-height:1.5;">Tersedia pilihan mobil luxury brand untuk kebutuhan acara bisnis, pernikahan, atau tamu VIP.</p>
            </div>
        </div>
    </div>
</x-layouts.app>
