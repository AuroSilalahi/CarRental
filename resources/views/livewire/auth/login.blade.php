<div class="max-w-md mx-auto my-8 sm:my-12 bg-white rounded-3xl p-8 border border-slate-200/80 shadow-xl">
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 mx-auto flex items-center justify-center text-2xl mb-3 shadow-xs">
            🔑
        </div>
        <h2 class="text-2xl font-black text-slate-900">
            Masuk Akun Pelanggan
        </h2>
        <p class="text-slate-500 text-xs font-medium mt-1">
            Masukkan email dan kata sandi Anda untuk melanjutkan.
        </p>
    </div>

    @if($errorMessage)
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl mb-6 text-xs font-bold flex items-center gap-2">
            <span>⚠️</span> {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-5">
        {{-- Email --}}
        <div>
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
            <input type="email" wire:model="email" placeholder="budi@example.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            @error('email') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- Password --}}
        <div>
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi</label>
            <input type="password" wire:model="password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            @error('password') <span class="text-rose-600 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full py-3.5 px-6 rounded-xl font-extrabold text-sm text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 active:scale-95 transition-all cursor-pointer">
            Masuk Sekarang →
        </button>
    </form>

    <p class="text-center mt-6 text-xs text-slate-500">
        Belum punya akun? <a href="/register" class="text-blue-600 font-extrabold hover:underline">Daftar di sini</a>
    </p>
</div>

