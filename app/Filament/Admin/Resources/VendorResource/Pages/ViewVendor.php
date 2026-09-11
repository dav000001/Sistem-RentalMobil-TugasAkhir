<?php

namespace App\Filament\Admin\Resources\VendorResource\Pages;

use App\Enums\VendorStatus;
use App\Filament\Admin\Resources\VendorResource;
use App\Services\Vendor\VendorVerificationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Setujui — hanya untuk pending dan needs_revision
            Actions\Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Vendor')
                ->modalDescription('Apakah Anda yakin ingin menyetujui vendor ini? Vendor akan langsung aktif.')
                ->visible(fn () => in_array(
                    $this->record->status instanceof VendorStatus ? $this->record->status->value : $this->record->status,
                    ['pending', 'needs_revision']
                ))
                ->action(function () {
                    try {
                        app(VendorVerificationService::class)->approveWithoutDocCheck($this->record, auth('admin')->user());
                        Notification::make()->title('Vendor berhasil disetujui')->success()->send();
                        $this->refreshFormData(['status', 'documents_verified', 'last_reviewed_at']);
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // Tolak — hanya untuk pending dan needs_revision
            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3)
                        ->placeholder('Jelaskan alasan penolakan...'),
                ])
                ->visible(fn () => in_array(
                    $this->record->status instanceof VendorStatus ? $this->record->status->value : $this->record->status,
                    ['pending', 'needs_revision']
                ))
                ->action(function (array $data) {
                    try {
                        app(VendorVerificationService::class)->reject($this->record, auth('admin')->user(), $data['reason']);
                        Notification::make()
                            ->title('Vendor ditolak')
                            ->body('Email notifikasi telah dikirim ke ' . $this->record->user->email)
                            ->warning()
                            ->send();
                        $this->refreshFormData(['status', 'last_reviewed_at']);
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // Minta Revisi — hanya untuk pending
            Actions\Action::make('request_revision')
                ->label('Minta Revisi')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Catatan Revisi')
                        ->required()
                        ->rows(3)
                        ->placeholder('Jelaskan dokumen apa yang perlu diperbaiki...'),
                ])
                ->visible(fn () => (
                    $this->record->status instanceof VendorStatus
                        ? $this->record->status->value
                        : $this->record->status
                ) === 'pending')
                ->action(function (array $data) {
                    try {
                        app(VendorVerificationService::class)->requestRevision($this->record, auth('admin')->user(), $data['reason']);
                        Notification::make()->title('Permintaan revisi dikirim')->warning()->send();
                        $this->refreshFormData(['status', 'last_reviewed_at']);
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // Bekukan — hanya untuk approved
            Actions\Action::make('suspend')
                ->label('Bekukan')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Alasan Pembekuan')
                        ->required()
                        ->rows(3)
                        ->placeholder('Jelaskan alasan pembekuan akun...'),
                ])
                ->visible(fn () => (
                    $this->record->status instanceof VendorStatus
                        ? $this->record->status->value
                        : $this->record->status
                ) === 'approved')
                ->action(function (array $data) {
                    try {
                        app(VendorVerificationService::class)->suspend($this->record, auth('admin')->user(), $data['reason']);
                        Notification::make()->title('Vendor dibekukan')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // Aktifkan Kembali — hanya untuk suspended
            Actions\Action::make('unsuspend')
                ->label('Aktifkan Kembali')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aktifkan Kembali Vendor')
                ->modalDescription('Akun vendor akan diaktifkan kembali dan semua mobil yang dibekukan akan dipublish ulang.')
                ->visible(fn () => (
                    $this->record->status instanceof VendorStatus
                        ? $this->record->status->value
                        : $this->record->status
                ) === 'suspended')
                ->action(function () {
                    try {
                        app(VendorVerificationService::class)->unsuspend($this->record, auth('admin')->user());
                        Notification::make()->title('Vendor diaktifkan kembali')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (\Throwable $e) {
                        Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Actions\EditAction::make()
                ->label('Edit Data')
                ->visible(fn () => (
                    $this->record->status instanceof VendorStatus
                        ? $this->record->status->value
                        : $this->record->status
                ) === 'approved'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Vendor')
                ->components([
                    TextEntry::make('business_name')->label('Nama Bisnis'),
                    TextEntry::make('user.name')->label('Pemilik'),
                    TextEntry::make('user.email')->label('Email'),
                    TextEntry::make('city.name')->label('Kota'),
                    TextEntry::make('address')->label('Alamat')->columnSpanFull(),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state instanceof VendorStatus
                            ? $state->label()
                            : (VendorStatus::tryFrom($state)?->label() ?? $state))
                        ->color(fn ($state): string => match (($state instanceof VendorStatus ? $state->value : $state)) {
                            'pending', 'needs_revision' => 'warning',
                            'approved'                  => 'success',
                            'rejected'                  => 'danger',
                            default                     => 'gray',
                        }),
                    IconEntry::make('documents_complete')->label('Dokumen Lengkap')->boolean(),
                    IconEntry::make('documents_verified')->label('Dokumen Terverifikasi')->boolean(),
                    TextEntry::make('documents_submitted_at')->label('Dokumen Disubmit')->dateTime('d M Y H:i'),
                    TextEntry::make('last_reviewed_at')->label('Terakhir Direview')->dateTime('d M Y H:i'),
                ])
                ->columns(2),

            Section::make('Informasi Bank')
                ->components([
                    TextEntry::make('bank_name')->label('Nama Bank'),
                    TextEntry::make('bank_account_number')->label('No. Rekening'),
                    TextEntry::make('bank_account_name')->label('Nama Pemilik Rekening'),
                ])
                ->columns(3),

            Section::make('Dokumen yang Diupload')
                ->components([
                    TextEntry::make('documents_summary')
                        ->label('')
                        ->columnSpanFull()
                        ->html()
                        ->getStateUsing(function ($record) {
                            $docs = $record->documents;
                            if ($docs->isEmpty()) {
                                return '<p class="text-gray-400 text-sm italic">Belum ada dokumen diupload.</p>';
                            }

                            $docLabels = [
                                'ktp'      => 'KTP',
                                'npwp'     => 'NPWP',
                                'siup'     => 'SIUP / NIB',
                                'selfie'   => 'Selfie + KTP',
                                'logo'     => 'Logo',
                                'domisili' => 'Surat Domisili',
                            ];

                            $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                            foreach ($docs as $doc) {
                                $label = $docLabels[$doc->type] ?? strtoupper($doc->type);
                                $statusBadge = match ($doc->status) {
                                    'approved' => '<span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">✅ Disetujui</span>',
                                    'rejected' => '<span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-medium">❌ Ditolak</span>',
                                    default    => '<span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-medium">⏳ Menunggu</span>',
                                };

                                $isImage = in_array($doc->mime, ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
                                $isPdf = $doc->mime === 'application/pdf';
                                $fileSize = $doc->size ? number_format($doc->size / 1024, 1) . ' KB' : '';
                                $previewUrl = route('admin.vendor-documents.preview', $doc->id);
                                $downloadUrl = route('admin.vendor-documents.download', $doc->id);

                                $html .= '<div class="border border-gray-200 rounded-lg p-4 bg-gray-50">';
                                $html .= '<div class="flex items-center justify-between mb-3">';
                                $html .= '<div class="flex items-center gap-2"><span class="font-semibold text-gray-800 text-sm">' . $label . '</span>' . $statusBadge . '</div>';
                                $html .= '<div class="flex gap-2">';
                                $html .= '<a href="' . $previewUrl . '" target="_blank" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 transition font-medium">👁 Lihat</a>';
                                $html .= '<a href="' . $downloadUrl . '" class="text-xs bg-gray-600 text-white px-3 py-1.5 rounded hover:bg-gray-700 transition font-medium">⬇ Unduh</a>';
                                $html .= '</div></div>';

                                if ($isImage) {
                                    $html .= '<a href="' . $previewUrl . '" target="_blank">';
                                    $html .= '<img src="' . $previewUrl . '" alt="' . $label . '" class="w-full max-h-52 object-contain rounded border border-gray-200 bg-white cursor-zoom-in hover:opacity-90 transition">';
                                    $html .= '</a>';
                                } elseif ($isPdf) {
                                    $html .= '<div class="bg-white border border-gray-200 rounded p-4 text-center">';
                                    $html .= '<div class="text-5xl mb-2">📄</div>';
                                    $html .= '<p class="text-sm text-gray-700 font-medium">' . e($doc->original_name ?? 'dokumen.pdf') . '</p>';
                                    if ($fileSize) $html .= '<p class="text-xs text-gray-400">' . $fileSize . '</p>';
                                    $html .= '<a href="' . $previewUrl . '" target="_blank" class="text-blue-600 text-xs hover:underline mt-2 inline-block">Buka PDF →</a>';
                                    $html .= '</div>';
                                } else {
                                    $html .= '<p class="text-sm text-gray-500 mt-1">' . e($doc->original_name ?? '-') . '</p>';
                                }

                                if ($doc->rejection_reason) {
                                    $html .= '<p class="text-xs text-red-600 mt-2 bg-red-50 border border-red-100 p-2 rounded">⚠️ ' . e($doc->rejection_reason) . '</p>';
                                }

                                $html .= '</div>';
                            }
                            $html .= '</div>';
                            return $html;
                        }),
                ]),

            Section::make('Catatan Internal')
                ->components([
                    TextEntry::make('internal_notes')->label('')->columnSpanFull(),
                ])
                ->collapsed(),

            Section::make('Riwayat Status')
                ->components([
                    TextEntry::make('status_history')
                        ->label('')
                        ->columnSpanFull()
                        ->getStateUsing(function ($record) {
                            $logs = $record->statusLogs()->with('actor')->latest()->get();
                            if ($logs->isEmpty()) return 'Belum ada riwayat status.';

                            return $logs->map(function ($log) {
                                $from = $log->from_status
                                    ? (VendorStatus::tryFrom($log->from_status)?->label() ?? $log->from_status)
                                    : '-';
                                $to = VendorStatus::tryFrom($log->to_status)?->label() ?? $log->to_status;
                                $actor = $log->actor?->name ?? 'Sistem';
                                $time = $log->created_at->format('d M Y H:i');
                                $reason = $log->reason ? " — {$log->reason}" : '';
                                return "[{$time}] {$from} → {$to} oleh {$actor}{$reason}";
                            })->implode("\n");
                        }),
                ])
                ->collapsed(),
        ]);
    }
}
