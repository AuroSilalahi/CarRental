<div class="bg-white rounded-3xl border border-slate-200/80 shadow-md p-6 sm:p-8 sticky top-28">

    <h2 class="text-xl font-black text-slate-900 mb-6 pb-3 border-b border-slate-100 flex items-center gap-2">
        <span>📝</span> Formulir Pemesanan
    </h2>

    @if(! auth()->check())
        {{-- Not authenticated prompt --}}
        <div class="text-center py-8 px-4 bg-blue-50/70 border border-blue-100 rounded-2xl">
            <span class="text-3xl block mb-2">🔐</span>
            <p class="text-slate-700 text-sm font-semibold mb-4">
                Anda harus masuk untuk melakukan pemesanan kendaraan.
            </p>
            <a href="/login" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-6 py-3 rounded-xl text-sm shadow-md shadow-blue-500/20 transition-all active:scale-95">
                Login untuk Memesan →
            </a>
        </div>
    @else
        {{-- General error --}}
        @error('general')
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl mb-6 text-sm font-semibold">
                ⚠️ {{ $message }}
            </div>
        @enderror

        <form wire:submit="submit" novalidate class="space-y-4">

            {{-- Start Date --}}
            <div>
                <label for="startDate" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                    Tanggal Mulai <span class="text-rose-500">*</span>
                </label>
                <input
                    type="date"
                    id="startDate"
                    wire:model.live="startDate"
                    min="{{ now()->toDateString() }}"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                >
                @error('startDate')
                    <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- End Date --}}
            <div>
                <label for="endDate" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                    Tanggal Selesai <span class="text-rose-500">*</span>
                </label>
                <input
                    type="date"
                    id="endDate"
                    wire:model.live="endDate"
                    min="{{ now()->addDay()->toDateString() }}"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                >
                @error('endDate')
                    <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Real-time Cost Estimate --}}
            @if($estimatedCost > 0)
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-4 flex items-center justify-between shadow-2xs">
                    <span class="text-xs font-black uppercase text-blue-900">Estimasi Biaya:</span>
                    <span class="text-xl font-black text-blue-600">
                        Rp {{ number_format($estimatedCost, 0, ',', '.') }}
                    </span>
                </div>
            @endif

            {{-- Availability Error --}}
            @if($availabilityError)
                <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl text-xs font-bold">
                    ⚠️ {{ $availabilityError }}
                </div>
            @endif

            {{-- Pickup Location --}}
            <div>
                <label for="pickupLocation" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                    Lokasi Pengambilan <span class="text-rose-500">*</span>
                </label>
                <textarea
                    id="pickupLocation"
                    wire:model="pickupLocation"
                    rows="2"
                    placeholder="Masukkan alamat lokasi pengambilan di Sumatera Utara..."
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                ></textarea>
                @error('pickupLocation')
                    <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Return Location --}}
            <div>
                <label for="returnLocation" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                    Lokasi Pengembalian <span class="text-rose-500">*</span>
                </label>
                <textarea
                    id="returnLocation"
                    wire:model="returnLocation"
                    rows="2"
                    placeholder="Masukkan alamat lokasi pengembalian..."
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                ></textarea>
                @error('returnLocation')
                    <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <button
                type="submit"
                @if(! $isAvailable) disabled @endif
                class="w-full py-3.5 px-6 rounded-xl font-extrabold text-sm text-white shadow-md transition-all duration-200 flex items-center justify-center gap-2 {{ $isAvailable ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20 active:scale-95 cursor-pointer' : 'bg-slate-300 cursor-not-allowed' }}"
            >
                <span wire:loading.remove wire:target="submit">Pesan Sekarang →</span>
                <span wire:loading wire:target="submit">Memproses Pemesanan...</span>
            </button>

        </form>

    @endif

</div>

