@props(['subtotal', 'addonFees' => 0, 'platformFee' => 0, 'discount' => 0, 'total', 'withDriver' => false, 'driverPrice' => 0, 'days' => 1])

<div class="space-y-3">
    <div class="flex justify-between text-sm">
        <span class="text-gray-600">Sewa {{ $days }} hari</span>
        <span class="font-medium">{{ formatRupiah($subtotal) }}</span>
    </div>

    @if($withDriver && $driverPrice > 0)
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Sopir {{ $days }} hari</span>
            <span class="font-medium">{{ formatRupiah($driverPrice * $days) }}</span>
        </div>
    @endif

    @if($addonFees > 0)
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">Biaya tambahan</span>
            <span class="font-medium">{{ formatRupiah($addonFees) }}</span>
        </div>
    @endif

    @if($platformFee > 0)
        {{-- Biaya layanan tidak ditampilkan ke customer, ini urusan internal platform-vendor --}}
    @endif

    @if($discount > 0)
        <div class="flex justify-between text-sm text-green-600">
            <span>Diskon</span>
            <span class="font-medium">- {{ formatRupiah($discount) }}</span>
        </div>
    @endif

    <div class="border-t pt-3 flex justify-between">
        <span class="font-bold text-lg">Total</span>
        <span class="font-bold text-lg text-blue-600">{{ formatRupiah($total) }}</span>
    </div>
</div>
