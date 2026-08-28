<div class="space-y-4 text-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div>
            <span class="text-xs font-bold text-slate-400 block">Nama Lengkap</span>
            <span class="font-extrabold text-slate-900 text-base">{{ $customer->name }}</span>
        </div>
        <div>
            <span class="text-xs font-bold text-slate-400 block">Alamat Email</span>
            <span class="font-bold text-slate-800">{{ $customer->email }}</span>
        </div>
        <div>
            <span class="text-xs font-bold text-slate-400 block">Nomor Telepon</span>
            <span class="font-bold text-slate-800">{{ $customer->phone ?? '-' }}</span>
        </div>
        <div>
            <span class="text-xs font-bold text-slate-400 block">Tanggal Registrasi</span>
            <span class="font-bold text-slate-800">{{ $customer->created_at->format('d M Y, H:i') }}</span>
        </div>
        <div class="md:col-span-2">
            <span class="text-xs font-bold text-slate-400 block">Alamat Rumah</span>
            <span class="font-medium text-slate-800">{{ $customer->address ?? '-' }}, {{ $customer->city ?? '-' }}, {{ $customer->province ?? '-' }}</span>
        </div>
    </div>

    <div class="border-t border-slate-200 pt-4">
        <h4 class="font-extrabold text-slate-900 text-sm mb-3">Dokumen Identitas (KTP / SIM)</h4>

        @if($document && $documentUrl)
            <div class="space-y-3">
                <div class="flex items-center justify-between text-xs bg-blue-50 p-3 rounded-lg border border-blue-200">
                    <span class="font-bold text-blue-900">Status Identitas: {{ strtoupper($document->status->value ?? $document->status) }}</span>
                    <a href="{{ $documentUrl }}" target="_blank" class="font-bold text-blue-600 underline hover:text-blue-800">
                        🔍 Buka Foto KTP di Tab Baru (AWS S3 Link) →
                    </a>
                </div>

                @if(str_contains(strtolower($document->file_path), '.pdf'))
                    <div class="p-4 bg-slate-100 rounded-xl text-center text-xs font-bold text-slate-600">
                        📄 File berformat PDF. Klik link di atas untuk membuka dokumen PDF dari AWS S3.
                    </div>
                @else
                    <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-h-72 bg-black flex items-center justify-center">
                        <img src="{{ $documentUrl }}" alt="Dokumen KTP {{ $customer->name }}" class="max-h-72 object-contain">
                    </div>
                @endif
            </div>
        @else
            <div class="bg-amber-50 text-amber-800 p-4 rounded-xl text-xs font-bold border border-amber-200">
                ⚠️ Pelanggan ini belum mengunggah dokumen identitas (KTP).
            </div>
        @endif
    </div>
</div>
