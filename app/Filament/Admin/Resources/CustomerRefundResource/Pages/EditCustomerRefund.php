<?php

namespace App\Filament\Admin\Resources\CustomerRefundResource\Pages;

use App\Filament\Admin\Resources\CustomerRefundResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomerRefund extends EditRecord
{
    protected static string $resource = CustomerRefundResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Sembunyikan tombol Delete bawaan
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Auto-fill rekening customer saat form edit dibuka,
     * jika field masih kosong di record.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ambil data rekening dari profil customer jika field di record masih kosong
        if (empty($data['bank_name']) || empty($data['bank_account_no'])) {
            $customer = $this->record->customer;
            if ($customer) {
                $data['bank_name']         = $data['bank_name']         ?: $customer->bank_name;
                $data['bank_account_no']   = $data['bank_account_no']   ?: $customer->bank_account_no;
                $data['bank_account_name'] = $data['bank_account_name'] ?: $customer->bank_account_name;
            }
        }

        return $data;
    }

    /**
     * Jika ada file baru diupload (transfer_proof_new), gunakan sebagai transfer_proof.
     * Jika tidak ada upload baru, pertahankan nilai lama.
     * Jika status berubah ke paid, potong vendor_payout_amount.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['transfer_proof_new'])) {
            $data['transfer_proof'] = ltrim(
                str_replace('/storage/', '', $data['transfer_proof_new']),
                '/'
            );
        }
        unset($data['transfer_proof_new']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Potong vendor_payout_amount saat status berubah ke paid dari form edit
        if ($record->status === 'paid'
            && $record->wasChanged('status')
            && str_contains(strtolower($record->notes ?? ''), 'komplain')
        ) {
            $booking = \App\Models\Booking::find($record->booking_id);
            if ($booking) {
                $currentPayout = $booking->vendor_payout_amount ?? 0;
                $newPayout     = max(0, $currentPayout - $record->amount);
                $booking->updateQuietly(['vendor_payout_amount' => $newPayout]);
            }
        }
    }
}
