<?php

namespace App\Notifications;

use App\Models\EmergencyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyStatusUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private EmergencyReport $emergency;
    private string $previousStatus;

    public function __construct(EmergencyReport $emergency, string $previousStatus = '')
    {
        $this->emergency = $emergency;
        $this->previousStatus = $previousStatus;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->emergency->getStatusLabel();
        $typeLabel = $this->emergency->getTypeLabel();
        $emergencyCode = $this->emergency->generateEmergencyCode();

        $subject = "📋 Update Emergency {$emergencyCode} - Status: {$this->emergency->status}";

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo {$notifiable->name},")
            ->line("Ada update status untuk laporan emergency Anda.");

        // Emergency basic info
        $mailMessage->line("**Detail Emergency:**")
            ->line("• Kode Emergency: {$emergencyCode}")
            ->line("• Jenis: {$typeLabel}")
            ->line("• Status Baru: {$statusLabel}")
            ->line("• Waktu Update: {$this->emergency->updated_at->format('d M Y H:i:s')}")
            ->line("");

        // Status-specific messages
        $mailMessage = $this->addStatusSpecificContent($mailMessage);

        // Resolution details if resolved
        if (in_array($this->emergency->status, ['resolved', 'closed']) && $this->emergency->resolution) {
            $mailMessage->line("**Penyelesaian:**")
                ->line($this->emergency->resolution)
                ->line("");
        }

        // Response time if resolved
        if ($this->emergency->isResolved()) {
            $responseTime = $this->emergency->getResponseTimeFormatted();
            if ($responseTime) {
                $mailMessage->line("• Waktu penanganan: {$responseTime}")
                    ->line("");
            }
        }

        // Next steps based on status
        $nextSteps = $this->getNextSteps();
        if (!empty($nextSteps)) {
            $mailMessage->line("**Langkah Selanjutnya:**");
            foreach ($nextSteps as $step) {
                $mailMessage->line("• {$step}");
            }
            $mailMessage->line("");
        }

        // Contact info
        if ($this->emergency->status === 'responding') {
            $vendorContactPhone = $this->emergency->vendor->user->phone ?? 'Tidak tersedia';
            $vendorContactEmail = $this->emergency->vendor->user->email;
            $vendorContactName  = $this->emergency->vendor->business_name;

            $mailMessage->line("**Kontak Tim Emergency:**")
                ->line("• Vendor: {$vendorContactName}")
                ->line("• Phone: {$vendorContactPhone}")
                ->line("• Email: {$vendorContactEmail}")
                ->line("");
        }

        $mailMessage->line('Terima kasih atas kesabaran Anda.')
            ->line('')
            ->salutation('Salam, Tim ' . config('app.name'));

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'emergency_status_update',
            'emergency_id' => $this->emergency->id,
            'booking_id' => $this->emergency->booking_id,
            'title' => 'Emergency Update: ' . $this->emergency->getStatusLabel(),
            'message' => $this->generateDatabaseMessage(),
            'emergency_code' => $this->emergency->generateEmergencyCode(),
            'emergency_type' => $this->emergency->type,
            'old_status' => $this->previousStatus,
            'new_status' => $this->emergency->status,
            'status_label' => $this->emergency->getStatusLabel(),
            'resolution' => $this->emergency->resolution,
            'response_time' => $this->emergency->getResponseTimeFormatted(),
            'updated_at' => $this->emergency->updated_at->toISOString(),
            'is_resolved' => $this->emergency->isResolved(),
            'vendor_info' => [
                'business_name' => $this->emergency->vendor->business_name,
                'phone' => $this->emergency->vendor->user->phone
            ]
        ];
    }

    /**
     * Add status-specific content to mail
     */
    private function addStatusSpecificContent(MailMessage $mailMessage): MailMessage
    {
        switch ($this->emergency->status) {
            case 'responding':
                $mailMessage->line("✅ **Tim emergency sedang menuju lokasi Anda.**")
                    ->line("Vendor telah menerima laporan dan akan segera memberikan bantuan.")
                    ->line("Harap tetap tenang dan ikuti instruksi yang diberikan.")
                    ->line("");
                break;

            case 'resolved':
                $mailMessage->line("✅ **Emergency telah berhasil ditangani.**")
                    ->line("Tim kami telah menyelesaikan masalah dan situasi sudah aman.")
                    ->line("");
                break;

            case 'closed':
                $mailMessage->line("✅ **Emergency telah resmi ditutup.**")
                    ->line("Semua tindak lanjut telah selesai. Terima kasih atas kerja sama Anda.")
                    ->line("");
                break;

            default:
                $mailMessage->line("📋 Status emergency Anda telah diperbarui.")
                    ->line("");
        }

        return $mailMessage;
    }

    /**
     * Get next steps based on current status
     */
    private function getNextSteps(): array
    {
        return match($this->emergency->status) {
            'responding' => [
                'Tetap di lokasi yang aman',
                'Jawab panggilan dari tim emergency',
                'Ikuti instruksi yang diberikan',
                'Hubungi nomor darurat jika situasi memburuk'
            ],
            'resolved' => [
                'Pastikan Anda dalam kondisi baik',
                'Lanjutkan perjalanan jika aman',
                'Hubungi kami jika ada masalah lanjutan',
                'Berikan feedback untuk emergency response'
            ],
            'closed' => [
                'Emergency sudah selesai ditangani',
                'Dokumen terkait sudah diproses',
                'Dapat melanjutkan aktivitas normal',
                'Hubungi customer service jika ada pertanyaan'
            ],
            default => [
                'Pantau update status emergency',
                'Hubungi vendor jika perlu bantuan'
            ]
        };
    }

    /**
     * Generate message for database notification
     */
    private function generateDatabaseMessage(): string
    {
        $emergencyCode = $this->emergency->generateEmergencyCode();
        $statusLabel = $this->emergency->getStatusLabel();
        
        $message = "Emergency {$emergencyCode} status diperbarui ke: {$statusLabel}";
        
        if ($this->emergency->resolution) {
            $resolution = substr($this->emergency->resolution, 0, 100);
            $message .= ". Penyelesaian: {$resolution}" . (strlen($this->emergency->resolution) > 100 ? '...' : '');
        }
        
        return $message;
    }

    /**
     * Determine if the notification should be sent
     */
    public function shouldSend(object $notifiable): bool
    {
        // Always send to customer for status updates
        if ($notifiable->id === $this->emergency->customer->user_id) {
            return true;
        }
        
        // Send to vendor for important status changes
        if ($notifiable->id === $this->emergency->vendor->user_id) {
            return in_array($this->emergency->status, ['resolved', 'closed']);
        }
        
        return false;
    }
}