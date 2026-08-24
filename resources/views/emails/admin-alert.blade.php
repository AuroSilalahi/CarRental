<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pemberitahuan Alert Admin</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <h2>⚠️ Perhatian Administrator,</h2>
    <p>Sistem otomatis mengalami masalah saat mencoba menyelesaikan rental secara otomatis:</p>
    <ul>
        <li><strong>Nomor Referensi:</strong> {{ $rental->reference_number }}</li>
        <li><strong>Pelanggan:</strong> {{ $rental->customer->name ?? 'N/A' }}</li>
        <li><strong>Pesan Error:</strong> {{ $errorMessage }}</li>
    </ul>
    <p>Status rental telah diubah menjadi <strong>REVIEW_REQUIRED</strong>. Silakan periksa detailnya melalui panel admin Filament.</p>
</body>
</html>
