<?php

namespace App\Filament\Admin\Resources\VendorSupportTicketResource\Pages;

use App\Filament\Admin\Resources\VendorSupportTicketResource;
use App\Notifications\VendorSupportReplyNotification;
use Filament\Resources\Pages\EditRecord;

class EditVendorSupportTicket extends EditRecord
{
    protected static string $resource = VendorSupportTicketResource::class;

    protected function afterSave(): void
    {
        $record = $this->record;

        // Jika admin mengisi balasan, update replied_at & replied_by
        if ($record->admin_reply && !$record->replied_at) {
            $record->update([
                'replied_by' => auth('admin')->id() ?? auth()->id(),
                'replied_at' => now(),
                'status'     => 'replied',
            ]);

            // Kirim notifikasi ke vendor
            try {
                $record->vendor?->user?->notify(
                    new VendorSupportReplyNotification($record)
                );
            } catch (\Throwable) {}
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
