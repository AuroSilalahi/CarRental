<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
    {{-- Left: Profile Form --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-md">
        <h2 class="text-xl font-black text-slate-900 mb-6 pb-3 border-b border-slate-100 flex items-center gap-2">
            <span>👤</span> Data Profil Pelanggan
        </h2>

        @if(session('profile_success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl mb-6 text-xs font-bold flex items-center gap-2">
                <span>✓</span> {{ session('profile_success') }}
            </div>
        @endif

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                <input type="text" wire:model="name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                @error('name') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Email (Terverifikasi)</label>
                <input type="email" value="{{ $email }}" disabled class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-500 bg-slate-50 cursor-not-allowed">
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Telepon / WhatsApp</label>
                <input type="text" wire:model="phone" placeholder="08123456789" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                @error('phone') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Rumah</label>
                <textarea wire:model="address" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Kota / Kabupaten</label>
                    <input type="text" wire:model="city" placeholder="Medan" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Provinsi</label>
                    <input type="text" wire:model="province" placeholder="Sumatera Utara" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                </div>
            </div>

            <button type="submit" class="px-6 py-3 rounded-xl font-extrabold text-sm text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 active:scale-95 transition-all cursor-pointer mt-4">
                Simpan Perubahan
            </button>
        </form>
    </div>

    {{-- Right: Identity KTP Verification --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-md">
        <h2 class="text-xl font-black text-slate-900 mb-6 pb-3 border-b border-slate-100 flex items-center gap-2">
            <span>🪪</span> Verifikasi Identitas (KTP)
        </h2>

        @if(session('ktp_success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl mb-6 text-xs font-bold flex items-center gap-2">
                <span>✓</span> {{ session('ktp_success') }}
            </div>
        @endif

        {{-- Status Box --}}
        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-5 mb-6">
            <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block mb-2">Status Dokumen KTP:</span>
            
            @if(!$latestKtp)
                <span class="inline-block bg-rose-100 text-rose-800 border border-rose-200 px-3.5 py-1.5 rounded-full text-xs font-black">
                    Belum Mengunggah Dokumen
                </span>
            @else
                @php
                    $ktpStatus = $latestKtp->status->value ?? $latestKtp->status;
                @endphp
                @if($ktpStatus === 'pending_review')
                    <span class="inline-block bg-amber-100 text-amber-800 border border-amber-200 px-3.5 py-1.5 rounded-full text-xs font-black">
                        ⏳ Dalam Peninjauan Admin (Pending)
                    </span>
                @elseif($ktpStatus === 'verified')
                    <span class="inline-block bg-emerald-100 text-emerald-800 border border-emerald-200 px-3.5 py-1.5 rounded-full text-xs font-black">
                        ✓ Terverifikasi (Verified)
                    </span>
                @elseif($ktpStatus === 'rejected')
                    <span class="inline-block bg-rose-100 text-rose-800 border border-rose-200 px-3.5 py-1.5 rounded-full text-xs font-black">
                        ✕ Ditolak (Rejected)
                    </span>
                    @if($latestKtp->rejection_reason)
                        <div class="mt-3 p-3 bg-white border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
                            <strong>Alasan Penolakan:</strong> {{ $latestKtp->rejection_reason }}
                        </div>
                    @endif
                @endif
            @endif
        </div>

        {{-- Upload Form --}}
        <form wire:submit="uploadKtp" class="space-y-4">
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">
                    Pilih File KTP (JPEG, PNG, atau PDF - Maks. 5MB)
                </label>
                <input type="file" wire:model="ktpDocument" class="w-full p-2.5 border border-slate-200 rounded-xl text-xs bg-slate-50 font-medium file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                @error('ktpDocument') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" class="px-6 py-3 rounded-xl font-extrabold text-sm text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 active:scale-95 transition-all cursor-pointer">
                <span wire:loading.remove wire:target="uploadKtp">Unggah Dokumen KTP</span>
                <span wire:loading wire:target="uploadKtp">Mengunggah...</span>
            </button>
        </form>
    </div>
</div>

