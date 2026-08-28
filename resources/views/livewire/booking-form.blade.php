<div class="bg-white rounded-3xl border border-slate-200/80 shadow-md p-6 sm:p-8 sticky top-28">

    <h2 class="text-xl font-black text-slate-900 mb-6 pb-3 border-b border-slate-100 flex items-center gap-2">
        <span>📝</span> Formulir Pemesanan
    </h2>

    @if(! auth()->check())
        {{-- Not authenticated prompt --}}
        <div class="text-center py-8 px-4 bg-blue-50/70 border border-blue-100 rounded-2xl space-y-3">
            <span class="text-3xl block">🔐</span>
            <p class="text-slate-700 text-sm font-semibold">
                Anda harus masuk untuk melakukan pemesanan kendaraan.
            </p>
            <a href="/login" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-6 py-3 rounded-xl text-sm shadow-md shadow-blue-500/20 transition-all active:scale-95">
                Login untuk Memesan →
            </a>
        </div>
    @else
        @php
            /** @var \App\Models\User $currentUser */
            $currentUser = auth()->user();
            $latestDoc = $currentUser->identityDocuments()->latest()->first();
            $ktpStatus = $latestDoc ? ($latestDoc->status->value ?? $latestDoc->status) : 'none';
        @endphp

        @if($ktpStatus === 'none')
            {{-- No KTP uploaded yet --}}
            <div class="text-center py-6 px-4 bg-amber-50 border border-amber-200 rounded-2xl space-y-3">
                <span class="text-3xl block">🪪</span>
                <h3 class="text-sm font-black text-amber-900">Upload KTP Diperlukan</h3>
                <p class="text-xs font-semibold text-amber-700 leading-relaxed">
                    Sebelum membuat janjian & memesan mobil, Anda wajib mengunggah foto KTP pada profil Anda terlebih dahulu.
                </p>
                <a href="/profile" class="inline-block bg-amber-600 hover:bg-amber-700 text-white font-extrabold px-5 py-2.5 rounded-xl text-xs shadow-md transition-all active:scale-95">
                    Unggah KTP Sekarang →
                </a>
            </div>
        @elseif($ktpStatus === 'pending_review')
            {{-- KTP Pending Admin Approval --}}
            <div class="text-center py-6 px-4 bg-blue-50 border border-blue-200 rounded-2xl space-y-3">
                <span class="text-3xl block">⏳</span>
                <h3 class="text-sm font-black text-blue-900">Menunggu Verifikasi Admin</h3>
                <p class="text-xs font-semibold text-blue-700 leading-relaxed">
                    KTP Anda sedang diverifikasi Admin (estimasi 5–10 menit). Setelah disetujui, Anda dapat langsung memilih tanggal dan memesan mobil.
                </p>
                <div class="pt-2">
                    <span class="inline-block bg-blue-100 text-blue-800 text-[11px] font-black px-3.5 py-1 rounded-full border border-blue-200">
                        Status: Pending Approval (5-10 Menit)
                    </span>
                </div>
            </div>
        @elseif($ktpStatus === 'rejected')
            {{-- KTP Rejected --}}
            <div class="text-center py-6 px-4 bg-rose-50 border border-rose-200 rounded-2xl space-y-3">
                <span class="text-3xl block">❌</span>
                <h3 class="text-sm font-black text-rose-900">Dokumen KTP Ditolak</h3>
                <p class="text-xs font-semibold text-rose-700 leading-relaxed">
                    Alasan: "{{ $latestDoc->rejection_reason ?? 'Foto KTP kurang jelas.' }}". Silakan upload ulang KTP di halaman profil.
                </p>
                <a href="/profile" class="inline-block bg-rose-600 hover:bg-rose-700 text-white font-extrabold px-5 py-2.5 rounded-xl text-xs shadow-md transition-all active:scale-95">
                    Upload Ulang KTP →
                </a>
            </div>
        @else
            {{-- KTP Verified: Show Booking Form --}}
            @error('general')
                <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl mb-6 text-sm font-semibold">
                    ⚠️ {{ $message }}
                </div>
            @enderror

            <form wire:submit="submit" novalidate class="space-y-4">

                {{-- Start Date --}}
                <div>
                    <label for="startDate" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tanggal Mulai Sewa <span class="text-rose-500">*</span>
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
                        Tanggal Selesai Sewa <span class="text-rose-500">*</span>
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

                {{-- Destination Purpose --}}
                <div>
                    <label for="pickupLocation" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Destinasi / Tujuan Perjalanan <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        id="pickupLocation"
                        wire:model="pickupLocation"
                        rows="2"
                        placeholder="Contoh: Perjalanan ke Pematangsiantar, Danau Toba, Berastagi, Medan Kota, dll."
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
                        placeholder="Kantor Utama CarRental (Jl. Pemuda No. 1, Medan)"
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
                    <span wire:loading.remove wire:target="submit">Buat Pemesanan & Janjikan di Kantor →</span>
                    <span wire:loading wire:target="submit">Memproses Pemesanan...</span>
                </button>

            </form>
        @endif
    @endif

</div>
