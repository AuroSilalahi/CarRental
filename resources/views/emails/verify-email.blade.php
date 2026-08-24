<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; padding: 32px; }
        .btn { display: inline-block; padding: 12px 32px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 16px; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Halo, {{ $user->name }}!</h2>
        <p>Terima kasih telah mendaftar di aplikasi rental mobil kami. Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda.</p>
        <p>
            <a href="{{ $verificationUrl }}" class="btn">Verifikasi Email</a>
        </p>
        <p>Link verifikasi ini berlaku selama <strong>24 jam</strong>. Jika Anda tidak membuat akun, abaikan email ini.</p>
        <p>Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:</p>
        <p style="word-break:break-all; color:#2563eb;">{{ $verificationUrl }}</p>
        <div class="footer">
            &copy; {{ date('Y') }} Car Rental App. Seluruh hak dilindungi.
        </div>
    </div>
</body>
</html>
