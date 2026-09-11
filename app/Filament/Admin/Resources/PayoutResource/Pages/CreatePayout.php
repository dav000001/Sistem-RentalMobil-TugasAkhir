<?php

namespace App\Filament\Admin\Resources\PayoutResource\Pages;

use App\Filament\Admin\Resources\PayoutResource;
use App\Models\Booking;
use Filament\Resources\Pages\CreateRecord;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-set periode dari booking pertama dan terakhir vendor yang sudah dibayar
        if (! empty($data['vendor_id'])) {
            $bookings = Booking::where('vendor_id', $data['vendor_id'])
                ->whereHas('payment', fn ($q) => $q->where('status', 'paid'))
                ->orderBy('created_at')
                ->get();

            if ($bookings->isNotEmpty()) {
                $data['period_start'] = $bookings->first()->created_at->toDateString();
                $data['period_end']   = $bookings->last()->created_at->toDateString();
            } else {
                // Fallback: bulan ini
                $data['period_start'] = now()->startOfMonth()->toDateString();
                $data['period_end']   = now()->endOfMonth()->toDateString();
            }
        }

        return $data;
    }
}
