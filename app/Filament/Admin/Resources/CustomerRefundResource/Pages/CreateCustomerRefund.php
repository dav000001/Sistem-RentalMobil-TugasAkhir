<?php

namespace App\Filament\Admin\Resources\CustomerRefundResource\Pages;

use App\Filament\Admin\Resources\CustomerRefundResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerRefund extends CreateRecord
{
    protected static string $resource = CustomerRefundResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan customer_id terisi dari booking jika belum
        if (empty($data['customer_id']) && !empty($data['booking_id'])) {
            $booking = \App\Models\Booking::find($data['booking_id']);
            $data['customer_id'] = $booking?->customer_id;
        }
        return $data;
    }
}
