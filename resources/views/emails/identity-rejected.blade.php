<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pemberitahuan Penolakan Dokumen KTP</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <h2>Halo {{ $customer->name }},</h2>
    <p>Mohon maaf, dokumen KTP yang Anda unggah <strong>BELUM DAPAT DISETUJUI</strong> karena alasan berikut:</p>
    <blockquote style="background: #fee2e2; padding: 12px; border-left: 4px solid #ef4444; color: #991b1b;">
        {{ $reason }}
    </blockquote>
    <p>Silakan unggah kembali foto/file KTP Anda yang lebih jelas melalui halaman profil Anda.</p>
    <p><a href="{{ url('/profile') }}" style="background: #2563eb; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Unggah Ulang Dokumen</a></p>
    <br>
    <p>Terima kasih,<br>Tim CarRental</p>
</body>
</html>
