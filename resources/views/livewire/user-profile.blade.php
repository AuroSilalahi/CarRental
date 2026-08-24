<div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; align-items:start;">
    {{-- Left: Profile Form --}}
    <div style="background:#ffffff; border-radius:1rem; padding:1.75rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
        <h2 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin-bottom:1.25rem;">
            👤 Data Profil Pelanggan
        </h2>

        @if(session('profile_success'))
            <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:0.75rem; border-radius:0.375rem; margin-bottom:1rem; font-size:0.875rem;">
                ✓ {{ session('profile_success') }}
            </div>
        @endif

        <form wire:submit="updateProfile">
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Nama Lengkap</label>
                <input type="text" wire:model="name" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                @error('name') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Email (Terverifikasi)</label>
                <input type="email" value="{{ $email }}" disabled style="width:100%; padding:0.5rem 0.75rem; border:1px solid #e2e8f0; border-radius:0.375rem; font-size:0.875rem; background:#f8fafc; color:#64748b;">
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Nomor Telepon / WhatsApp</label>
                <input type="text" wire:model="phone" placeholder="08123456789" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                @error('phone') <span style="color:#ef4444; font-size:0.75rem;">{{ $message }}</span> @enderror
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Alamat Rumah</label>
                <textarea wire:model="address" rows="2" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; font-family:inherit;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div>
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Kota / Kabupaten</label>
                    <input type="text" wire:model="city" placeholder="Medan" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">Provinsi</label>
                    <input type="text" wire:model="province" placeholder="Sumatera Utara" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem;">
                </div>
            </div>

            <button type="submit" style="background:#2563eb; color:#fff; font-weight:700; padding:0.6rem 1.5rem; border-radius:0.375rem; border:none; cursor:pointer; font-size:0.875rem;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                Simpan Perubahan
            </button>
        </form>
    </div>

    {{-- Right: Identity KTP Verification --}}
    <div style="background:#ffffff; border-radius:1rem; padding:1.75rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
        <h2 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin-bottom:1.25rem;">
            🪪 Verifikasi Identitas (KTP)
        </h2>

        @if(session('ktp_success'))
            <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:0.75rem; border-radius:0.375rem; margin-bottom:1rem; font-size:0.875rem;">
                ✓ {{ session('ktp_success') }}
            </div>
        @endif

        {{-- Status Box --}}
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.5rem; padding:1rem; margin-bottom:1.5rem;">
            <span style="font-size:0.85rem; color:#64748b; font-weight:600; display:block; margin-bottom:0.25rem;">Status Dokumen KTP:</span>
            
            @if(!$latestKtp)
                <span style="display:inline-block; background:#fee2e2; color:#991b1b; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.8rem; font-weight:700;">
                    Belum Mengunggah Dokumen
                </span>
            @else
                @php
                    $ktpStatus = $latestKtp->status->value ?? $latestKtp->status;
                @endphp
                @if($ktpStatus === 'pending_review')
                    <span style="display:inline-block; background:#fef3c7; color:#b45309; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.8rem; font-weight:700;">
                        ⏳ Dalam Peninjauan Admin (Pending)
                    </span>
                @elseif($ktpStatus === 'verified')
                    <span style="display:inline-block; background:#dcfce7; color:#15803d; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.8rem; font-weight:700;">
                        ✓ Terverifikasi (Verified)
                    </span>
                @elseif($ktpStatus === 'rejected')
                    <span style="display:inline-block; background:#fee2e2; color:#b91c1c; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.8rem; font-weight:700;">
                        ✕ Ditolak (Rejected)
                    </span>
                    @if($latestKtp->rejection_reason)
                        <p style="color:#b91c1c; font-size:0.85rem; margin-top:0.5rem; background:#fff; padding:0.5rem; border-radius:0.25rem; border:1px solid #fca5a5;">
                            <strong>Alasan Penolakan:</strong> {{ $latestKtp->rejection_reason }}
                        </p>
                    @endif
                @endif
            @endif
        </div>

        {{-- Upload Form --}}
        <form wire:submit="uploadKtp">
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#334155; margin-bottom:0.25rem;">
                    Pilih File KTP (JPEG, PNG, atau PDF - Maks. 5MB)
                </label>
                <input type="file" wire:model="ktpDocument" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.85rem; background:#fff;">
                @error('ktpDocument') <span style="color:#ef4444; font-size:0.75rem; display:block; margin-top:0.25rem;">{{ $message }}</span> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" style="background:#2563eb; color:#fff; font-weight:700; padding:0.6rem 1.5rem; border-radius:0.375rem; border:none; cursor:pointer; font-size:0.875rem;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                <span wire:loading.remove wire:target="uploadKtp">Unggah Dokumen KTP</span>
                <span wire:loading wire:target="uploadKtp">Mengunggah...</span>
            </button>
        </form>
    </div>
</div>
