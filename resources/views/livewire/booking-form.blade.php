<div style="background:#fff; border-radius:0.75rem; box-shadow:0 1px 4px rgba(0,0,0,0.08); padding:1.75rem;">

    <h2 style="font-size:1.25rem; font-weight:700; color:#111827; margin-bottom:1.25rem;">
        Formulir Pemesanan
    </h2>

    @if(! auth()->check())
        {{-- Not authenticated: show login prompt --}}
        <div style="text-align:center; padding:2rem 1rem; background:#f0f9ff; border-radius:0.5rem; border:1px solid #bae6fd;">
            <p style="color:#075985; margin-bottom:1rem; font-size:0.95rem;">
                Anda harus masuk untuk melakukan pemesanan.
            </p>
            <a href="/login"
               style="display:inline-block; background:#1d4ed8; color:#fff; padding:0.6rem 1.5rem; border-radius:0.375rem; font-weight:600; text-decoration:none; font-size:0.875rem;">
                Login untuk Memesan
            </a>
        </div>
    @else
        {{-- General error --}}
        @error('general')
            <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:0.75rem 1rem; border-radius:0.375rem; margin-bottom:1rem; font-size:0.875rem;">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit="submit" novalidate>

            {{-- Start Date --}}
            <div style="margin-bottom:1rem;">
                <label for="startDate" style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.375rem;">
                    Tanggal Mulai <span style="color:#dc2626;">*</span>
                </label>
                <input
                    type="date"
                    id="startDate"
                    wire:model.live="startDate"
                    min="{{ now()->toDateString() }}"
                    style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.875rem; color:#111827; outline:none; transition:border-color 0.15s;"
                    onfocus="this.style.borderColor='#1d4ed8'"
                    onblur="this.style.borderColor='#d1d5db'"
                >
                @error('startDate')
                    <p style="color:#dc2626; font-size:0.8rem; margin-top:0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- End Date --}}
            <div style="margin-bottom:1rem;">
                <label for="endDate" style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.375rem;">
                    Tanggal Selesai <span style="color:#dc2626;">*</span>
                </label>
                <input
                    type="date"
                    id="endDate"
                    wire:model.live="endDate"
                    min="{{ now()->addDay()->toDateString() }}"
                    style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.875rem; color:#111827; outline:none; transition:border-color 0.15s;"
                    onfocus="this.style.borderColor='#1d4ed8'"
                    onblur="this.style.borderColor='#d1d5db'"
                >
                @error('endDate')
                    <p style="color:#dc2626; font-size:0.8rem; margin-top:0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Real-time Cost Estimate --}}
            @if($estimatedCost > 0)
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.5rem; padding:0.875rem 1rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.875rem; color:#1e40af; font-weight:500;">Estimasi Biaya:</span>
                    <span style="font-size:1.125rem; font-weight:700; color:#1d4ed8;">
                        Rp {{ number_format($estimatedCost, 0, ',', '.') }}
                    </span>
                </div>
            @endif

            {{-- Availability Error --}}
            @if($availabilityError)
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:0.75rem 1rem; border-radius:0.375rem; margin-bottom:1rem; font-size:0.875rem;">
                    ⚠️ {{ $availabilityError }}
                </div>
            @endif

            {{-- Pickup Location --}}
            <div style="margin-bottom:1rem;">
                <label for="pickupLocation" style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.375rem;">
                    Lokasi Pengambilan <span style="color:#dc2626;">*</span>
                </label>
                <textarea
                    id="pickupLocation"
                    wire:model="pickupLocation"
                    rows="2"
                    placeholder="Masukkan alamat lokasi pengambilan kendaraan di Sumatera Utara..."
                    style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.875rem; color:#111827; resize:vertical; outline:none; transition:border-color 0.15s; font-family:inherit;"
                    onfocus="this.style.borderColor='#1d4ed8'"
                    onblur="this.style.borderColor='#d1d5db'"
                ></textarea>
                @error('pickupLocation')
                    <p style="color:#dc2626; font-size:0.8rem; margin-top:0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Return Location --}}
            <div style="margin-bottom:1.5rem;">
                <label for="returnLocation" style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.375rem;">
                    Lokasi Pengembalian <span style="color:#dc2626;">*</span>
                </label>
                <textarea
                    id="returnLocation"
                    wire:model="returnLocation"
                    rows="2"
                    placeholder="Masukkan alamat lokasi pengembalian kendaraan di Sumatera Utara..."
                    style="width:100%; padding:0.5rem 0.75rem; border:1px solid #d1d5db; border-radius:0.375rem; font-size:0.875rem; color:#111827; resize:vertical; outline:none; transition:border-color 0.15s; font-family:inherit;"
                    onfocus="this.style.borderColor='#1d4ed8'"
                    onblur="this.style.borderColor='#d1d5db'"
                ></textarea>
                @error('returnLocation')
                    <p style="color:#dc2626; font-size:0.8rem; margin-top:0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <button
                type="submit"
                @if(! $isAvailable) disabled @endif
                style="width:100%; padding:0.75rem 1rem; border-radius:0.375rem; font-weight:700; font-size:1rem; cursor:{{ $isAvailable ? 'pointer' : 'not-allowed' }}; border:none; background:{{ $isAvailable ? '#1d4ed8' : '#93c5fd' }}; color:#fff; transition:background 0.15s;"
                @if($isAvailable) onmouseover="this.style.background='#1e40af'" onmouseout="this.style.background='#1d4ed8'" @endif
            >
                <span wire:loading.remove wire:target="submit">Pesan Sekarang</span>
                <span wire:loading wire:target="submit">Memproses...</span>
            </button>

        </form>

    @endif

</div>
