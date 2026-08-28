<div style="font-family: ui-sans-serif, system-ui, sans-serif; font-size: 14px; color: #1e293b;">

    {{-- Customer Information Table --}}
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 20px; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <thead style="background-color: #f8fafc;">
            <tr>
                <th colspan="2" style="padding: 14px 18px; text-align: left; font-size: 15px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0;">
                    👤 Data Pelanggan — {{ $customer->name }}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 10px 18px; width: 32%; font-weight: 700; color: #64748b; background-color: #f8fafc; border-bottom: 1px solid #f1f5f9;">Nama Lengkap</td>
                <td style="padding: 10px 18px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{{ $customer->name }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 18px; font-weight: 700; color: #64748b; background-color: #f8fafc; border-bottom: 1px solid #f1f5f9;">Alamat Email</td>
                <td style="padding: 10px 18px; font-weight: 600; color: #2563eb; border-bottom: 1px solid #f1f5f9;">{{ $customer->email }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 18px; font-weight: 700; color: #64748b; background-color: #f8fafc; border-bottom: 1px solid #f1f5f9;">Nomor Telepon / WhatsApp</td>
                <td style="padding: 10px 18px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{{ $customer->phone ?? 'Belum Diisi' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 18px; font-weight: 700; color: #64748b; background-color: #f8fafc; border-bottom: 1px solid #f1f5f9;">Tanggal Registrasi</td>
                <td style="padding: 10px 18px; font-weight: 600; color: #334155; border-bottom: 1px solid #f1f5f9;">{{ $customer->created_at->format('d M Y, H:i') }} WIB</td>
            </tr>
            <tr>
                <td style="padding: 10px 18px; font-weight: 700; color: #64748b; background-color: #f8fafc; border-bottom: 1px solid #f1f5f9;">Kota & Provinsi</td>
                <td style="padding: 10px 18px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{{ $customer->city ?? '-' }}, {{ $customer->province ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 18px; font-weight: 700; color: #64748b; background-color: #f8fafc; border-bottom: 1px solid #f1f5f9;">Alamat Lengkap Rumah</td>
                <td style="padding: 10px 18px; font-weight: 500; color: #334155; border-bottom: 1px solid #f1f5f9;">{{ $customer->address ?? 'Alamat belum diisi.' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 18px; font-weight: 700; color: #64748b; background-color: #f8fafc;">Status Akun & Identitas KTP</td>
                <td style="padding: 10px 18px;">
                    @php
                        $accStatus = $customer->account_status->value ?? $customer->account_status;
                    @endphp
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; background-color: #dcfce7; color: #166534; margin-right: 8px;">
                        AKUN: {{ strtoupper($accStatus) }}
                    </span>

                    @php
                        $docStatus = $document ? ($document->status->value ?? $document->status) : 'none';
                        $ktpBg = match($docStatus) {
                            'verified' => '#dcfce7',
                            'pending_review' => '#fef3c7',
                            'rejected' => '#ffe4e6',
                            default => '#f1f5f9',
                        };
                        $ktpText = match($docStatus) {
                            'verified' => '#166534',
                            'pending_review' => '#92400e',
                            'rejected' => '#9f1239',
                            default => '#475569',
                        };
                    @endphp
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; background-color: {{ $ktpBg }}; color: {{ $ktpText }};">
                        KTP: {{ strtoupper(str_replace('_', ' ', $docStatus)) }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- KTP Document Image Section --}}
    <table style="width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <thead style="background-color: #f8fafc;">
            <tr>
                <th style="padding: 14px 18px; text-align: left; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0;">
                    🪪 Dokumen Identitas Pelanggan (KTP / SIM)
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 20px; text-align: center;">
                    @if($document && $documentUrl)
                        <div style="margin-bottom: 14px;">
                            <a href="{{ $documentUrl }}" target="_blank" style="display: inline-block; padding: 8px 18px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 12px; font-weight: 800;">
                                🔍 Buka Foto KTP Resolusi Penuh di Tab Baru →
                            </a>
                        </div>
                        <div style="background-color: #0f172a; padding: 12px; border-radius: 12px; display: inline-block; max-width: 100%;">
                            <img src="{{ $documentUrl }}" alt="KTP {{ $customer->name }}" style="max-height: 300px; max-width: 100%; object-fit: contain; border-radius: 8px;">
                        </div>
                    @else
                        <div style="padding: 16px; background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; color: #92400e; font-weight: 700; font-size: 13px;">
                            ⚠️ Pelanggan ini belum mengunggah foto KTP.
                        </div>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

</div>
