@extends('layouts.vendor')

@section('title', 'Onboarding Vendor - ' . $vendor->business_name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Verifikasi Akun Vendor</h1>
            <p class="mt-1 text-sm text-gray-500">Lengkapi profil dan dokumen untuk mengaktifkan akun vendor Anda.</p>
        </div>

        {{-- Status Banner --}}
        @php
            $bannerColors = [
                'pending'        => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-400', 'text' => 'text-yellow-800', 'icon' => '⏳'],
                'needs_revision' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-400', 'text' => 'text-orange-800', 'icon' => '✏️'],
                'rejected'       => ['bg' => 'bg-red-50',    'border' => 'border-red-400',    'text' => 'text-red-800',    'icon' => '❌'],
                'suspended'      => ['bg' => 'bg-gray-50',   'border' => 'border-gray-400',   'text' => 'text-gray-800',   'icon' => '🔒'],
            ];
            $bc = $bannerColors[$status->value] ?? $bannerColors['pending'];
        @endphp
        <div class="{{ $bc['bg'] }} {{ $bc['border'] }} border-l-4 p-4 rounded-lg mb-6">
            <div class="flex items-center">
                <span class="text-2xl mr-3">{{ $bc['icon'] }}</span>
                <div>
                    <p class="font-semibold {{ $bc['text'] }}">Status: {{ $status->label() }}</p>
                    @if($status->value === 'pending')
                        <p class="text-sm {{ $bc['text'] }} mt-1">Dokumen Anda sedang dalam antrian review. Estimasi 1x24 jam kerja.</p>
                    @elseif($status->value === 'needs_revision')
                        <p class="text-sm {{ $bc['text'] }} mt-1">Beberapa dokumen perlu diperbaiki. Silakan upload ulang dokumen yang diminta.</p>
                    @elseif($status->value === 'rejected')
                        <p class="text-sm {{ $bc['text'] }} mt-1">Pendaftaran Anda ditolak. Hubungi support untuk informasi lebih lanjut.</p>
                    @elseif($status->value === 'suspended')
                        <p class="text-sm {{ $bc['text'] }} mt-1">Akun Anda dibekukan sementara. Hubungi support untuk informasi lebih lanjut.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        {{-- Progress Steps --}}
        <div class="flex items-center mb-8">
            @php
                $allDocsUploaded = collect($requiredTypes)->every(fn($t) => isset($documents[$t]));
                $step1Done = $vendor->business_name && $vendor->address && $vendor->city_id;
                $step2Done = $allDocsUploaded;
                $step3Done = $vendor->documents_complete;
            @endphp
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step1Done ? 'bg-green-500 text-white' : 'bg-blue-500 text-white' }} text-sm font-bold">
                    {{ $step1Done ? '✓' : '1' }}
                </div>
                <span class="ml-2 text-sm font-medium {{ $step1Done ? 'text-green-700' : 'text-blue-700' }}">Profil Bisnis</span>
            </div>
            <div class="flex-1 h-0.5 mx-4 {{ $step1Done ? 'bg-green-300' : 'bg-gray-200' }}"></div>
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step2Done ? 'bg-green-500 text-white' : ($step1Done ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-600') }} text-sm font-bold">
                    {{ $step2Done ? '✓' : '2' }}
                </div>
                <span class="ml-2 text-sm font-medium {{ $step2Done ? 'text-green-700' : ($step1Done ? 'text-blue-700' : 'text-gray-500') }}">Dokumen</span>
            </div>
            <div class="flex-1 h-0.5 mx-4 {{ $step2Done ? 'bg-green-300' : 'bg-gray-200' }}"></div>
            <div class="flex items-center">
                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step3Done ? 'bg-green-500 text-white' : ($step2Done ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-600') }} text-sm font-bold">
                    {{ $step3Done ? '✓' : '3' }}
                </div>
                <span class="ml-2 text-sm font-medium {{ $step3Done ? 'text-green-700' : ($step2Done ? 'text-blue-700' : 'text-gray-500') }}">Submit</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- Step 1: Profile Form --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">1. Profil Bisnis</h2>
                        @if($step1Done)
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Lengkap</span>
                        @endif
                    </div>
                    <div class="p-6">
                        <form action="{{ route('vendor.onboarding.profile') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Bisnis</label>
                                <div class="flex items-center gap-3 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg">
                                    <span class="text-lg">
                                        @if($businessType === 'perorangan') 👤
                                        @elseif($businessType === 'pt') 🏢
                                        @elseif($businessType === 'cv') 🏬
                                        @elseif($businessType === 'komunitas') 👥
                                        @else 🏢
                                        @endif
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $businessTypeLabel }}</p>
                                        <p class="text-xs text-gray-400">Diisi saat pendaftaran · tidak dapat diubah di sini</p>
                                    </div>
                                    <span class="ml-auto text-xs px-2 py-1 rounded-full font-medium
                                        {{ $businessType === 'perorangan' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $businessType === 'perorangan' ? 'SIUP Opsional' : 'SIUP Wajib' }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bisnis <span class="text-red-500">*</span></label>
                                <input type="text" name="business_name" value="{{ old('business_name', $vendor->business_name) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_name') border-red-400 @enderror"
                                    required>
                                @error('business_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                                <textarea name="address" rows="3"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('address') border-red-400 @enderror"
                                    required>{{ old('address', $vendor->address) }}</textarea>
                                @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kota <span class="text-red-500">*</span></label>
                                <select name="city_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('city_id') border-red-400 @enderror"
                                    required>
                                    <option value="">-- Pilih Kota --</option>
                                    @foreach(\App\Models\City::orderBy('name')->get() as $city)
                                        <option value="{{ $city->id }}" {{ old('city_id', $vendor->city_id) == $city->id ? 'selected' : '' }}>
                                            {{ $city->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('city_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-sm font-medium text-gray-700 mb-3">Informasi Bank (untuk pencairan dana)</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Nama Bank</label>
                                        <input type="text" name="bank_name" value="{{ old('bank_name', $vendor->bank_name) }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="BCA, Mandiri, dll">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">No. Rekening</label>
                                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $vendor->bank_account_number) }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Nama Pemilik Rekening</label>
                                        <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $vendor->bank_account_name) }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                                    Simpan Profil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Step 2: Documents --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">2. Upload Dokumen</h2>
                        @if($step2Done)
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Semua Terupload</span>
                        @endif
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-4">Upload dokumen berikut dalam format JPG, PNG, atau PDF (maks. 3MB per file).</p>
                        @php
                            $docLabels = [
                                'ktp'    => ['label' => 'KTP',          'desc' => 'Kartu Tanda Penduduk pemilik usaha'],
                                'npwp'   => ['label' => 'NPWP',         'desc' => 'Nomor Pokok Wajib Pajak'],
                                'siup'   => ['label' => 'SIUP / NIB',   'desc' => 'Surat Izin Usaha Perdagangan atau NIB dari OSS'],
                                'selfie' => ['label' => 'Selfie + KTP', 'desc' => 'Foto selfie sambil memegang KTP'],
                            ];
                            $statusColors = [
                                'pending'  => 'text-yellow-600 bg-yellow-50',
                                'approved' => 'text-green-600 bg-green-50',
                                'rejected' => 'text-red-600 bg-red-50',
                            ];
                            $statusLabels = [
                                'pending'  => 'Menunggu Review',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ];
                        @endphp

                        {{-- Dokumen Wajib --}}
                        <div class="space-y-4">
                            @foreach($requiredTypes as $type)
                                @php $doc = $documents[$type] ?? null; @endphp
                                <div class="border border-gray-200 rounded-lg p-4 {{ $doc && $doc->status === 'approved' ? 'border-green-200 bg-green-50/30' : ($doc && $doc->status === 'rejected' ? 'border-red-200 bg-red-50/30' : '') }}">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium text-gray-900 text-sm">{{ $docLabels[$type]['label'] }}</p>
                                                <span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium">Wajib</span>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ $docLabels[$type]['desc'] }}</p>
                                        </div>
                                        @if($doc)
                                            <span class="text-xs px-2 py-1 rounded-full font-medium {{ $statusColors[$doc->status] ?? 'text-gray-600 bg-gray-50' }}">
                                                {{ $statusLabels[$doc->status] ?? $doc->status }}
                                            </span>
                                        @else
                                            <span class="text-xs px-2 py-1 rounded-full font-medium text-gray-500 bg-gray-100">Belum Upload</span>
                                        @endif
                                    </div>

                                    @if($doc && $doc->status === 'rejected' && $doc->rejection_reason)
                                        <div class="bg-red-50 border border-red-200 rounded p-2 mb-3">
                                            <p class="text-xs text-red-700"><strong>Alasan ditolak:</strong> {{ $doc->rejection_reason }}</p>
                                        </div>
                                    @endif

                                    @if($doc)
                                        <p class="text-xs text-gray-500 mb-2">
                                            <span class="font-medium">File:</span> {{ $doc->original_name }}
                                            @if($doc->size)
                                                ({{ number_format($doc->size / 1024, 1) }} KB)
                                            @endif
                                        </p>
                                    @endif

                                    @if(!$doc || $doc->status !== 'approved')
                                        <form action="{{ route('vendor.onboarding.documents') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="type" value="{{ $type }}">
                                            <div class="flex items-center gap-2">
                                                <input type="file" name="file" accept=".jpg,.jpeg,.png,.pdf"
                                                    class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                                    required>
                                                <button type="submit" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded transition">
                                                    Upload
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Dokumen Opsional (hanya tampil jika ada) --}}
                        @if(count($optionalTypes) > 0)
                            <div class="mt-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <h3 class="text-sm font-semibold text-gray-700">Dokumen Tambahan (Opsional)</h3>
                                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Tidak diwajibkan</span>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">Jika Anda memiliki dokumen berikut, silakan upload untuk memperkuat verifikasi akun Anda.</p>
                                <div class="space-y-4">
                                    @foreach($optionalTypes as $type)
                                        @php $doc = $documents[$type] ?? null; @endphp
                                        <div class="border border-dashed border-gray-300 rounded-lg p-4 {{ $doc && $doc->status === 'approved' ? 'border-green-200 bg-green-50/30' : ($doc && $doc->status === 'rejected' ? 'border-red-200 bg-red-50/30' : 'bg-gray-50/50') }}">
                                            <div class="flex items-start justify-between mb-3">
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <p class="font-medium text-gray-900 text-sm">{{ $docLabels[$type]['label'] }}</p>
                                                        <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium">Opsional</span>
                                                    </div>
                                                    <p class="text-xs text-gray-500">{{ $docLabels[$type]['desc'] }}</p>
                                                </div>
                                                @if($doc)
                                                    <span class="text-xs px-2 py-1 rounded-full font-medium {{ $statusColors[$doc->status] ?? 'text-gray-600 bg-gray-50' }}">
                                                        {{ $statusLabels[$doc->status] ?? $doc->status }}
                                                    </span>
                                                @else
                                                    <span class="text-xs px-2 py-1 rounded-full font-medium text-gray-400 bg-gray-100">Belum Upload</span>
                                                @endif
                                            </div>

                                            @if($doc && $doc->status === 'rejected' && $doc->rejection_reason)
                                                <div class="bg-red-50 border border-red-200 rounded p-2 mb-3">
                                                    <p class="text-xs text-red-700"><strong>Alasan ditolak:</strong> {{ $doc->rejection_reason }}</p>
                                                </div>
                                            @endif

                                            @if($doc)
                                                <p class="text-xs text-gray-500 mb-2">
                                                    <span class="font-medium">File:</span> {{ $doc->original_name }}
                                                    @if($doc->size)
                                                        ({{ number_format($doc->size / 1024, 1) }} KB)
                                                    @endif
                                                </p>
                                            @endif

                                            @if(!$doc || $doc->status !== 'approved')
                                                <form action="{{ route('vendor.onboarding.documents') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="type" value="{{ $type }}">
                                                    <div class="flex items-center gap-2">
                                                        <input type="file" name="file" accept=".jpg,.jpeg,.png,.pdf"
                                                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-600 hover:file:bg-gray-200 cursor-pointer">
                                                        <button type="submit" class="shrink-0 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium px-3 py-1.5 rounded transition">
                                                            Upload
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Step 3: Submit --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">3. Submit untuk Review</h2>
                    </div>
                    <div class="p-6">
                        @if($vendor->documents_complete && $status->value === 'pending')
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <p class="text-sm text-blue-800">
                                    ✅ Dokumen Anda sudah disubmit dan sedang dalam antrian review admin. Estimasi 1x24 jam kerja.
                                </p>
                            </div>
                        @elseif($allDocsUploaded)
                            <p class="text-sm text-gray-600 mb-4">
                                Semua dokumen sudah diupload. Klik tombol di bawah untuk mengirimkan ke admin untuk direview.
                            </p>
                            <form action="{{ route('vendor.onboarding.submit') }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition text-sm"
                                    onclick="return confirm('Yakin ingin submit dokumen untuk review?')">
                                    🚀 Submit Dokumen untuk Review
                                </button>
                            </form>
                        @else
                            <p class="text-sm text-gray-500">
                                Upload semua dokumen yang diperlukan terlebih dahulu sebelum bisa submit.
                            </p>
                            <button disabled class="mt-3 w-full bg-gray-200 text-gray-400 font-semibold py-3 px-6 rounded-lg text-sm cursor-not-allowed">
                                Submit Dokumen untuk Review
                            </button>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Sidebar: Status Timeline --}}
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">Riwayat Status</h3>
                    </div>
                    <div class="p-5">
                        @if($statusLogs->isEmpty())
                            <p class="text-xs text-gray-400 text-center py-4">Belum ada riwayat status.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($statusLogs as $log)
                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-center">
                                            <div class="w-2 h-2 rounded-full bg-blue-400 mt-1 shrink-0"></div>
                                            @if(!$loop->last)
                                                <div class="w-0.5 bg-gray-200 flex-1 mt-1"></div>
                                            @endif
                                        </div>
                                        <div class="pb-3 min-w-0">
                                            <p class="text-xs font-medium text-gray-800">
                                                @if($log->from_status)
                                                    {{ \App\Enums\VendorStatus::tryFrom($log->from_status)?->label() ?? $log->from_status }}
                                                    → 
                                                @endif
                                                {{ \App\Enums\VendorStatus::tryFrom($log->to_status)?->label() ?? $log->to_status }}
                                            </p>
                                            @if($log->reason)
                                                <p class="text-xs text-gray-500 mt-0.5 break-words">{{ $log->reason }}</p>
                                            @endif
                                            <p class="text-xs text-gray-400 mt-1">
                                                {{ $log->created_at->diffForHumans() }}
                                                @if($log->actor)
                                                    · {{ $log->actor->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Help Box --}}
                <div class="bg-blue-50 rounded-xl border border-blue-100 p-5">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">Butuh Bantuan?</h3>
                    <p class="text-xs text-blue-700 mb-3">Jika ada pertanyaan tentang proses verifikasi, hubungi tim support kami.</p>
                    <a href="https://wa.me/6281234567890" target="_blank"
                        class="inline-flex items-center text-xs font-medium text-blue-700 hover:text-blue-900">
                        💬 Chat via WhatsApp
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
