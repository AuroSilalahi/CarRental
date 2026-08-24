@extends('layouts.app')

@section('title', 'Pembayaran — ' . $rental->reference_number)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Pembayaran Rental</h1>
        <p class="text-sm text-gray-500 mt-1">Tinjau detail dan selesaikan pembayaran Anda.</p>
    </div>

    @php
        $isPaid = $rental->payment && $rental->payment->status->value === 'paid';
    @endphp

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">

        <!-- Vehicle & booking detail -->
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Detail Kendaraan</h2>
            <div class="flex items-start gap-4">
                <div class="flex-1">
                    <p class="text-lg font-semibold text-gray-900">{{ $rental->car->brand }} {{ $rental->car->model }}</p>
                    <p class="text-sm text-gray-500">{{ $rental->car->type }}</p>
                </div>
            </div>
        </div>

        <!-- Rental period -->
        <dl class="divide-y divide-gray-100">
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
                <dt class="text-sm text-gray-500">Jumlah Hari Sewa</dt>
                <dd class="text-sm font-medium text-gray-900">{{ $days }} hari</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-4">
                <dt class="text-sm text-gray-500">Tarif Per Hari</dt>
                <dd class="text-sm font-medium text-gray-900">
                    Rp {{ number_format($rental->car->daily_rate_idr, 0, ',', '.') }} / hari
                </dd>
            </div>
        </dl>

        <!-- Total -->
        <div class="px-6 py-5 bg-gray-50 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-base font-semibold text-gray-700">Total Pembayaran</span>
                <span class="text-xl font-bold text-gray-900">
                    Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Payment status or pay button -->
        <div class="px-6 py-4 border-t border-gray-100">
            @if ($isPaid)
                <div class="flex items-center justify-between">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        ✓ Lunas
                    </span>
                    @if ($rental->payment->paid_at)
                        <span class="text-sm text-gray-500">
                            Dibayar pada {{ $rental->payment->paid_at->isoFormat('D MMMM Y, HH:mm') }}
                        </span>
                    @endif
                </div>
                <div class="mt-4">
                    <a href="{{ route('bookings.show', $rental->id) }}"
                       class="inline-block text-sm text-gray-600 hover:text-gray-900">
                        ← Lihat Detail Booking
                    </a>
                </div>
            @else
                <form method="POST" action="{{ url('/payments/' . $rental->id . '/pay') }}">
                    @csrf
                    <div class="flex items-center justify-between">
                        <a href="{{ route('bookings.show', $rental->id) }}"
                           class="text-sm text-gray-500 hover:text-gray-700">← Kembali</a>
                        <button type="submit"
                                class="bg-gray-900 text-white text-sm font-medium px-6 py-2.5 rounded-md hover:bg-gray-700 transition">
                            Bayar Sekarang
                        </button>
                    </div>
                </form>
            @endif
        </div>

    </div>

    <!-- Expiry notice -->
    @if ($rental->payment && $rental->payment->expires_at && !$isPaid)
        <p class="text-xs text-gray-400 mt-3 text-center">
            Batas waktu pembayaran:
            {{ $rental->payment->expires_at->isoFormat('D MMMM Y, HH:mm') }}
        </p>
    @endif
</div>
@endsection
