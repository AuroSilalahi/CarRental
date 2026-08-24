<div style="position:relative; background:#ffffff; border-radius:1rem; padding:2rem; box-shadow:0 10px 25px -5px rgba(0,0,0,0.05); margin-bottom:3rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
            <h2 style="font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
                🚗 Armada Pilihan Terbaik
            </h2>
            <p style="color:#64748b; font-size:0.9rem;">
                Pilih kendaraan siap pakai untuk kebutuhan perjalanan Anda di Sumatera Utara.
            </p>
        </div>
        @if($cars->count() > 1)
            <div style="display:flex; gap:0.5rem;">
                <button wire:click="prev" style="width:38px; height:38px; border-radius:50%; border:1px solid #e2e8f0; background:#f8fafc; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center; color:#334155; transition:all 0.2s;" onmouseover="this.style.background='#2563eb'; this.style.color='#fff';" onmouseout="this.style.background='#f8fafc'; this.style.color='#334155';">
                    ❮
                </button>
                <button wire:click="next" style="width:38px; height:38px; border-radius:50%; border:1px solid #e2e8f0; background:#f8fafc; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center; color:#334155; transition:all 0.2s;" onmouseover="this.style.background='#2563eb'; this.style.color='#fff';" onmouseout="this.style.background='#f8fafc'; this.style.color='#334155';">
                    ❯
                </button>
            </div>
        @endif
    </div>

    @if($cars->isEmpty())
        <div style="text-align:center; padding:3rem 1rem; background:#f8fafc; border-radius:0.75rem; color:#64748b;">
            Belum ada kendaraan unggulan yang tersedia saat ini.
        </div>
    @else
        @php
            $currentCar = $cars[$currentIndex] ?? $cars->first();
        @endphp

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; align-items:center; background:linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); border-radius:0.75rem; padding:1.5rem;">
            {{-- Left Image --}}
            <div style="height:260px; background:#cbd5e1; border-radius:0.5rem; overflow:hidden; display:flex; align-items:center; justify-content:center; position:relative;">
                @if($currentCar->image_path)
                    <img src="{{ asset('storage/' . $currentCar->image_path) }}" alt="{{ $currentCar->brand }} {{ $currentCar->model }}" style="width:100%; height:100%; object-fit:cover;">
                @else
                    <div style="font-size:5rem; color:#94a3b8;">🚘</div>
                @endif

                @if($currentCar->is_luxury_brand)
                    <span style="position:absolute; top:12px; left:12px; background:linear-gradient(135deg, #f59e0b, #d97706); color:#fff; padding:0.35rem 0.85rem; border-radius:999px; font-size:0.75rem; font-weight:700; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                        ✨ Luxury Brand
                    </span>
                @endif
            </div>

            {{-- Right Info --}}
            <div>
                <span style="display:inline-block; background:#dbeafe; color:#1e40af; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; padding:0.25rem 0.6rem; border-radius:0.25rem; margin-bottom:0.5rem;">
                    {{ $currentCar->type }}
                </span>
                <h3 style="font-size:1.75rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">
                    {{ $currentCar->brand }} {{ $currentCar->model }}
                </h3>

                <div style="display:flex; gap:1.5rem; color:#475569; font-size:0.875rem; margin-bottom:1.25rem;">
                    <span>👥 {{ $currentCar->passenger_capacity }} Orang</span>
                    <span>🎨 {{ $currentCar->colour }}</span>
                    <span>📅 {{ $currentCar->year }}</span>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <span style="font-size:1.5rem; font-weight:800; color:#2563eb;">
                        Rp {{ number_format($currentCar->daily_rate_idr, 0, ',', '.') }}
                    </span>
                    <span style="color:#64748b; font-size:0.875rem;">/ hari</span>
                </div>

                <a href="/cars/{{ $currentCar->id }}" style="display:inline-block; background:#2563eb; color:#fff; font-weight:700; padding:0.75rem 1.75rem; border-radius:0.5rem; text-decoration:none; transition:background 0.2s;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                    Lihat Detail & Pesan →
                </a>
            </div>
        </div>
    @endif
</div>
