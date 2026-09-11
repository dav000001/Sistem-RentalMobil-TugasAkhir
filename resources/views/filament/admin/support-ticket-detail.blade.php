<div class="space-y-4 p-2">
    {{-- Info Vendor --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">📋 Informasi Vendor</h4>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div>
                <span class="text-gray-500">Nama Vendor:</span>
                <span class="font-medium ml-1">{{ $ticket->vendor?->business_name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Email:</span>
                <span class="font-medium ml-1">{{ $ticket->vendor?->user?->email ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Telepon:</span>
                <span class="font-medium ml-1">{{ $ticket->vendor?->user?->phone ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Dikirim:</span>
                <span class="font-medium ml-1">{{ $ticket->created_at->format('d M Y H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- Topik --}}
    <div>
        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">🏷️ Topik</h4>
        <p class="text-sm font-medium">{{ $ticket->subject }}</p>
    </div>

    {{-- Pesan Vendor --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-l-4 border-blue-500">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">💬 Pesan dari Vendor</h4>
        <p class="text-sm whitespace-pre-wrap">{{ $ticket->message }}</p>
    </div>

    {{-- Lampiran --}}
    @if($ticket->attachments && count($ticket->attachments) > 0)
        <div>
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">📎 Lampiran ({{ count($ticket->attachments) }} gambar)</h4>
            <div class="grid grid-cols-3 gap-2">
                @foreach($ticket->attachments as $attachment)
                    <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="block">
                        <img
                            src="{{ asset('storage/' . $attachment) }}"
                            alt="Lampiran"
                            class="w-full h-24 object-cover rounded-lg border hover:opacity-75 transition"
                        />
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Balasan Admin --}}
    @if($ticket->admin_reply)
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border-l-4 border-green-500">
            <h4 class="text-sm font-semibold text-green-700 dark:text-green-300 mb-2">
                ✅ Balasan Admin
                <span class="font-normal text-xs text-gray-500 ml-2">
                    {{ $ticket->replied_at?->format('d M Y H:i') }}
                </span>
            </h4>
            <p class="text-sm whitespace-pre-wrap">{{ $ticket->admin_reply }}</p>
        </div>
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border-l-4 border-yellow-500">
            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                ⏳ Belum ada balasan dari admin. Gunakan tombol "Balas" di tabel untuk membalas.
            </p>
        </div>
    @endif

    {{-- Status --}}
    <div class="flex items-center gap-2 text-sm">
        <span class="text-gray-500">Status:</span>
        @if($ticket->status === 'open')
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                🟡 Terbuka
            </span>
        @elseif($ticket->status === 'replied')
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                🟢 Sudah Dibalas
            </span>
        @else
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                ⚪ Ditutup
            </span>
        @endif
    </div>
</div>
