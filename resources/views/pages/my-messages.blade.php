@extends('layouts.app')
@section('title', 'Pesan Saya')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Pesan Saya</h1>
    <p class="text-gray-500 text-sm mb-6">Riwayat pesan yang Anda kirim ke admin beserta balasannya.</p>

    @if($messages->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <p class="text-4xl mb-3">📭</p>
            <p class="text-gray-500">Belum ada pesan. <a href="{{ route('contact') }}" class="text-blue-600 hover:underline">Kirim pesan ke admin</a></p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($messages as $msg)
                <div class="bg-white rounded-2xl border {{ $msg->hasReply() && !$msg->is_read_by_customer ? 'border-blue-400 shadow-md' : 'border-gray-200' }} overflow-hidden">
                    {{-- Header pesan --}}
                    <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-900 text-sm">{{ $msg->subject }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $msg->created_at->format('d M Y H:i') }}</p>
                        </div>
                        @if($msg->hasReply())
                            <span class="flex-shrink-0 bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                ✅ Dibalas
                            </span>
                        @else
                            <span class="flex-shrink-0 bg-yellow-100 text-yellow-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                ⏳ Menunggu
                            </span>
                        @endif
                    </div>

                    {{-- Pesan customer --}}
                    <div class="px-5 py-4 bg-gray-50">
                        <p class="text-xs text-gray-500 mb-1 font-medium">Pesan Anda:</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $msg->message }}</p>
                    </div>

                    {{-- Balasan admin --}}
                    @if($msg->hasReply())
                        <div class="px-5 py-4 bg-blue-50 border-t border-blue-100">
                            <p class="text-xs text-blue-600 mb-1 font-medium">
                                💬 Balasan Admin
                                @if($msg->replied_at)
                                    <span class="text-gray-400 font-normal">— {{ $msg->replied_at->format('d M Y H:i') }}</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-800 whitespace-pre-line">{{ $msg->admin_reply }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6 text-center">
        <a href="{{ route('contact') }}" class="text-sm text-blue-600 hover:underline">+ Kirim pesan baru</a>
    </div>
</div>
@endsection
