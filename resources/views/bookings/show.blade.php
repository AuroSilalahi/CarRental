@extends('layouts.app')

@section('title', 'Konfirmasi Booking — ' . $rental->reference_number)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Konfirmasi Booking</h1>
        <p class="text-sm text-gray-500 mt-1">Ringkasan detail pemesanan kendaraan Anda.</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">

        <!-- Header with status badge -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider">Nomor Referensi</p>
                <p class="text-lg font-semibold text-gray-900 mt-0.5">{{ $rental->reference_number }}</p>
            </div>
            @php
                $statusColors = [
                    'pending'          => 'bg-yellow-100 text-yellow-800',
                    'confirmed'        => 'bg-green-100 text-green-800',
                    'active'           => 'bg-blue-100 text-blue-800',
                    'completed'        => 'bg-gray-100 text-gray-700',
                    'cancelled'        => 'bg-red-100 text-red-800',
                    'expired'          => 'bg-orange-100 text-orange-800',
                    'review_required'  => 'bg-purple-100 text-purple-800',
                ];
                $statusLabels = [
                    'pending'          => 'Menunggu',
                    'confirmed'        => 'Dikonfirmasi',
                    'active'           => 'Aktif',
                    'completed'        => 'Selesai',
                    'cancelled'        => 'Dibatalkan',
                    'expired'          => 'Kedaluwarsa',
                    'review_required'  => 'Perlu Tinjauan',
                ];
                $statusValue = $rental->status->value;
                $colorClass = $statusColors[$statusValue] ?? 'bg-gray-100 text-gray-700';
                $statusLabel = $statusLabels[$statusValue] ?? $statusValue;
            @endphp
            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium {{ $colorClass }}">
                {{ $statusLabel }}
            </span>
        </div>

        <!-- Detail rows -->
        <dl class="divide-y divide-gray-100">
            <div class="px-6 py-4 grid grid-cols-2 gap-4">
                <dt class="text-sm text-gray-500">Kendaraan</dt>
                <dd class="text-sm font-medium text-gray-900">{{ $rental->car->brand }} {{ $rental->car->model }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-4">
                <dt class="text-sm text-gray-500">Tanggal Mulai</dt>
                <dd class="text-sm font-medium text-gray-900">
                    {{ \Carbon\Carbon::parse($rental->start_date)->isoFormat('D MMMM Y') }}
                </dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-4">
                <dt class="text-sm text-gray-500">Tanggal Selesai</dt>
                <dd class="text-sm font-medium text-gray-900">
                    {{ \Carbon\Carbon::parse($rental->end_date)->isoFormat('D MMMM Y') }}
                </dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-4">
                <dt class="text-sm text-gray-500">Lokasi Pengambilan</dt>
                <dd class="text-sm font-medium text-gray-900">{{ $rental->pickup_location }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-4">
                <dt class="text-sm text-gray-500">Lokasi Pengembalian</dt>
                <dd class="text-sm font-medium text-gray-900">{{ $rental->return_location }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-4">
                <dt class="text-sm text-gray-500">Total Biaya</dt>
                <dd class="text-sm font-semibold text-gray-900">
                    Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}
                </dd>
            </div>

            @if ($rental->payment)
                <div class="px-6 py-4 grid grid-cols-2 gap-4">
                    <dt class="text-sm text-gray-500">Status Pembayaran</dt>
                    <dd class="text-sm font-medium text-gray-900">
                        @php
                            $paymentStatusColors = [
                                'unpaid'  => 'bg-yellow-100 text-yellow-800',
                                'pending' => 'bg-blue-100 text-blue-800',
                                'paid'    => 'bg-green-100 text-green-800',
                                'failed'  => 'bg-red-100 text-red-800',
                                'expired' => 'bg-orange-100 text-orange-800',
                            ];
                            $paymentStatusLabels = [
                                'unpaid'  => 'Belum Dibayar',
                                'pending' => 'Menunggu',
                                'paid'    => 'Lunas',
                                'failed'  => 'Gagal',
                                'expired' => 'Kedaluwarsa',
                            ];
                            $pStatusValue = $rental->payment->status->value;
                            $pColorClass  = $paymentStatusColors[$pStatusValue] ?? 'bg-gray-100 text-gray-700';
                            $pStatusLabel = $paymentStatusLabels[$pStatusValue] ?? $pStatusValue;
                        @endphp
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium {{ $pColorClass }}">
                            {{ $pStatusLabel }}
                        </span>
                        @if ($rental->payment->paid_at)
                            <span class="text-gray-500 text-xs ml-2">
                                pada {{ $rental->payment->paid_at->isoFormat('D MMMM Y, HH:mm') }}
                            </span>
                        @endif
                    </dd>
                </div>
            @endif
        </dl>

        <!-- Actions -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <a href="/" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke Beranda</a>

            @if ($rental->status->value === 'pending')
                <a href="{{ url('/payments/' . $rental->id) }}"
                   class="inline-block bg-gray-900 text-white text-sm font-medium px-5 py-2 rounded-md hover:bg-gray-700 transition">
                    Lanjut ke Pembayaran →
                </a>
            @elseif ($rental->payment && $rental->payment->status->value === 'paid')
                <span class="text-sm text-green-700 font-medium">✓ Pembayaran telah selesai</span>
            @endif
        </div>
    </div>
</div>
@endsection
