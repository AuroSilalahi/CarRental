<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dokumen Identitas Disetujui</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <h2>Halo {{ $customer->name }},</h2>
    <p>Selamat! Dokumen KTP yang Anda unggah telah <strong>DISETUJUI</strong> oleh tim verifikasi kami.</p>
    <p>Akun Anda sekarang memiliki status verifikasi penuh dan siap untuk melakukan pemesanan kendaraan kapan saja.</p>
    <p><a href="{{ url('/cars') }}" style="background: #2563eb; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Sewa Mobil Sekarang</a></p>
    <br>
    <p>Terima kasih,<br>Tim CarRental</p>
</body>
</html>
