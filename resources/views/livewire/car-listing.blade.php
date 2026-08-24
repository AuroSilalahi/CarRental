<div>
    {{-- Search & Filter Section --}}
    <div style="background:#ffffff; border-radius:1rem; padding:1.5rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom:2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
            <h2 style="font-size:1.25rem; font-weight:800; color:#0f172a;">
                🔎 Filter & Cari Kendaraan
            </h2>
            <button wire:click="resetFilters" style="background:#f1f5f9; border:none; color:#64748b; font-size:0.875rem; font-weight:600; padding:0.4rem 0.8rem; border-radius:0.375rem; cursor:pointer; transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                🔄 Reset Filter
            </button>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1rem;">
            {{-- Search Keyword --}}
            <div>
                <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:0.25rem;">Cari (Brand / Model)</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Contoh: Toyota, Avanza..." style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; color:#0f172a;">
            </div>

            {{-- Type Filter --}}
            <div>
                <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:0.25rem;">Tipe Mobil</label>
                <select wire:model.live="type" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; color:#0f172a; background:#fff;">
                    <option value="">Semua Tipe</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Brand Filter --}}
            <div>
                <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:0.25rem;">Merek (Brand)</label>
                <select wire:model.live="brand" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; color:#0f172a; background:#fff;">
                    <option value="">Semua Merek</option>
                    @foreach($brands as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Min Capacity Filter --}}
            <div>
                <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:0.25rem;">Min. Kapasitas</label>
                <select wire:model.live="capacity" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; color:#0f172a; background:#fff;">
                    <option value="">Semua</option>
                    <option value="2">2+ Orang</option>
                    <option value="4">4+ Orang</option>
                    <option value="6">6+ Orang</option>
                    <option value="8">8+ Orang</option>
                </select>
            </div>

            {{-- Availability Filter --}}
            <div>
                <label style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:0.25rem;">Status Mobil</label>
                <select wire:model.live="availabilityFilter" style="width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; color:#0f172a; background:#fff;">
                    <option value="">Semua Status</option>
                    <option value="available">Tersedia</option>
                    <option value="unavailable">Tidak Tersedia</option>
                </select>
            </div>
        </div>

        {{-- Date Range Picker --}}
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:0.5rem; padding:1rem;">
            <span style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.5rem;">
                📅 Cek Ketersediaan Berdasarkan Tanggal Rental:
            </span>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#64748b;">Tanggal Mulai</label>
                    <input type="date" wire:model.live="startDate" min="{{ now()->toDateString() }}" style="width:100%; padding:0.4rem 0.6rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.85rem;">
                </div>
                <div>
                    <label style="display:block; font-size:0.75rem; font-weight:600; color:#64748b;">Tanggal Selesai</label>
                    <input type="date" wire:model.live="endDate" min="{{ now()->addDay()->toDateString() }}" style="width:100%; padding:0.4rem 0.6rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.85rem;">
                </div>
            </div>
            @error('endDate')
                <p style="color:#ef4444; font-size:0.8rem; margin-top:0.35rem; font-weight:600;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Cars Grid List --}}
    @if($cars->isEmpty())
        <div style="text-align:center; padding:4rem 1rem; background:#fff; border-radius:1rem; color:#64748b; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
            <h3 style="font-size:1.25rem; font-weight:700; color:#1e293b; margin-bottom:0.5rem;">Tidak ada kendaraan ditemukan</h3>
            <p style="font-size:0.9rem;">Coba sesuaikan filter atau tanggal pencarian Anda.</p>
        </div>
    @else
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
            @foreach($cars as $car)
                <div style="background:#ffffff; border-radius:0.75rem; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); display:flex; flex-direction:column; border:1px solid #f1f5f9; transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 20px -5px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.05)';">
                    
                    {{-- Image & Badges --}}
                    <div style="height:180px; background:#e2e8f0; position:relative; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                        @if($car->image_path)
                            <img src="{{ asset('storage/' . $car->image_path) }}" alt="{{ $car->brand }} {{ $car->model }}" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <div style="font-size:4rem; color:#94a3b8;">🚘</div>
                        @endif

                        {{-- Availability Badge --}}
                        @php
                            $effectiveAvailable = isset($car->is_date_available) ? $car->is_date_available : $car->is_available;
                        @endphp

                        @if($effectiveAvailable)
                            <span style="position:absolute; top:10px; right:10px; background:#dcfce7; color:#15803d; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.75rem; font-weight:700;">
                                ✓ Tersedia
                            </span>
                        @else
                            <span style="position:absolute; top:10px; right:10px; background:#fee2e2; color:#b91c1c; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.75rem; font-weight:700;">
                                ✕ Tidak Tersedia
                            </span>
                        @endif

                        @if($car->is_luxury_brand)
                            <span style="position:absolute; top:10px; left:10px; background:#fef3c7; color:#b45309; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.75rem; font-weight:700;">
                                ✨ Luxury
                            </span>
                        @endif
                    </div>

                    {{-- Card Body --}}
                    <div style="padding:1.25rem; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <span style="font-size:0.75rem; font-weight:700; color:#2563eb; text-transform:uppercase; letter-spacing:0.05em;">
                                {{ $car->type }}
                            </span>
                            <h3 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin-top:0.2rem; margin-bottom:0.5rem;">
                                {{ $car->brand }} {{ $car->model }}
                            </h3>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; font-size:0.8rem; color:#64748b; margin-bottom:1rem;">
                                <div>👥 {{ $car->passenger_capacity }} Penumpang</div>
                                <div>🎨 {{ $car->colour }}</div>
                                <div>📅 Tahun {{ $car->year }}</div>
                                <div>🪪 {{ $car->license_plate }}</div>
                            </div>
                        </div>

                        {{-- Card Footer --}}
                        <div style="border-top:1px solid #f1f5f9; pt:0.875rem; pt:0.75rem; display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
                            <div>
                                <span style="font-size:1.125rem; font-weight:800; color:#2563eb;">
                                    Rp {{ number_format($car->daily_rate_idr, 0, ',', '.') }}
                                </span>
                                <span style="font-size:0.75rem; color:#64748b;">/hr</span>
                            </div>

                            <a href="/cars/{{ $car->id }}" style="background:#2563eb; color:#fff; font-size:0.85rem; font-weight:700; padding:0.5rem 1rem; border-radius:0.375rem; text-decoration:none; transition:background 0.2s;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                                Detail →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
