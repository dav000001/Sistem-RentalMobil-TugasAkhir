@props(['status'])
@php
    $s = $status instanceof \App\Enums\VendorStatus ? $status : \App\Enums\VendorStatus::from($status ?? 'pending');
    $colors = [
        'pending'       => 'yellow',
        'needs_revision' => 'orange',
        'approved'      => 'green',
        'rejected'      => 'red',
        'suspended'     => 'gray',
    ];
    $color = $colors[$s->value] ?? 'gray';
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
    {{ $s->label() }}
</span>
