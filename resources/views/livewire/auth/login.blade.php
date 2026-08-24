<div style="max-width:420px; margin:3rem auto; background:#ffffff; border-radius:1rem; padding:2rem; box-shadow:0 10px 25px -5px rgba(0,0,0,0.05); border:1px solid #f1f5f9;">
    <h2 style="font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem; text-align:center;">
        🔑 Masuk Akun Pelanggan
    </h2>
    <p style="color:#64748b; font-size:0.875rem; text-align:center; margin-bottom:1.5rem;">
        Masukkan email dan kata sandi Anda untuk melanjutkan pemesanan.
    </p>

    @if($errorMessage)
        <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:0.75rem 1rem; border-radius:0.375rem; margin-bottom:1rem; font-size:0.875rem;">
            ⚠️ {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit="login">
        {{-- Email --}}
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Alamat Email</label>
            <input type="email" wire:model="email" placeholder="budi@example.com" style="width:100%; padding:0.6rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
            @error('email') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
        </div>

        {{-- Password --}}
        <div style="margin-bottom:1.5rem;">
            <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Kata Sandi</label>
            <input type="password" wire:model="password" style="width:100%; padding:0.6rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
            @error('password') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" style="width:100%; background:#2563eb; color:#fff; font-weight:700; padding:0.75rem; border-radius:0.375rem; border:none; cursor:pointer; font-size:1rem; transition:background 0.2s;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
            Masuk Sekarang
        </button>
    </form>

    <p style="text-align:center; margin-top:1.5rem; font-size:0.875rem; color:#64748b;">
        Belum punya akun? <a href="/register" style="color:#2563eb; font-weight:700; text-decoration:none;">Daftar di sini</a>
    </p>
</div>
