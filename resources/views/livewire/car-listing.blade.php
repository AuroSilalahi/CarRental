<div>
    {{-- Search & Filter Section --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-md mb-8">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                    <span>🔎</span> Filter & Cari Kendaraan
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Filter berdasarkan merek, tipe, kapasitas, dan status ketersediaan.</p>
            </div>
            <button wire:click="resetFilters" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-all flex items-center gap-1.5 active:scale-95">
                <span>🔄</span> Reset Filter
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            {{-- Search Keyword --}}
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Cari (Brand / Model)</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Contoh: Toyota, Avanza..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            </div>

            {{-- Type Filter --}}
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Tipe Mobil</label>
                <select wire:model.live="type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                    <option value="">Semua Tipe</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Brand Filter --}}
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Merek (Brand)</label>
                <select wire:model.live="brand" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                    <option value="">Semua Merek</option>
                    @foreach($brands as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Min Capacity Filter --}}
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Min. Kapasitas</label>
                <select wire:model.live="capacity" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                    <option value="">Semua</option>
                    <option value="2">2+ Orang</option>
                    <option value="4">4+ Orang</option>
                    <option value="6">6+ Orang</option>
                    <option value="8">8+ Orang</option>
                </select>
            </div>

            {{-- Availability Filter --}}
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Status Mobil</label>
                <select wire:model.live="availabilityFilter" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white">
                    <option value="">Semua Status</option>
                    <option value="available">Tersedia</option>
                    <option value="unavailable">Tidak Tersedia</option>
                </select>
            </div>
        </div>

        {{-- Date Range Picker --}}
        <div class="bg-blue-50/60 border border-blue-100 rounded-2xl p-4">
            <span class="block text-xs font-extrabold uppercase tracking-wider text-blue-900 mb-3 flex items-center gap-1.5">
                📅 Cek Ketersediaan Berdasarkan Tanggal Rental:
            </span>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Tanggal Mulai</label>
                    <input type="date" wire:model.live="startDate" min="{{ now()->toDateString() }}" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Tanggal Selesai</label>
                    <input type="date" wire:model.live="endDate" min="{{ now()->addDay()->toDateString() }}" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            @error('endDate')
                <p class="text-red-600 text-xs font-bold mt-2">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Cars Grid List --}}
    @if($cars->isEmpty())
        <div class="text-center py-16 px-4 bg-white rounded-3xl border border-slate-200/80 shadow-md">
            <span class="text-5xl block mb-4">🔍</span>
            <h3 class="text-lg font-black text-slate-900 mb-1">Tidak ada kendaraan ditemukan</h3>
            <p class="text-sm text-slate-500">Coba sesuaikan kata kunci pencarian atau reset filter Anda.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($cars as $car)
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col group">
                    
                    {{-- Image & Badges --}}
                    <div class="h-48 bg-slate-100 relative overflow-hidden flex items-center justify-center">
                        @if($car->image_path)
                            <img src="{{ $car->image_url }}" alt="{{ $car->brand }} {{ $car->model }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                            <span class="text-5xl text-slate-300">🚘</span>
                        @endif

                        {{-- Availability Badge --}}
                        @php
                            $effectiveAvailable = isset($car->is_date_available) ? $car->is_date_available : $car->is_currently_available;
                        @endphp

                        @if($effectiveAvailable)
                            <span class="absolute top-3 right-3 bg-emerald-500/90 backdrop-blur-md text-white px-3 py-1 rounded-full text-xs font-extrabold shadow-md">
                                ✓ Tersedia
                            </span>
                        @else
                            <span class="absolute top-3 right-3 bg-rose-500/90 backdrop-blur-md text-white px-3 py-1 rounded-full text-xs font-extrabold shadow-md">
                                ✕ Tidak Tersedia
                            </span>
                        @endif

                        @if($car->is_luxury_brand)
                            <span class="absolute top-3 left-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white px-3 py-1 rounded-full text-xs font-black shadow-md flex items-center gap-1">
                                ✨ Luxury
                            </span>
                        @endif
                    </div>

                    {{-- Card Body --}}
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] font-black uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-md">
                                {{ $car->type }}
                            </span>
                            <h3 class="text-xl font-black text-slate-900 mt-2 mb-3">
                                {{ $car->brand }} {{ $car->model }}
                            </h3>

                            <div class="grid grid-cols-2 gap-2 text-xs font-semibold text-slate-500 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <div class="flex items-center gap-1">👥 {{ $car->passenger_capacity }} Orang</div>
                                <div class="flex items-center gap-1">🎨 {{ $car->colour }}</div>
                                <div class="flex items-center gap-1">📅 {{ $car->year }}</div>
                                <div class="flex items-center gap-1">🪪 {{ $car->license_plate }}</div>
                            </div>
                        </div>

                        {{-- Card Footer --}}
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between mt-auto">
                            <div>
                                <span class="text-lg font-black text-blue-600">
                                    Rp {{ number_format($car->daily_rate_idr, 0, ',', '.') }}
                                </span>
                                <span class="text-xs font-semibold text-slate-400">/hr</span>
                            </div>

                            <a href="/cars/{{ $car->id }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-sm hover:shadow-md transition-all active:scale-95">
                                Detail →
                            </a>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @endif
</div>

