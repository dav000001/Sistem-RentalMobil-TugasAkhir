@auth
@php
    $switcher = app(\App\Services\Auth\MultiAccountSession::class);
    $count = $switcher->count(request());
    $active = $switcher->activeAccount(request());
@endphp

@if($count > 1)
    <div class="bg-yellow-50 border-b border-yellow-200 px-4 py-2">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-2 text-sm text-yellow-800">
                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>
                    Anda masuk di <strong>{{ $count }} akun</strong>.
                    Akun aktif: <strong>{{ $active['label'] ?? auth()->user()->name }}</strong>
                </span>
            </div>
            <a href="{{ route('auth.add-account') }}" class="text-xs text-yellow-700 hover:text-yellow-900 underline">
                Kelola Akun
            </a>
        </div>
    </div>
@endif
@endauth
