<div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-md">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4 pb-4 border-b border-slate-100">
        <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
            <span>📋</span> Riwayat Pemesanan Saya
        </h2>

        {{-- Filter Status --}}
        <div>
            <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500">
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
        <div class="text-center py-16 px-4 bg-slate-50 rounded-2xl text-slate-500">
            <span class="text-5xl block mb-3">🚘</span>
            <p class="text-base font-bold text-slate-800 mb-4">Belum ada riwayat pemesanan.</p>
            <a href="/cars" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-6 py-3 rounded-xl text-sm shadow-md shadow-blue-500/20 active:scale-95 transition-all">
                Sewa Mobil Sekarang →
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($rentals as $rental)
                <div class="border border-slate-200/80 rounded-2xl p-6 bg-white hover:border-blue-200 hover:shadow-md transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <span class="font-mono font-bold text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-lg">
                                {{ $rental->reference_number }}
                            </span>

                            {{-- Status Badge --}}
                            @php
                                $statusColors = [
                                    'pending'   => 'bg-amber-100 text-amber-800 border-amber-200',
                                    'confirmed' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'active'    => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'completed' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    'expired'   => 'bg-gray-100 text-gray-700 border-gray-200',
                                ];
                                $statusLabels = [
                                    'pending'   => 'Menunggu Pembayaran',
                                    'confirmed' => 'Dikonfirmasi',
                                    'active'    => 'Aktif Berjalan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
                                    'expired'   => 'Kadaluwarsa',
                                ];
                                $rawStatus = $rental->status->value ?? $rental->status;
                                $badgeClass = $statusColors[$rawStatus] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                $badgeLabel = $statusLabels[$rawStatus] ?? ucfirst($rawStatus);
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-black border {{ $badgeClass }}">
                                {{ $badgeLabel }}
                            </span>
                        </div>

                        <h4 class="text-lg font-black text-slate-900 mb-1">
                            {{ $rental->car->brand }} {{ $rental->car->model }}
                        </h4>

                        <div class="text-xs font-semibold text-slate-500 flex flex-wrap gap-4">
                            <span>📅 {{ $rental->start_date->format('d M Y') }} – {{ $rental->end_date->format('d M Y') }}</span>
                            <span>📍 Pickup: {{ Str::limit($rental->pickup_location, 30) }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col items-start md:items-end gap-3 w-full md:w-auto pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                        <span class="text-xl font-black text-blue-600">
                            Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}
                        </span>

                        <div class="flex items-center gap-2">
                            <a href="/bookings/{{ $rental->id }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold transition-all">
                                Detail
                            </a>

                            @if(($rental->status->value ?? $rental->status) === 'pending')
                                <a href="/payments/{{ $rental->id }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-md shadow-blue-500/20 transition-all active:scale-95">
                                    Bayar Sekarang →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-6">
                {{ $rentals->links() }}
            </div>
        </div>
    @endif
</div>

