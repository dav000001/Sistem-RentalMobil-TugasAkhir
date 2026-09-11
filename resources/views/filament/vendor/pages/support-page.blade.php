<x-filament-panels::page>

    @php $vendor = auth('vendor')->user()?->vendor; @endphp

    {{-- Info vendor --}}
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl mb-2">
        <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $vendor?->business_name }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth('vendor')->user()?->email }}</p>
    </div>

    {{-- Form Kirim Pesan Baru --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">📝 Kirim Pertanyaan Baru</h3>
        <form wire:submit="send">
            {{ $this->form }}

            <div class="mt-5">
                <x-filament::button type="submit" color="warning" icon="heroicon-o-paper-airplane" size="lg" class="w-full">
                    Kirim ke Admin
                </x-filament::button>
            </div>
        </form>
    </div>

    {{-- Tips --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300">
        <p class="font-semibold mb-2">💡 Tips</p>
        <ul class="space-y-1 text-xs">
            <li>• Admin akan melihat pesan Anda di menu <strong>"Pesan Vendor"</strong> di panel admin</li>
            <li>• Respons biasanya dalam 1×24 jam kerja</li>
            <li>• Semakin detail pertanyaan Anda, semakin cepat kami bisa membantu</li>
            <li>• Anda akan mendapat notifikasi 🔔 saat admin membalas</li>
        </ul>
    </div>

    {{-- ═══════════ Riwayat Tiket ═══════════ --}}
    <div class="mt-2">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">📋 Riwayat Pertanyaan</h3>

        @forelse($this->tickets as $ticket)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 mb-4 overflow-hidden">
                {{-- Header Tiket --}}
                <div class="flex items-center justify-between px-5 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ticket->subject }}</span>
                        <span class="text-xs text-gray-500 ml-2">{{ $ticket->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div>
                        @if($ticket->status === 'open')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                🟡 Menunggu Balasan
                            </span>
                        @elseif($ticket->status === 'replied')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                🟢 Dibalas
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                ⚪ Ditutup
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    {{-- Pesan Vendor --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-l-4 border-blue-500">
                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-300 mb-1">💬 Pesan Anda</p>
                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $ticket->message }}</p>
                    </div>

                    {{-- Lampiran --}}
                    @if($ticket->attachments && count($ticket->attachments) > 0)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 mb-2">📎 Lampiran ({{ count($ticket->attachments) }})</p>
                            <div class="flex gap-2 flex-wrap">
                                @foreach($ticket->attachments as $attachment)
                                    <a href="{{ asset('storage/' . $attachment) }}" target="_blank">
                                        <img
                                            src="{{ asset('storage/' . $attachment) }}"
                                            alt="Lampiran"
                                            class="w-20 h-20 object-cover rounded-lg border hover:opacity-75 transition"
                                        />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Balasan Admin --}}
                    @if($ticket->admin_reply)
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border-l-4 border-green-500">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="text-xs font-semibold text-green-700 dark:text-green-300">✅ Balasan Admin</p>
                                @if($ticket->replied_at)
                                    <span class="text-xs text-gray-500">{{ $ticket->replied_at->format('d M Y H:i') }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $ticket->admin_reply }}</p>
                        </div>
                    @else
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 text-center">
                            <p class="text-xs text-yellow-700 dark:text-yellow-400">
                                ⏳ Menunggu balasan admin...
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 text-center">
                <div class="text-4xl mb-2">📭</div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat pertanyaan. Kirim pertanyaan pertama Anda di atas!</p>
            </div>
        @endforelse
    </div>

</x-filament-panels::page>
