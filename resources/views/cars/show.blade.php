<x-layouts.app :title="$car->brand . ' ' . $car->model . ' — CarRental'">
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-xs font-bold text-slate-400 mb-3">
            <a href="/" class="hover:text-blue-600 transition-colors">Beranda</a>
            <span>/</span>
            <a href="/cars" class="hover:text-blue-600 transition-colors">Katalog Kendaraan</a>
            <span>/</span>
            <span class="text-slate-700">{{ $car->brand }} {{ $car->model }}</span>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- Left: Car Info & Image --}}
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-md overflow-hidden">
            
            {{-- Car Image --}}
            <div class="bg-slate-100 aspect-video relative overflow-hidden flex items-center justify-center">
                @if($car->image_path)
                    <img src="{{ $car->image_url }}" alt="{{ $car->brand }} {{ $car->model }}" class="w-full h-full object-cover">
                @else
                    <span class="text-6xl text-slate-300">🚗</span>
                @endif

                @if($car->is_luxury_brand)
                    <span class="absolute top-4 left-4 bg-gradient-to-r from-amber-500 to-amber-600 text-white px-3.5 py-1.5 rounded-full text-xs font-black shadow-md flex items-center gap-1">
                        ✨ Luxury Brand
                    </span>
                @endif

                @if($car->is_available)
                    <span class="absolute top-4 right-4 bg-emerald-500 text-white px-3.5 py-1.5 rounded-full text-xs font-extrabold shadow-md">
                        ✓ Tersedia
                    </span>
                @else
                    <span class="absolute top-4 right-4 bg-rose-500 text-white px-3.5 py-1.5 rounded-full text-xs font-extrabold shadow-md">
                        ✕ Tidak Tersedia
                    </span>
                @endif
            </div>

            {{-- Car Details --}}
            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-xs font-black uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-md">
                        {{ $car->type }}
                    </span>
                </div>

                <h1 class="text-3xl font-black text-slate-900 mb-3">
                    {{ $car->brand }} {{ $car->model }}
                </h1>

                <div class="flex items-baseline gap-2 mb-6 pb-6 border-b border-slate-100">
                    <span class="text-3xl font-black text-blue-600">
                        Rp {{ number_format($car->daily_rate_idr, 0, ',', '.') }}
                    </span>
                    <span class="text-sm font-semibold text-slate-400">/ hari</span>
                </div>

                {{-- Specs Table --}}
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Plat Nomor</span>
                        <span class="font-bold text-slate-900 font-mono tracking-wider">{{ $car->license_plate }}</span>
                    </div>
                    <div class="flex justify-between py-2.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Kapasitas Penumpang</span>
                        <span class="font-bold text-slate-900">{{ $car->passenger_capacity }} Orang</span>
                    </div>
                    <div class="flex justify-between py-2.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Warna Unit</span>
                        <span class="font-bold text-slate-900">{{ $car->colour }}</span>
                    </div>
                    <div class="flex justify-between py-2.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Tahun Rilis</span>
                        <span class="font-bold text-slate-900">{{ $car->year }}</span>
                    </div>
                    @if($car->is_luxury_brand)
                        <div class="flex justify-between py-2.5 border-b border-slate-100">
                            <span class="text-slate-500 font-medium">Luxury Multiplier</span>
                            <span class="font-bold text-amber-600">{{ $car->luxury_multiplier }}×</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right: Booking Form --}}
        <div class="lg:col-span-5">
            <livewire:booking-form :car="$car" />
        </div>

    </div>
</x-layouts.app>

