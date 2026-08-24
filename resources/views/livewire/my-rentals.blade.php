<div style="background:#ffffff; border-radius:1rem; padding:1.5rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h2 style="font-size:1.25rem; font-weight:800; color:#0f172a;">
            📋 Riwayat Pemesanan Saya
        </h2>

        {{-- Filter Status --}}
        <div>
            <select wire:model.live="statusFilter" style="padding:0.4rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.375rem; font-size:0.875rem; color:#0f172a; background:#fff;">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Pembayaran (Pending)</option>
                <option value="confirmed">Dikonfirmasi (Confirmed)</option>
                <option value="active">Sedang Berjalan (Active)</option>
                <option value="completed">Selesai (Completed)</option>
                <option value="cancelled">Dibatalkan (Cancelled)</option>
                <option value="expired">Kadaluwarsa (Expired)</option>
            </select>
        </div>
    </div>

    @if($rentals->isEmpty())
        <div style="text-align:center; padding:3rem 1rem; background:#f8fafc; border-radius:0.75rem; color:#64748b;">
            <div style="font-size:3rem; margin-bottom:0.5rem;">🚘</div>
            <p style="font-size:1rem; font-weight:600; color:#334155; margin-bottom:0.5rem;">Belum ada riwayat pemesanan.</p>
            <a href="/cars" style="display:inline-block; background:#2563eb; color:#fff; font-weight:700; padding:0.5rem 1.25rem; border-radius:0.375rem; text-decoration:none; font-size:0.875rem;">
                Sewa Mobil Sekarang
            </a>
        </div>
    @else
        <div style="display:flex; flex-direction:column; gap:1rem;">
            @foreach($rentals as $rental)
                <div style="border:1px solid #e2e8f0; border-radius:0.75rem; padding:1.25rem; background:#fff; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div>
                        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;">
                            <span style="font-family:monospace; font-weight:700; font-size:0.9rem; background:#f1f5f9; padding:0.2rem 0.5rem; border-radius:0.25rem; color:#334155;">
                                {{ $rental->reference_number }}
                            </span>

                            {{-- Status Badge --}}
                            @php
                                $statusColors = [
                                    'pending' => ['bg' => '#fef3c7', 'text' => '#b45309', 'label' => 'Menunggu Pembayaran'],
                                    'confirmed' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'label' => 'Dikonfirmasi'],
                                    'active' => ['bg' => '#dcfce7', 'text' => '#15803d', 'label' => 'Aktif'],
                                    'completed' => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => 'Selesai'],
                                    'cancelled' => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'label' => 'Dibatalkan'],
                                    'expired' => ['bg' => '#f3f4f6', 'text' => '#6b7280', 'label' => 'Kadaluwarsa'],
                                ];
                                $st = $statusColors[$rental->status->value ?? $rental->status] ?? ['bg' => '#e2e8f0', 'text' => '#334155', 'label' => ucfirst($rental->status->value ?? $rental->status)];
                            @endphp
                            <span style="background:{{ $st['bg'] }}; color:{{ $st['text'] }}; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.75rem; font-weight:700;">
                                {{ $st['label'] }}
                            </span>
                        </div>

                        <h4 style="font-size:1.125rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
                            {{ $rental->car->brand }} {{ $rental->car->model }}
                        </h4>

                        <div style="font-size:0.85rem; color:#64748b; display:flex; gap:1.5rem; flex-wrap:wrap;">
                            <span>📅 {{ $rental->start_date->format('d M Y') }} - {{ $rental->end_date->format('d M Y') }}</span>
                            <span>📍 Pickup: {{ Str::limit($rental->pickup_location, 25) }}</span>
                        </div>
                    </div>

                    <div style="text-align:right; display:flex; flex-direction:column; align-items:flex-end; gap:0.5rem;">
                        <span style="font-size:1.125rem; font-weight:800; color:#2563eb;">
                            Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}
                        </span>

                        <div style="display:flex; gap:0.5rem;">
                            <a href="/bookings/{{ $rental->id }}" style="background:#f1f5f9; color:#334155; font-size:0.8rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.375rem; text-decoration:none;" onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                                Detail Pemesanan
                            </a>

                            @if(($rental->status->value ?? $rental->status) === 'pending')
                                <a href="/payments/{{ $rental->id }}" style="background:#2563eb; color:#fff; font-size:0.8rem; font-weight:700; padding:0.4rem 0.8rem; border-radius:0.375rem; text-decoration:none;" onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                                    Bayar Sekarang →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div style="margin-top:1rem;">
                {{ $rentals->links() }}
            </div>
        </div>
    @endif
</div>
