<x-layouts.app title="Katalog Kendaraan — CarRental">
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-xs font-bold text-slate-400 mb-3">
            <a href="/" class="hover:text-blue-600 transition-colors">Beranda</a>
            <span>/</span>
            <span class="text-slate-700">Katalog Kendaraan</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            Armada Kendaraan Tersedia
        </h1>
        <p class="text-slate-500 text-sm mt-1">Temukan kendaraan terbaik untuk perjalanan Anda di Sumatera Utara.</p>
    </div>

    <livewire:car-listing />
</x-layouts.app>

