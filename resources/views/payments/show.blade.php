@extends('layouts.app')

@section('title', 'Pembayaran & Pengambilan — ' . $rental->reference_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Pembayaran & Serah Terima Kunci</h1>
            <p class="text-sm font-semibold text-slate-500">Kode Booking: <span class="font-mono text-blue-600 font-bold">{{ $rental->reference_number }}</span></p>
        </div>
        <a href="{{ route('bookings.show', $rental->id) }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-all">
            ← Kembali ke Detail
        </a>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl text-sm font-bold flex items-center gap-3">
            <span class="text-xl">✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl text-sm font-bold flex items-center gap-3">
            <span class="text-xl">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @php
        $paymentStatus = $rental->payment ? ($rental->payment->status->value ?? $rental->payment->status) : 'unpaid';
        $isPaid = $paymentStatus === 'paid';
        $isPending = $paymentStatus === 'pending';
    @endphp

    {{-- Vehicle & Cost Summary Card --}}
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-md space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-slate-100 gap-4">
            <div>
                <span class="text-xs font-black text-blue-600 uppercase tracking-widest block mb-1">Kendaraan Dipesan</span>
                <h2 class="text-xl font-black text-slate-900">{{ $rental->car->brand }} {{ $rental->car->model }}</h2>
                <p class="text-xs font-bold text-slate-500">{{ $rental->car->type }} • Plat: {{ $rental->car->license_plate }}</p>
            </div>
            <div class="text-left sm:text-right">
                <span class="text-xs font-extrabold text-slate-400 block mb-1">Total Tagihan</span>
                <span class="text-2xl font-black text-blue-600">Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Rental Info Breakdown --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-semibold bg-slate-50 p-4 rounded-2xl border border-slate-100">
            <div>
                <span class="text-slate-400 block mb-1">Mulai Sewa / Penjemputan</span>
                <span class="text-slate-800 font-bold block">{{ \Carbon\Carbon::parse($rental->start_date)->isoFormat('D MMMM Y') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-1">Selesai Sewa / Pengembalian</span>
                <span class="text-slate-800 font-bold block">{{ \Carbon\Carbon::parse($rental->end_date)->isoFormat('D MMMM Y') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-1">Durasi</span>
                <span class="text-slate-800 font-bold block">{{ $days }} Hari</span>
            </div>
        </div>

        {{-- Status Section --}}
        @if($isPaid)
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 text-emerald-900 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🎉</span>
                    <div>
                        <h4 class="font-black text-sm">Pembayaran Lunas & Mobil Telah Diserahkan!</h4>
                        <p class="text-xs font-medium text-emerald-700">Metode: {{ $rental->payment->payment_method ?? 'Bayar di Kantor' }} • Dibayar {{ $rental->payment->paid_at ? $rental->payment->paid_at->isoFormat('D MMMM Y, HH:mm') : '-' }}</p>
                    </div>
                </div>
                <span class="bg-emerald-600 text-white text-xs font-black px-4 py-1.5 rounded-full">STATUS: PAID & ACTIVE</span>
            </div>
        @else
            {{-- Office Pickup Instructions Banner --}}
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 text-blue-900 space-y-3">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🏬</span>
                    <div>
                        <h4 class="font-black text-base">Pembayaran & Pengambilan Mobil di Kantor</h4>
                        <p class="text-xs font-semibold text-blue-700 mt-1 leading-relaxed">
                            Pemesanan Anda telah berhasil dicatat! Silakan datang ke kantor kami pada tanggal <strong>{{ \Carbon\Carbon::parse($rental->start_date)->isoFormat('D MMMM Y') }}</strong> dengan menunjukkan Kode Booking <strong>{{ $rental->reference_number }}</strong> dan KTP Anda.
                        </p>
                    </div>
                </div>
                <div class="pt-3 border-t border-blue-200/80 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-bold text-blue-800">
                    <div>📍 Lokasi Kantor: Jl. Pemuda No. 1, Medan</div>
                    <div>🕒 Jam Operasional: 08:00 - 20:00 WIB</div>
                </div>
            </div>

            {{-- Optional Transfer Section --}}
            <div class="space-y-6 pt-4 border-t border-slate-100">
                <div>
                    <h3 class="text-base font-black text-slate-900 mb-1">Transfer Bank Sebelum Kedatangan (Opsional)</h3>
                    <p class="text-xs font-semibold text-slate-500">Anda juga dapat membayar via transfer bank sekarang dan mengunggah buktinya di bawah ini:</p>
                </div>

                {{-- Bank Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Bank Jago --}}
                    <div class="border-2 border-amber-400/80 bg-amber-50/40 rounded-2xl p-4 space-y-2 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-amber-600 text-sm">BANK JAGO</span>
                            <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded">UTAMA</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-400 block">No. Rekening</span>
                            <span class="font-mono text-lg font-black text-slate-900 select-all">1071 8527 4876</span>
                        </div>
                        <span class="text-xs font-bold text-slate-500 block">a/n CarRental Demo</span>
                    </div>

                    {{-- Bank BCA --}}
                    <div class="border border-slate-200 bg-slate-50/50 rounded-2xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-blue-600 text-sm">BANK BCA</span>
                            <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2 py-0.5 rounded">OPSIONAL</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-slate-400 block">No. Rekening</span>
                            <span class="font-mono text-lg font-black text-slate-900 select-all">8830 123 456</span>
                        </div>
                        <span class="text-xs font-bold text-slate-500 block">a/n PT CarRental Indonesia</span>
                    </div>
                </div>

                {{-- Upload Proof Form --}}
                <form method="POST" action="{{ route('payments.proof', $rental->id) }}" enctype="multipart/form-data" class="space-y-4 pt-4 border-t border-slate-100">
                    @csrf
                    <h4 class="text-sm font-black text-slate-900">Upload Bukti Transfer (Jika Membayar via Transfer)</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                            <select name="payment_method" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="Bayar di Kantor (Cash/EDC)">Bayar di Kantor (Cash / EDC saat Pengambilan)</option>
                                <option value="Bank Jago (1071 8527 4876)">Bank Jago (1071 8527 4876)</option>
                                <option value="Bank BCA (8830 123 456)">Bank BCA (8830 123 456)</option>
                                <option value="GoPay / OVO / DANA">E-Wallet (GoPay / OVO / DANA)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Referensi (Opsional)</label>
                            <input type="text" name="transaction_reference" placeholder="Contoh: TRX-99887766" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">File Bukti Transfer (Opsional jika bayar di kantor)</label>
                        <input type="file" name="proof_file" required accept="image/jpeg,image/png,application/pdf" class="w-full px-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-all cursor-pointer">
                        @error('proof_file')
                            <span class="text-xs font-bold text-rose-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-3 flex items-center justify-between gap-4">
                        <span class="text-[11px] font-semibold text-slate-400">
                            🛡️ File simpanan bukti transfer dienkripsi di AWS S3 Private Storage.
                        </span>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-6 py-3 rounded-xl text-xs shadow-md shadow-blue-500/20 active:scale-95 transition-all">
                            Kirim Bukti Pembayaran →
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
