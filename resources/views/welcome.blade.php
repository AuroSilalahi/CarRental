<x-layouts.app title="Car Rental — Layanan Rental Mobil Terpercaya di Sumatera Utara">
    
    {{-- Hero Section --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-blue-950 to-indigo-900 text-white p-8 sm:p-12 lg:p-16 mb-12 shadow-2xl border border-slate-800">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl mx-auto text-center">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-extrabold tracking-wide uppercase bg-blue-500/20 text-blue-300 border border-blue-400/30 mb-6 backdrop-blur-sm">
                ✨ Rental Mobil Sumatera Utara Terlengkap
            </span>
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight mb-6 bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-100 to-blue-200">
                Sewa Mobil Impian Anda dengan Cepat & Transparan
            </h1>
            <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto mb-8 font-medium leading-relaxed">
                Nikmati perjalanan aman dan nyaman bersama armada mobil terawat, harga terjangkau tanpa biaya tersembunyi, dan layanan responsif 24/7.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="/cars" class="px-8 py-4 rounded-xl font-extrabold text-slate-900 bg-white hover:bg-slate-100 shadow-xl shadow-white/10 hover:scale-105 active:scale-95 transition-all duration-200 flex items-center gap-2">
                    <span>🚗</span> Jelajahi Kendaraan
                </a>
                @guest
                    <a href="/register" class="px-8 py-4 rounded-xl font-bold text-white bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 hover:scale-105 active:scale-95 transition-all duration-200">
                        Daftar Sekarang
                    </a>
                @endguest
            </div>
        </div>
    </div>

    {{-- Featured Cars Carousel --}}
    <div class="mb-12">
        <livewire:featured-cars-carousel />
    </div>

    {{-- Key Benefits Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        
        <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl mb-5 shadow-xs">
                🛡️
            </div>
            <h3 class="text-lg font-extrabold text-slate-900 mb-2">Kondisi Terawat & Aman</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
                Seluruh unit armada rutin diservis secara berkala. Dijamin bersih, dingin, dan siap untuk perjalanan jarak jauh.
            </p>
        </div>

        <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl mb-5 shadow-xs">
                ⚡
            </div>
            <h3 class="text-lg font-extrabold text-slate-900 mb-2">Pemesanan Instant</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
                Pesan langsung secara online, cek estimasi harga real-time, dan selesaikan pembayaran dengan sistem yang praktis.
            </p>
        </div>

        <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-3xl mb-5 shadow-xs">
                💎
            </div>
            <h3 class="text-lg font-extrabold text-slate-900 mb-2">Pilihan Brand Mewah</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
                Tersedia pilihan mobil luxury brand dengan multiplier transparan untuk kebutuhan wedding, eksekutif, & VIP.
            </p>
        </div>

    </div>

</x-layouts.app>

