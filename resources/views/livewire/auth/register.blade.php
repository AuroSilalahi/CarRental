<div class="max-w-xl mx-auto my-8 sm:my-12 bg-white rounded-3xl p-8 border border-slate-200/80 shadow-xl">
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 mx-auto flex items-center justify-center text-2xl mb-3 shadow-xs">
            📝
        </div>
        <h2 class="text-2xl font-black text-slate-900">
            Pendaftaran Akun Pelanggan
        </h2>
        <p class="text-slate-500 text-xs font-medium mt-1">
            Lengkapi formulir di bawah ini untuk mulai menyewa kendaraan.
        </p>
    </div>

    <form wire:submit="register" class="space-y-4">
        {{-- Name --}}
        <div>
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input type="text" wire:model="name" placeholder="Budi Santoso" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            @error('name') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- Email --}}
        <div>
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Email <span class="text-rose-500">*</span></label>
            <input type="email" wire:model="email" placeholder="budi@example.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            @error('email') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- Phone --}}
        <div>
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Telepon / WhatsApp <span class="text-rose-500">*</span></label>
            <input type="text" wire:model="phone" placeholder="08123456789" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            @error('phone') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- Password --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi <span class="text-rose-500">*</span></label>
                <input type="password" wire:model="password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                @error('password') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Konfirmasi Sandi <span class="text-rose-500">*</span></label>
                <input type="password" wire:model="passwordConfirmation" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                @error('passwordConfirmation') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Address --}}
        <div>
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Rumah <span class="text-rose-500">*</span></label>
            <textarea wire:model="address" rows="2" placeholder="Jl. Merdeka No. 45" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all"></textarea>
            @error('address') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- Province & City Dropdowns --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Provinsi <span class="text-rose-500">*</span></label>
                <select wire:model.live="province" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all bg-white cursor-pointer">
                    <option value="">-- Pilih Provinsi --</option>
                    @foreach($provinces as $id => $provName)
                        <option value="{{ $provName }}">{{ $provName }}</option>
                    @endforeach
                </select>
                @error('province') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Kota / Kabupaten <span class="text-rose-500">*</span></label>
                <select wire:model="city" @if(empty($cities)) disabled @endif class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:cursor-not-allowed cursor-pointer">
                    <option value="">{{ empty($province) ? '-- Pilih Provinsi Terlebih Dahulu --' : '-- Pilih Kota / Kabupaten --' }}</option>
                    @foreach($cities as $cityName)
                        <option value="{{ $cityName }}">{{ $cityName }}</option>
                    @endforeach
                </select>
                @error('city') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full py-3.5 px-6 rounded-xl font-extrabold text-sm text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 active:scale-95 transition-all cursor-pointer mt-4">
            Daftar Akun Sekarang →
        </button>
    </form>

    <p class="text-center mt-6 text-xs text-slate-500">
        Sudah punya akun? <a href="/login" class="text-blue-600 font-extrabold hover:underline">Masuk di sini</a>
    </p>
</div>

