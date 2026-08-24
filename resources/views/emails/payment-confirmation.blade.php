<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Konfirmasi Pembayaran</title>
</head>
<body>
<p>Yth. {{ $rental->customer->name }},</p>

<p>Pembayaran rental mobil Anda telah berhasil. Berikut rinciannya:</p>

<ul>
    <li><strong>Nomor Referensi:</strong> {{ $rental->reference_number }}</li>
    <li><strong>Kendaraan:</strong> {{ $rental->car->brand }} {{ $rental->car->model }}</li>
    <li><strong>Tanggal Mulai:</strong> {{ $rental->start_date->format('d M Y') }}</li>
    <li><strong>Tanggal Selesai:</strong> {{ $rental->end_date->format('d M Y') }}</li>
    @php
        $days = $rental->start_date->diffInDays($rental->end_date);
        $days = max(1, (int) ceil($days));
        $dailyRate = $rental->total_cost_idr / $days;
    @endphp
    <li><strong>Jumlah Hari:</strong> {{ $days }} hari</li>
    <li><strong>Tarif Harian:</strong> Rp {{ number_format($dailyRate, 0, ',', '.') }}</li>
    <li><strong>Total Dibayar:</strong> Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}</li>
</ul>

<p>Rental Anda telah dikonfirmasi. Selamat menikmati perjalanan!</p>

<p>Terima kasih telah menggunakan layanan kami.</p>
</body>
</html>
