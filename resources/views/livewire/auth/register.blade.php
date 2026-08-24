<div style="max-width:520px; margin:2rem auto; background:#ffffff; border-radius:1rem; padding:2rem; box-shadow:0 10px 25px -5px rgba(0,0,0,0.05); border:1px solid #f1f5f9;">
    <h2 style="font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem; text-align:center;">
        📝 Pendaftaran Akun Pelanggan
    </h2>
    <p style="color:#64748b; font-size:0.875rem; text-align:center; margin-bottom:1.5rem;">
        Lengkapi formulir di bawah ini untuk mulai menyewa kendaraan di Sumatera Utara.
    </p>

    <form wire:submit="register">
        {{-- Name --}}
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Nama Lengkap <span style="color:#ef4444;">*</span></label>
            <input type="text" wire:model="name" placeholder="Budi Santoso" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
            @error('name') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
        </div>

        {{-- Email --}}
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Email <span style="color:#ef4444;">*</span></label>
            <input type="email" wire:model="email" placeholder="budi@example.com" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
            @error('email') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
        </div>

        {{-- Phone --}}
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Nomor Telepon / WhatsApp <span style="color:#ef4444;">*</span></label>
            <input type="text" wire:model="phone" placeholder="08123456789" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
            @error('phone') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
        </div>

        {{-- Password --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Kata Sandi <span style="color:#ef4444;">*</span></label>
                <input type="password" wire:model="password" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                @error('password') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Konfirmasi Sandi <span style="color:#ef4444;">*</span></label>
                <input type="password" wire:model="passwordConfirmation" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                @error('passwordConfirmation') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Address --}}
        <div style="margin-bottom:1rem;">
            <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Alamat Rumah <span style="color:#ef4444;">*</span></label>
            <textarea wire:model="address" rows="2" placeholder="Jl. Merdeka No. 45" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; font-family:inherit;"></textarea>
            @error('address') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
        </div>

        {{-- City & Province --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Kota / Kabupaten <span style="color:#ef4444;">*</span></label>
                <input type="text" wire:model="city" placeholder="Medan" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                @error('city') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Provinsi <span style="color:#ef4444;">*</span></label>
                <input type="text" wire:model="province" placeholder="Sumatera Utara" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                @error('province') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" style="width:100%; background:#2563eb; color:#fff; font-weight:700; padding:0.75rem; border-radius:0.375rem; border:none; cursor:pointer; font-size:1rem; transition:background 0.2s;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
            Daftar Sekarang
        </button>
    </form>

    <p style="text-align:center; margin-top:1.5rem; font-size:0.875rem; color:#64748b;">
        Sudah punya akun? <a href="/login" style="color:#2563eb; font-weight:700; text-decoration:none;">Masuk di sini</a>
    </p>
</div>
