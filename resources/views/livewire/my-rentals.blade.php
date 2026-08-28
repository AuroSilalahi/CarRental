<div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-md relative">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4 pb-4 border-b border-slate-100">
        <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
            <span>📋</span> Riwayat Pemesanan Saya
        </h2>

        {{-- Filter Status --}}
        <div>
            <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
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
                <div class="border border-slate-200/80 rounded-2xl p-6 bg-white hover:border-blue-300 hover:shadow-md transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="cursor-pointer" wire:click="openDetailModal({{ $rental->id }})">
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <span class="font-mono font-bold text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-lg">
                                {{ $rental->reference_number }}
                            </span>

                            {{-- Status Badge --}}
                            @php
                                $rawStatus = $rental->status->value ?? $rental->status;
                                $paymentStatus = $rental->payment ? ($rental->payment->status->value ?? $rental->payment->status) : 'unpaid';
                                $hasProof = $rental->payment && !empty($rental->payment->proof_path);

                                $statusColors = [
                                    'pending'   => $hasProof ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-amber-100 text-amber-800 border-amber-200',
                                    'confirmed' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'active'    => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'completed' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    'expired'   => 'bg-gray-100 text-gray-700 border-gray-200',
                                ];
                                $statusLabels = [
                                    'pending'   => $hasProof ? '⏳ Menunggu Verifikasi Admin' : 'Menunggu Pembayaran',
                                    'confirmed' => 'Dikonfirmasi',
                                    'active'    => 'Aktif Berjalan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
                                    'expired'   => 'Kadaluwarsa',
                                ];
                                $badgeClass = $statusColors[$rawStatus] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                $badgeLabel = $statusLabels[$rawStatus] ?? ucfirst($rawStatus);
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-black border {{ $badgeClass }}">
                                {{ $badgeLabel }}
                            </span>
                        </div>

                        <h4 class="text-lg font-black text-slate-900 mb-1 hover:text-blue-600 transition-colors">
                            {{ $rental->car->brand }} {{ $rental->car->model }}
                        </h4>

                        <div class="text-xs font-semibold text-slate-500 flex flex-wrap gap-4 mt-2">
                            <span>📅 {{ $rental->start_date->format('d M Y') }} – {{ $rental->end_date->format('d M Y') }}</span>
                            <span>📍 Pengambilan: Kantor Utama (Jl. Pemuda No. 1, Medan)</span>
                            <span>🎯 Destinasi: {{ Str::limit($rental->destination ?? $rental->pickup_location, 30) }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col items-start md:items-end gap-3 w-full md:w-auto pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                        <span class="text-xl font-black text-blue-600">
                            Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}
                        </span>

                        <div class="flex items-center gap-2">
                            <button wire:click="openDetailModal({{ $rental->id }})" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold transition-all cursor-pointer">
                                👁️ Detail Modal
                            </button>

                            @if(($rental->status->value ?? $rental->status) === 'pending')
                                <a href="/payments/{{ $rental->id }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-md shadow-blue-500/20 transition-all active:scale-95">
                                    Bayar / Info Kantor →
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

    {{-- Detail Rental Popup Modal --}}
    @if($showModal && $selectedRental)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-6 relative max-h-[90vh] overflow-y-auto border border-slate-100">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-[11px] font-black text-blue-600 uppercase tracking-widest block">Detail Pemesanan</span>
                        <h3 class="text-lg font-black text-slate-900 font-mono">{{ $selectedRental->reference_number }}</h3>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold flex items-center justify-center text-sm transition-all cursor-pointer">
                        ✕
                    </button>
                </div>

                {{-- Car Header Card --}}
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                    <div class="w-20 h-16 bg-slate-200 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0">
                        @if($selectedRental->car->image_path)
                            <img src="{{ $selectedRental->car->image_url }}" alt="{{ $selectedRental->car->brand }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-2xl">🚗</span>
                        @endif
                    </div>
                    <div>
                        <h4 class="font-black text-slate-900 text-base">{{ $selectedRental->car->brand }} {{ $selectedRental->car->model }}</h4>
                        <p class="text-xs font-bold text-slate-500">{{ $selectedRental->car->type }} • Plat: {{ $selectedRental->car->license_plate }}</p>
                        <span class="text-xs font-black text-blue-600 mt-0.5 block">Rp {{ number_format($selectedRental->car->daily_rate_idr, 0, ',', '.') }} / hari</span>
                    </div>
                </div>

                {{-- Details Table --}}
                <div class="space-y-2 text-xs font-semibold text-slate-700 bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                    <div class="flex justify-between py-1.5 border-b border-slate-200/60">
                        <span class="text-slate-400 font-bold">Tanggal Sewa</span>
                        <span class="font-bold text-slate-900">{{ $selectedRental->start_date->format('d M Y') }} – {{ $selectedRental->end_date->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-200/60">
                        <span class="text-slate-400 font-bold">Total Durasi</span>
                        <span class="font-bold text-slate-900">{{ $selectedRental->start_date->diffInDays($selectedRental->end_date) }} Hari</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-200/60">
                        <span class="text-slate-400 font-bold">Total Biaya</span>
                        <span class="font-black text-blue-600 text-sm">Rp {{ number_format($selectedRental->total_cost_idr, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-200/60">
                        <span class="text-slate-400 font-bold">Lokasi Pengambilan</span>
                        <span class="font-bold text-slate-900 text-right">Kantor Utama (Jl. Pemuda No. 1, Medan)</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-200/60">
                        <span class="text-slate-400 font-bold">Lokasi Pengembalian</span>
                        <span class="font-bold text-slate-900 text-right">Kantor Utama (Jl. Pemuda No. 1, Medan)</span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-400 font-bold">Tujuan / Destinasi</span>
                        <span class="font-bold text-blue-600 text-right">{{ $selectedRental->destination ?? $selectedRental->pickup_location }}</span>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="flex items-center justify-between pt-2">
                    <button wire:click="closeModal" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold transition-all cursor-pointer">
                        Tutup
                    </button>
                    <a href="/payments/{{ $selectedRental->id }}" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-md transition-all">
                        Informasi Kantor & Pembayaran →
                    </a>
                </div>

            </div>
        </div>
    @endif
</div>
