<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Konfirmasi Pemesanan</title>
</head>
<body>
<p>Yth. {{ $rental->customer->name }},</p>

<p>Pemesanan rental mobil Anda telah berhasil dibuat. Berikut rinciannya:</p>

<ul>
    <li><strong>Nomor Referensi:</strong> {{ $rental->reference_number }}</li>
    <li><strong>Kendaraan:</strong> {{ $rental->car->brand }} {{ $rental->car->model }}</li>
    <li><strong>Plat Nomor:</strong> {{ $rental->car->license_plate }}</li>
    <li><strong>Tanggal Mulai:</strong> {{ $rental->start_date->format('d M Y') }}</li>
    <li><strong>Tanggal Selesai:</strong> {{ $rental->end_date->format('d M Y') }}</li>
    <li><strong>Lokasi Pengambilan:</strong> {{ $rental->pickup_location }}</li>
    <li><strong>Lokasi Pengembalian:</strong> {{ $rental->return_location }}</li>
    <li><strong>Total Biaya:</strong> Rp {{ number_format($rental->total_cost_idr, 0, ',', '.') }}</li>
</ul>

<p>Silakan selesaikan pembayaran dalam 24 jam untuk mengkonfirmasi pemesanan Anda.</p>

<p>Terima kasih telah menggunakan layanan kami.</p>
</body>
</html>
