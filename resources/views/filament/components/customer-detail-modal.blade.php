<div class="space-y-6 text-sm p-1 font-sans">
    
    {{-- Header Banner Card --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 p-6 text-white shadow-lg">
        <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-600/30 border-2 border-blue-400/40 flex items-center justify-center text-xl font-black text-blue-300 shadow-inner">
                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                </div>
                <div>
                    <h3 class="text-xl font-black tracking-tight text-white">{{ $customer->name }}</h3>
                    <p class="text-xs font-medium text-slate-300 flex items-center gap-1.5 mt-0.5">
                        <span>✉️</span> {{ $customer->email }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                {{-- Account Status Badge --}}
                @php
                    $accStatus = $customer->account_status->value ?? $customer->account_status;
                    $accColor = match($accStatus) {
                        'active' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                        'deactivated' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
                        default => 'bg-slate-500/20 text-slate-300 border-slate-500/40',
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-black border uppercase tracking-wider {{ $accColor }}">
                    Akun: {{ $accStatus }}
                </span>

                {{-- KTP Status Badge --}}
                @php
                    $docStatus = $document ? ($document->status->value ?? $document->status) : 'none';
                    $ktpBadge = match($docStatus) {
                        'verified' => 'bg-emerald-500 text-white',
                        'pending_review' => 'bg-amber-500 text-white animate-pulse',
                        'rejected' => 'bg-rose-500 text-white',
                        default => 'bg-slate-600 text-slate-200',
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-black shadow-sm uppercase tracking-wider {{ $ktpBadge }}">
                    KTP: {{ str_replace('_', ' ', $docStatus) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Details Grid Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Phone --}}
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-1">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 block">📞 Nomor Telepon</span>
            <span class="font-extrabold text-slate-900 text-sm block">{{ $customer->phone ?? 'Belum Diisi' }}</span>
        </div>

        {{-- Registration Date --}}
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-1">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 block">📅 Tanggal Registrasi</span>
            <span class="font-extrabold text-slate-900 text-sm block">{{ $customer->created_at->format('d M Y, H:i') }} WIB</span>
        </div>

        {{-- Location / City --}}
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-1">
            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 block">📍 Kota / Provinsi</span>
            <span class="font-extrabold text-slate-900 text-sm block">{{ $customer->city ?? '-' }}, {{ $customer->province ?? '-' }}</span>
        </div>
    </div>

    {{-- Full Address Card --}}
    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-1">
        <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 block">🏠 Alamat Lengkap Rumah</span>
        <p class="font-semibold text-slate-800 text-xs leading-relaxed">{{ $customer->address ?? 'Alamat rumah belum diisi oleh pelanggan.' }}</p>
    </div>

    {{-- KTP Identity Document Section --}}
    <div class="border-t border-slate-200 pt-5 space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="font-black text-slate-900 text-base flex items-center gap-2">
                <span>🪪</span> Dokumen Identitas Pelanggan (KTP / SIM)
            </h4>
            @if($documentUrl)
                <a href="{{ $documentUrl }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-black text-blue-600 hover:text-blue-800 underline bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-200 transition-all">
                    🔍 Buka Link AWS S3 Resolusi Penuh →
                </a>
            @endif
        </div>

        @if($document && $documentUrl)
            <div class="space-y-3">
                @if(str_contains(strtolower($document->file_path), '.pdf'))
                    <div class="p-6 bg-slate-100 rounded-2xl text-center text-xs font-bold text-slate-700 border border-slate-200 space-y-2">
                        <span class="text-3xl block">📄</span>
                        <p>Dokumen KTP ini diunggah dalam format PDF.</p>
                        <a href="{{ $documentUrl }}" target="_blank" class="inline-block bg-blue-600 text-white font-extrabold px-4 py-2 rounded-xl shadow-sm">
                            Buka Dokumen PDF S3 →
                        </a>
                    </div>
                @else
                    <div class="relative rounded-2xl overflow-hidden border-2 border-slate-200 bg-slate-900 shadow-md flex items-center justify-center p-2 group">
                        <img src="{{ $documentUrl }}" alt="Dokumen KTP {{ $customer->name }}" class="max-h-80 w-auto object-contain rounded-xl transition-transform duration-300 group-hover:scale-105">
                    </div>
                @endif
            </div>
        @else
            <div class="bg-amber-50 text-amber-900 p-5 rounded-2xl text-xs font-bold border border-amber-200/80 flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h5 class="font-black text-amber-900">Belum Ada Dokumen KTP</h5>
                    <p class="font-semibold text-amber-700 mt-0.5">Pelanggan ini belum mengunggah dokumen KTP ke dalam sistem.</p>
                </div>
            </div>
        @endif
    </div>

</div>
