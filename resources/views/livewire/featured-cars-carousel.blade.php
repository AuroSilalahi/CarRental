<div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-md">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <span class="text-xs font-black tracking-wider uppercase text-blue-600 bg-blue-50 px-3 py-1 rounded-full">Pilihan Favorit</span>
            <h2 class="text-2xl font-black text-slate-900 mt-1">
                🚗 Armada Pilihan Terbaik
            </h2>
            <p class="text-sm text-slate-500">
                Pilih kendaraan siap pakai untuk kebutuhan perjalanan Anda di Sumatera Utara.
            </p>
        </div>
        @if($cars->count() > 1)
            <div class="flex items-center gap-2">
                <button wire:click="prev" class="w-10 h-10 rounded-full border border-slate-200 bg-slate-50 hover:bg-blue-600 hover:text-white hover:border-blue-600 text-slate-700 font-bold flex items-center justify-center transition-all shadow-xs active:scale-90">
                    ❮
                </button>
                <button wire:click="next" class="w-10 h-10 rounded-full border border-slate-200 bg-slate-50 hover:bg-blue-600 hover:text-white hover:border-blue-600 text-slate-700 font-bold flex items-center justify-center transition-all shadow-xs active:scale-90">
                    ❯
                </button>
            </div>
        @endif
    </div>

    @if($cars->isEmpty())
        <div class="text-center py-12 bg-slate-50 rounded-2xl text-slate-500 font-medium">
            Belum ada kendaraan unggulan yang tersedia saat ini.
        </div>
    @else
        @php
            $currentCar = $cars[$currentIndex] ?? $cars->first();
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center bg-gradient-to-br from-slate-50 to-blue-50/50 rounded-2xl p-6 border border-slate-100">
            {{-- Left Image --}}
            <div class="h-64 sm:h-72 bg-slate-200 rounded-xl overflow-hidden relative flex items-center justify-center group shadow-inner">
                @if($currentCar->image_path)
                    <img src="{{ asset('storage/' . $currentCar->image_path) }}" alt="{{ $currentCar->brand }} {{ $currentCar->model }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                    <span class="text-6xl text-slate-400">🚘</span>
                @endif

                @if($currentCar->is_luxury_brand)
                    <span class="absolute top-3 left-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-xs font-black px-3.5 py-1.5 rounded-full shadow-lg flex items-center gap-1">
                        ✨ Luxury Brand
                    </span>
                @endif

                <span class="absolute top-3 right-3 bg-emerald-500 text-white text-xs font-black px-3.5 py-1.5 rounded-full shadow-lg">
                    ✓ Tersedia
                </span>
            </div>

            {{-- Right Info --}}
            <div class="flex flex-col justify-center">
                <span class="inline-block text-xs font-black tracking-wider uppercase text-blue-700 bg-blue-100 px-3 py-1 rounded-md w-max mb-2">
                    {{ $currentCar->type }}
                </span>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-3">
                    {{ $currentCar->brand }} {{ $currentCar->model }}
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs font-bold text-slate-600 mb-6 bg-white p-3.5 rounded-xl border border-slate-200/60 shadow-2xs">
                    <div class="flex items-center gap-1.5">👥 {{ $currentCar->passenger_capacity }} Orang</div>
                    <div class="flex items-center gap-1.5">🎨 {{ $currentCar->colour }}</div>
                    <div class="flex items-center gap-1.5">📅 {{ $currentCar->year }}</div>
                </div>

                <div class="flex items-baseline gap-2 mb-6">
                    <span class="text-2xl sm:text-3xl font-black text-blue-600">
                        Rp {{ number_format($currentCar->daily_rate_idr, 0, ',', '.') }}
                    </span>
                    <span class="text-sm font-semibold text-slate-500">/ hari</span>
                </div>

                <a href="/cars/{{ $currentCar->id }}" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-6 py-3.5 rounded-xl text-sm shadow-md shadow-blue-500/20 hover:shadow-lg transition-all active:scale-95">
                    Lihat Detail & Pesan Sekarang →
                </a>
            </div>
        </div>
    @endif
</div>

