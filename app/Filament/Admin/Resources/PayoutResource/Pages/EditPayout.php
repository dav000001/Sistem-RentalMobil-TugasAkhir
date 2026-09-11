<?php

namespace App\Filament\Admin\Resources\PayoutResource\Pages;

use App\Filament\Admin\Resources\PayoutResource;
use App\Notifications\PayoutFailedNotification;
use Filament\Resources\Pages\EditRecord;

class EditPayout extends EditRecord
{
    protected static string $resource = PayoutResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Normalisasi path transfer_proof saat form dibuka.
     * Database menyimpan path lengkap (/storage/payout-proofs/file.jpg),
     * sedangkan Filament FileUpload butuh nama file relatif (payout-proofs/file.jpg).
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['transfer_proof'])) {
            // Strip prefix /storage/ agar Filament bisa resolve file dengan benar
            $data['transfer_proof'] = ltrim(
                str_replace('/storage/', '', $data['transfer_proof']),
                '/'
            );
        }

        return $data;
    }

    /**
     * Normalisasi path saat disimpan.
     * Jika ada file baru diupload (transfer_proof_new), gunakan itu.
     * Jika tidak ada upload baru, pertahankan nilai lama.
     * FileUpload bisa return string atau array — handle keduanya.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['transfer_proof_new'])) {
            // Filament bisa return string atau array — ambil nilai pertama jika array
            $raw = is_array($data['transfer_proof_new'])
                ? array_values($data['transfer_proof_new'])[0]
                : $data['transfer_proof_new'];

            $data['transfer_proof'] = ltrim(
                str_replace('/storage/', '', $raw),
                '/'
            );
        }
        // Selalu buang field sementara ini, tidak ada di tabel database
        unset($data['transfer_proof_new']);

        return $data;
    }

    /**
     * Setelah record disimpan — kirim notifikasi dan isi paid_at jika perlu.
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Kirim notifikasi ke vendor jika status baru = failed
        if ($record->status === 'failed' && $record->wasChanged('status')) {
            try {
                $reason = $record->notes ?? '';
                $record->vendor?->user?->notify(
                    new PayoutFailedNotification($record, $reason)
                );
            } catch (\Throwable) {}
        }

        // Jika status = paid dan paid_at belum diisi, set otomatis
        if ($record->status === 'paid' && ! $record->paid_at) {
            $record->updateQuietly(['paid_at' => now()]);
        }

        // Jika bukti transfer baru diupload dan status paid → notif vendor
        if ($record->status === 'paid' && $record->wasChanged('transfer_proof') && $record->transfer_proof) {
            try {
                $record->vendor?->user?->notify(
                    new \App\Notifications\PayoutCreatedNotification($record)
                );
            } catch (\Throwable) {}
        }
    }
}
