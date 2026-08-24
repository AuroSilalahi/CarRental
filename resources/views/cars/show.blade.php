<x-layouts.app :title="$car->brand . ' ' . $car->model . ' - Car Rental'">
    <div class="container">

        {{-- Breadcrumb --}}
        <nav style="margin-bottom:1.5rem; font-size:0.875rem; color:#6b7280;">
            <a href="/" style="color:#1d4ed8; text-decoration:none;">Beranda</a>
            <span style="margin:0 0.5rem;">/</span>
            <a href="/cars" style="color:#1d4ed8; text-decoration:none;">Kendaraan</a>
            <span style="margin:0 0.5rem;">/</span>
            <span>{{ $car->brand }} {{ $car->model }}</span>
        </nav>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; align-items:start;">

            {{-- Left: Car Info --}}
            <div style="background:#fff; border-radius:0.75rem; box-shadow:0 1px 4px rgba(0,0,0,0.08); overflow:hidden;">

                {{-- Car Image --}}
                <div style="background:#e5e7eb; aspect-ratio:16/9; overflow:hidden;">
                    @if($car->image_path)
                        <img src="{{ asset('storage/' . $car->image_path) }}"
                             alt="{{ $car->brand }} {{ $car->model }}"
                             style="width:100%; height:100%; object-fit:cover;">
                    @else
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:3rem;">
                            🚗
                        </div>
                    @endif
                </div>

                {{-- Car Details --}}
                <div style="padding:1.5rem;">
                    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem; flex-wrap:wrap;">
                        <h1 style="font-size:1.5rem; font-weight:700; color:#111827;">
                            {{ $car->brand }} {{ $car->model }}
                        </h1>
                        {{-- Availability Badge --}}
                        @if($car->is_available)
                            <span style="background:#dcfce7; color:#166534; padding:0.25rem 0.75rem; border-radius:999px; font-size:0.75rem; font-weight:600;">
                                Tersedia
                            </span>
                        @else
                            <span style="background:#fee2e2; color:#991b1b; padding:0.25rem 0.75rem; border-radius:999px; font-size:0.75rem; font-weight:600;">
                                Tidak Tersedia
                            </span>
                        @endif
                        {{-- Luxury Badge --}}
                        @if($car->is_luxury_brand)
                            <span style="background:#fef3c7; color:#92400e; padding:0.25rem 0.75rem; border-radius:999px; font-size:0.75rem; font-weight:600;">
                                ✨ Luxury
                            </span>
                        @endif
                    </div>

                    {{-- Daily Rate --}}
                    <div style="font-size:1.375rem; font-weight:700; color:#1d4ed8; margin-bottom:1.25rem;">
                        Rp {{ number_format($car->daily_rate_idr, 0, ',', '.') }}
                        <span style="font-size:0.875rem; font-weight:400; color:#6b7280;">/ hari</span>
                    </div>

                    {{-- Attributes Grid --}}
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <tbody>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:0.6rem 0; color:#6b7280; width:45%;">Tipe Kendaraan</td>
                                <td style="padding:0.6rem 0; font-weight:500; color:#111827;">{{ $car->type }}</td>
                            </tr>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:0.6rem 0; color:#6b7280;">Plat Nomor</td>
                                <td style="padding:0.6rem 0; font-weight:500; color:#111827; font-family:monospace; letter-spacing:0.05em;">{{ $car->license_plate }}</td>
                            </tr>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:0.6rem 0; color:#6b7280;">Kapasitas Penumpang</td>
                                <td style="padding:0.6rem 0; font-weight:500; color:#111827;">{{ $car->passenger_capacity }} orang</td>
                            </tr>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:0.6rem 0; color:#6b7280;">Warna</td>
                                <td style="padding:0.6rem 0; font-weight:500; color:#111827;">{{ $car->colour }}</td>
                            </tr>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:0.6rem 0; color:#6b7280;">Tahun</td>
                                <td style="padding:0.6rem 0; font-weight:500; color:#111827;">{{ $car->year }}</td>
                            </tr>
                            @if($car->is_luxury_brand)
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:0.6rem 0; color:#6b7280;">Luxury Multiplier</td>
                                <td style="padding:0.6rem 0; font-weight:500; color:#111827;">{{ $car->luxury_multiplier }}×</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Right: Booking Form --}}
            <div>
                <livewire:booking-form :car="$car" />
            </div>

        </div>

    </div>
</x-layouts.app>
