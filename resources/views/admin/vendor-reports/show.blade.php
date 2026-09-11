@extends('layouts.admin')
@section('title', 'Laporan Vendor ' . $complaint->reference)

@section('content')
<div>
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('admin.vendor-reports.index') }}" class="text-blue-600 hover:underline text-sm">← Daftar Laporan Vendor</a>
        <div class="flex flex-wrap items-center gap-3 mt-2">
            <h1 class="text-2xl font-bold">{{ $complaint->reference }}</h1>
            @php $color = $complaint->statusColor(); @endphp
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-{{ $color }}-100 text-{{ $color }}-700">
                {{ $complaint->statusLabel() }}
            </span>
            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-700">
                🏪 Dilaporkan oleh Vendor
            </span>
        </div>
        <p class="text-gray-500 text-sm mt-1">
            Diajukan {{ $complaint->created_at->format('d M Y H:i') }} · Kategori: {{ $complaint->category?->name }}
        </p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Konten Utama --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Detail Laporan --}}
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="font-semibold text-lg mb-3">Detail Laporan Vendor</h2>
                <p class="text-gray-700 text-sm leading-relaxed">{{ $complaint->description }}</p>

                {{-- Estimasi biaya dari vendor --}}
                @if($complaint->demanded_refund_amount > 0)
                    <div class="mt-4 pt-4 border-t">
                        <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3">
                            <p class="text-xs text-gray-500 mb-0.5">💰 Estimasi Biaya Perbaikan (dari Vendor)</p>
                            <p class="text-lg font-bold text-orange-700">
                                Rp {{ number_format($complaint->demanded_refund_amount, 0, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $complaint->customer_demand_note }}</p>
                        </div>
                    </div>
                @endif

                {{-- Bukti --}}
                @if($complaint->attachments && count($complaint->attachments) > 0)
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-sm font-medium text-gray-600 mb-2">📎 Bukti yang Dilampirkan</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($complaint->attachments as $path)
                                @php
                                    $url = asset('storage/' . ltrim($path, '/'));
                                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                                @endphp
                                @if($isImg)
                                    <a href="{{ $url }}" target="_blank">
                                        <img src="{{ $url }}" alt="Bukti"
                                             class="h-28 w-auto rounded-lg border border-gray-200 object-cover hover:opacity-80 transition cursor-pointer">
                                    </a>
                                @else
                                    <a href="{{ $url }}" target="_blank"
                                       class="flex items-center gap-1.5 px-3 py-2 bg-gray-100 rounded-lg text-sm text-blue-600 hover:bg-gray-200">
                                        📄 {{ basename($path) }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Info Pihak --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3 text-sm">🏪 Vendor Pelapor</h3>
                    <p class="font-medium text-sm">{{ $complaint->vendor?->business_name ?? $complaint->reporter?->name }}</p>
                    <p class="text-gray-500 text-xs">{{ $complaint->reporter?->email }}</p>
                    <p class="text-gray-500 text-xs">{{ $complaint->reporter?->phone }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3 text-sm">👤 Customer Terkait</h3>
                    @php $cust = $complaint->booking?->customer; @endphp
                    <p class="font-medium text-sm">{{ $cust?->full_name ?? '-' }}</p>
                    <p class="text-gray-500 text-xs">{{ $cust?->user?->email ?? '-' }}</p>
                    <p class="text-gray-500 text-xs">{{ $cust?->user?->phone ?? '-' }}</p>
                </div>
            </div>

            {{-- Info Pesanan --}}
            @if($complaint->booking)
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3 text-sm">Pesanan Terkait</h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-500 text-xs">Kode Booking</p>
                            <p class="font-medium">{{ $complaint->booking->code }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Mobil</p>
                            <p class="font-medium">{{ $complaint->booking->car?->brand }} {{ $complaint->booking->car?->model }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Periode Sewa</p>
                            <p class="font-medium">
                                {{ $complaint->booking->start_at?->format('d M Y') }} – {{ $complaint->booking->end_at?->format('d M Y') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs">Total Pembayaran</p>
                            <p class="font-medium">Rp {{ number_format($complaint->booking->total, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Keputusan --}}
            @if($complaint->resolution)
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h2 class="font-semibold text-lg mb-3 text-green-800">✅ Keputusan Admin</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Keputusan</span>
                            <span class="font-semibold">{{ $complaint->resolution->decisionLabel() }}</span>
                        </div>
                        <div class="pt-2 border-t">
                            <p class="text-gray-600 text-xs mb-1">Alasan</p>
                            <p>{{ $complaint->resolution->reasoning }}</p>
                        </div>
                        <p class="text-gray-400 text-xs">
                            Diputuskan oleh {{ $complaint->resolution->admin?->name }}
                            · {{ $complaint->resolution->created_at->format('d M Y H:i') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Riwayat Komunikasi --}}
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="font-semibold text-lg mb-4">Riwayat Komunikasi</h2>
                @php $publicResponses = $complaint->responses->where('visibility', 'public'); @endphp
                @if($publicResponses->isEmpty())
                    <p class="text-gray-500 text-sm">Belum ada respons.</p>
                @else
                    <div class="space-y-4">
                        @foreach($publicResponses as $response)
                            <div class="border rounded-lg p-4 bg-gray-50">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                                            @if($response->author_role === 'admin') bg-purple-100 text-purple-700
                                            @elseif($response->author_role === 'vendor') bg-orange-100 text-orange-700
                                            @else bg-blue-100 text-blue-700 @endif">
                                            {{ $response->authorRoleLabel() }}
                                        </span>
                                        <span class="text-sm font-medium">{{ $response->author?->name }}</span>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $response->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <p class="text-sm text-gray-700">{{ $response->message }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($complaint->isOpen())
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="font-medium text-sm mb-1">Kirim Respons ke Vendor</h3>
                        <p class="text-xs text-gray-500 mb-3">Respons akan terlihat oleh vendor dan customer terkait.</p>
                        <form method="POST" action="{{ route('admin.vendor-reports.respond', $complaint->id) }}">
                            @csrf
                            <textarea name="message" rows="3" required minlength="5"
                                      placeholder="Tulis respons untuk vendor..."
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                            <div class="flex items-center justify-between mt-2">
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="visibility" value="internal_admin" class="rounded">
                                    <span>Internal (hanya admin)</span>
                                </label>
                                <button type="submit"
                                        class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">
                                    Kirim Respons
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Form Tagihan Kompensasi — di konten utama agar tidak terpotong --}}
            @php
                $existingCharge = \App\Models\CompensationCharge::where('booking_id', $complaint->booking_id)
                    ->whereIn('status', ['pending', 'paid'])->first();
                $estimatedCost  = $complaint->demanded_refund_amount ?? 0;
            @endphp
            @if($complaint->isOpen())
                <div class="bg-white rounded-xl border-2 border-orange-200 p-6">
                    <h2 class="font-semibold text-lg mb-1 text-orange-700">💰 Tagih Kompensasi ke Customer</h2>
                    <p class="text-xs text-gray-500 mb-4">Langkah 2 dari panduan — buat tagihan sebelum menyelesaikan laporan.</p>

                    @if($existingCharge)
                        <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 text-sm text-orange-700">
                            @if($existingCharge->status === 'paid')
                                ✅ Tagihan kompensasi <strong>Rp {{ number_format($existingCharge->amount, 0, ',', '.') }}</strong> sudah dibayar customer.
                            @else
                                ⏳ Tagihan kompensasi <strong>Rp {{ number_format($existingCharge->amount, 0, ',', '.') }}</strong> sudah dikirim — menunggu pembayaran customer.
                            @endif
                        </div>
                    @else
                        @if($estimatedCost > 0)
                            <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3 mb-4 text-sm text-orange-700">
                                🏪 Estimasi biaya dari vendor: <strong>Rp {{ number_format($estimatedCost, 0, ',', '.') }}</strong>
                                — sudah diisi otomatis di bawah. Sesuaikan jika perlu.
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.compensation.store') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $complaint->booking_id }}">
                            <input type="hidden" name="complaint_id" value="{{ $complaint->id }}">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Jumlah Kompensasi (Rp) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="amount" min="1" required
                                           value="{{ $estimatedCost > 0 ? $estimatedCost : '' }}"
                                           placeholder="Contoh: 150000"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Batas Pembayaran</label>
                                    <input type="date" name="due_date"
                                           value="{{ now()->addDays(7)->format('Y-m-d') }}"
                                           min="{{ now()->addDay()->format('Y-m-d') }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Alasan Tagihan <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="reason" required
                                       placeholder="Contoh: Ganti rugi kerusakan aksesori interior"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                            </div>
                            <button type="submit"
                                    class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 rounded-lg text-sm font-semibold transition">
                                💰 Kirim Tagihan ke Customer
                            </button>
                        </form>
                    @endif
                </div>
            @endif

        </div>

        {{-- Sidebar Aksi --}}
        <div class="space-y-4">

            {{-- Petunjuk Alur --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 text-sm mb-2">💡 Panduan Penanganan</h3>
                <ol class="text-xs text-blue-700 space-y-1.5 list-none">
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-blue-500 flex-shrink-0">1.</span>
                        <span>Tinjau detail laporan dan bukti dari vendor.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-blue-500 flex-shrink-0">2.</span>
                        <span>Jika customer terbukti bersalah → klik <strong>"+ Buat Tagihan"</strong> untuk menagih kompensasi ke customer.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-blue-500 flex-shrink-0">3.</span>
                        <span>Setelah customer membayar atau keputusan sudah jelas → klik <strong>"✅ Selesaikan"</strong> untuk menutup laporan.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-blue-500 flex-shrink-0">4.</span>
                        <span>Jika laporan tidak valid → klik <strong>"✕ Tolak"</strong> dengan alasan yang jelas.</span>
                    </li>
                </ol>
            </div>

            @if($complaint->isOpen())
                {{-- Selesaikan --}}
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3">Selesaikan Laporan</h3>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 mb-3 text-xs text-orange-700">
                        🏪 Laporan dari vendor. Jika customer terbukti bersalah, gunakan tombol Tagih Kompensasi di bawah.
                    </div>
                    <form method="POST" action="{{ route('admin.vendor-reports.resolve', $complaint->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Keputusan *</label>
                            <select name="decision" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih keputusan...</option>
                                <option value="warning_vendor">Peringatan ke Customer (dicatat)</option>
                                <option value="mutual_agreement">Kesepakatan Bersama</option>
                                <option value="escalated_legal">Eskalasi Legal</option>
                                <option value="escalated_insurance">Eskalasi Asuransi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Alasan Keputusan *</label>
                            <textarea name="reasoning" rows="3" required minlength="20"
                                      placeholder="Jelaskan alasan keputusan..."
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                        <button type="submit"
                                class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 text-sm font-medium">
                            ✅ Selesaikan
                        </button>
                    </form>
                </div>

                {{-- Tolak --}}
                <div class="bg-white rounded-lg shadow-sm border p-5">
                    <h3 class="font-semibold mb-3 text-red-700">Tolak Laporan</h3>
                    <form method="POST" action="{{ route('admin.vendor-reports.reject', $complaint->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Alasan Penolakan *</label>
                            <textarea name="reason" rows="3" required minlength="10"
                                      placeholder="Jelaskan alasan penolakan..."
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        </div>
                        <button type="submit"
                                class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 text-sm font-medium"
                                onclick="return confirm('Yakin ingin menolak laporan ini?')">
                            ✕ Tolak Laporan
                        </button>
                    </form>
                </div>
            @endif

            {{-- Log --}}
            <div class="bg-white rounded-lg shadow-sm border p-5">
                <h3 class="font-semibold mb-4 text-sm">Log Aktivitas</h3>
                <div class="space-y-3">
                    @foreach($complaint->logs as $log)
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full bg-orange-400 mt-1.5 flex-shrink-0"></div>
                            <div>
                                <p class="text-sm font-medium">{{ $log->actionLabel() }}</p>
                                <p class="text-xs text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</p>
                                @if($log->actor)
                                    <p class="text-xs text-gray-400">{{ $log->actor->name }} ({{ $log->actor_role }})</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
