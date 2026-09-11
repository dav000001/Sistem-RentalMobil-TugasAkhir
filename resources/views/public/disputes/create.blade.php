@extends('layouts.app')

@section('title', 'Ajukan Sengketa — ' . $booking->code)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <div class="mb-6">
        <a href="{{ route('bookings.show', $booking->code) }}" class="text-blue-600 hover:underline text-sm">
            ← Kembali ke Pesanan
        </a>
        <h1 class="text-2xl font-bold mt-2">Ajukan Sengketa</h1>
        <p class="text-gray-600 text-sm mt-1">
            Pesanan: <strong>{{ $booking->code }}</strong>
            — {{ $booking->car->brand }} {{ $booking->car->model }}
            | Vendor: {{ $booking->vendor?->business_name ?? '—' }}
        </p>
    </div>

    {{-- Info banner --}}
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 flex items-start gap-3">
        <span class="text-2xl leading-none mt-0.5">⚠️</span>
        <div class="text-sm text-amber-800">
            <p class="font-semibold mb-1">Penting — Baca sebelum mengajukan sengketa</p>
            <ul class="list-disc list-inside space-y-1 text-amber-700">
                <li>Sengketa akan mengubah status pesanan menjadi <strong>"Sengketa"</strong> dan proses payout vendor akan ditangguhkan.</li>
                <li>Tim admin akan meninjau dalam <strong>1–3 hari kerja</strong>.</li>
                <li>Keputusan admin bersifat final dan mengikat kedua pihak.</li>
                <li>Sertakan bukti yang relevan untuk mempercepat proses penyelesaian.</li>
            </ul>
        </div>
    </div>

    {{-- Error summary --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('disputes.store', $booking) }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

        {{-- Info booking --}}
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h2 class="font-semibold text-base mb-3">📋 Info Pesanan</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-gray-500">Kode Booking</p>
                    <p class="font-semibold">{{ $booking->code }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Mobil</p>
                    <p class="font-semibold">{{ $booking->car->brand }} {{ $booking->car->model }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Periode Sewa</p>
                    <p class="font-semibold">
                        {{ $booking->start_at->format('d M Y') }} → {{ $booking->end_at->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500">Total Pembayaran</p>
                    <p class="font-semibold text-blue-700">{{ formatRupiah($booking->total) }}</p>
                </div>
            </div>
        </div>

        {{-- Alasan sengketa --}}
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h2 class="font-semibold text-base mb-1">1. Jelaskan Masalah Anda <span class="text-red-500">*</span></h2>
            <p class="text-gray-500 text-xs mb-3">
                Ceritakan permasalahan secara jelas dan kronologis. Minimal 30 karakter.
            </p>
            <textarea name="reason"
                      rows="7"
                      placeholder="Contoh: Pada tanggal 10 Juni 2025, saya mengambil mobil dan menemukan kondisi AC tidak berfungsi meskipun sudah dijanjikan berfungsi normal. Vendor tidak merespons keluhan saya dan tidak memberikan solusi. Saya meminta refund sebagian karena ketidaknyamanan ini..."
                      class="w-full border rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none
                             @error('reason') border-red-500 @else border-gray-300 @enderror"
                      required
                      minlength="30"
                      maxlength="5000">{{ old('reason') }}</textarea>
            <div class="flex justify-between mt-1">
                @error('reason')
                    <p class="text-red-600 text-xs">{{ $message }}</p>
                @else
                    <p class="text-gray-400 text-xs">Minimal 30 karakter. Semakin detail semakin cepat ditangani.</p>
                @enderror
                <p class="text-gray-400 text-xs" id="reason-count">0 / 5000</p>
            </div>
        </div>

        {{-- Bukti pendukung --}}
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h2 class="font-semibold text-base mb-1">
                2. Bukti Pendukung
                <span class="text-gray-400 font-normal text-sm">(opsional, maks. 5 file)</span>
            </h2>
            <p class="text-gray-500 text-xs mb-3">
                Upload foto kerusakan, screenshot chat, atau dokumen pendukung lainnya.
                Format: JPG, PNG, PDF — maks. 5 MB per file.
            </p>

            <div id="evidence-dropzone"
                 class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm text-gray-600 mb-1">Klik atau seret file ke sini</p>
                <p class="text-xs text-gray-400">JPG, PNG, PDF — maks. 5 MB per file, maks. 5 file</p>
                <input type="file"
                       name="evidence[]"
                       id="evidence-input"
                       multiple
                       accept="image/jpeg,image/png,image/jpg,application/pdf"
                       class="hidden">
            </div>

            {{-- Preview area --}}
            <div id="evidence-preview" class="mt-3 flex flex-wrap gap-2"></div>

            @error('evidence')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
            @error('evidence.*')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('bookings.show', $booking->code) }}"
               class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                Batal
            </a>
            <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold transition
                           flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Ajukan Sengketa
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Character counter
    const reasonTextarea = document.querySelector('textarea[name="reason"]');
    const reasonCount    = document.getElementById('reason-count');
    if (reasonTextarea && reasonCount) {
        const update = () => reasonCount.textContent = reasonTextarea.value.length + ' / 5000';
        reasonTextarea.addEventListener('input', update);
        update();
    }

    // Evidence file upload
    const dropzone  = document.getElementById('evidence-dropzone');
    const input     = document.getElementById('evidence-input');
    const preview   = document.getElementById('evidence-preview');
    const MAX_FILES = 5;
    const MAX_SIZE  = 5 * 1024 * 1024; // 5 MB

    dropzone.addEventListener('click', () => input.click());

    dropzone.addEventListener('dragover', e => {
        e.preventDefault();
        dropzone.classList.add('border-blue-400', 'bg-blue-50');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-blue-400', 'bg-blue-50');
    });
    dropzone.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('border-blue-400', 'bg-blue-50');
        handleFiles(e.dataTransfer.files);
    });

    input.addEventListener('change', () => handleFiles(input.files));

    let selectedFiles = [];

    function handleFiles(fileList) {
        const newFiles = Array.from(fileList);
        const combined = [...selectedFiles, ...newFiles].slice(0, MAX_FILES);

        // Validate sizes
        const invalid = combined.filter(f => f.size > MAX_SIZE);
        if (invalid.length) {
            alert('File ' + invalid.map(f => f.name).join(', ') + ' melebihi batas 5 MB.');
            return;
        }

        selectedFiles = combined;
        renderPreviews();
        syncInputFiles();
    }

    function renderPreviews() {
        preview.innerHTML = '';
        selectedFiles.forEach((file, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'relative group';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'w-20 h-20 object-cover rounded-lg border border-gray-200';
                img.src = URL.createObjectURL(file);
                wrap.appendChild(img);
            } else {
                const doc = document.createElement('div');
                doc.className = 'w-20 h-20 flex flex-col items-center justify-center bg-gray-100 rounded-lg border border-gray-200 text-xs text-gray-600 gap-1';
                doc.innerHTML = '<span class="text-2xl">📄</span><span class="truncate max-w-full px-1">' + file.name.substring(0, 10) + '…</span>';
                wrap.appendChild(doc);
            }

            // Remove button
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition leading-none';
            btn.textContent = '×';
            btn.addEventListener('click', () => {
                selectedFiles.splice(idx, 1);
                renderPreviews();
                syncInputFiles();
            });
            wrap.appendChild(btn);

            preview.appendChild(wrap);
        });

        // Update dropzone hint
        const hint = dropzone.querySelector('p.text-sm');
        if (hint) {
            hint.textContent = selectedFiles.length > 0
                ? selectedFiles.length + ' file dipilih (maks. ' + MAX_FILES + ')'
                : 'Klik atau seret file ke sini';
        }
    }

    function syncInputFiles() {
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }
</script>
@endpush
@endsection
