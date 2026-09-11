<?php

namespace App\Notifications;

use App\Models\EmergencyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmergencyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private EmergencyReport $emergency;

    public function __construct(EmergencyReport $emergency)
    {
        $this->emergency = $emergency;
        $this->onQueue('urgent'); // Use urgent queue for emergency notifications
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
        $typeLabel = $this->emergency->getTypeLabel();
        $priorityLevel = $this->emergency->getPriorityLevel();
        
        $subject = "🚨 EMERGENCY: {$typeLabel} - Booking #{$this->emergency->booking_id}";

        if ($priorityLevel === 'critical') {
            $subject = "🆘 CRITICAL EMERGENCY: {$typeLabel} - Booking #{$this->emergency->booking_id}";
        }

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting("EMERGENCY ALERT!")
            ->line("Laporan emergency baru telah diterima dan memerlukan penanganan segera.")
            ->line("");

        // Emergency details
        $mailMessage->line("**Detail Emergency:**")
            ->line("• ID Emergency: #{$this->emergency->id}")
            ->line("• Kode Emergency: {$this->emergency->generateEmergencyCode()}")
            ->line("• Jenis: {$typeLabel}")
            ->line("• Prioritas: " . strtoupper($priorityLevel))
            ->line("• Waktu Lapor: {$this->emergency->reported_at->format('d M Y H:i:s')}")
            ->line("");

        // Booking details
        $booking = $this->emergency->booking;
        $customerPhone = $booking->customer->user->phone ?? 'Tidak tersedia';
        $vendorPhone   = $booking->vendor->user->phone ?? 'Tidak tersedia';

        $mailMessage->line("**Detail Booking:**")
            ->line("• Nomor Booking: #{$booking->id}")
            ->line("• Customer: {$booking->customer->user->name}")
            ->line("• Phone: {$customerPhone}")
            ->line("• Kendaraan: {$booking->car->brand} {$booking->car->model} ({$booking->car->license_plate})")
            ->line("• Vendor: {$booking->vendor->business_name}")
            ->line("");

        // Location details
        if ($this->emergency->latitude && $this->emergency->longitude) {
            $mapUrl = $this->emergency->getMapUrl();
            $mailMessage->line("**Lokasi Emergency:**")
                ->line("• Koordinat: {$this->emergency->latitude}, {$this->emergency->longitude}")
                ->line("• Google Maps: {$mapUrl}")
                ->line("");
        }

        // Description
        if ($this->emergency->description) {
            $mailMessage->line("**Deskripsi:**")
                ->line($this->emergency->description)
                ->line("");
        }

        // Next steps based on emergency type
        $nextSteps = $this->getEmergencyResponseSteps();
        if (!empty($nextSteps)) {
            $mailMessage->line("**Langkah Penanganan:**");
            foreach ($nextSteps as $step) {
                $mailMessage->line("• {$step}");
            }
            $mailMessage->line("");
        }

        // Contact information
        $mailMessage->line("**Kontak Darurat:**")
            ->line("• Customer: {$customerPhone}")
            ->line("• Vendor: {$vendorPhone}")
            ->line("• Police: 110")
            ->line("• Medical: 118/119")
            ->line("");

        // Action button
        $actionUrl = route('filament.admin.resources.bookings.view', $this->emergency->booking);
        $mailMessage->action('TANGANI EMERGENCY SEKARANG', $actionUrl);

        $mailMessage->line('⚠️ EMERGENCY INI MEMERLUKAN PENANGANAN SEGERA!')
            ->line('')
            ->salutation('Tim Emergency Response ' . config('app.name'));

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'emergency_report',
            'emergency_id' => $this->emergency->id,
            'booking_id' => $this->emergency->booking_id,
            'title' => 'Emergency Report: ' . $this->emergency->getTypeLabel(),
            'message' => $this->generateDatabaseMessage(),
            'emergency_type' => $this->emergency->type,
            'emergency_code' => $this->emergency->generateEmergencyCode(),
            'priority_level' => $this->emergency->getPriorityLevel(),
            'status' => $this->emergency->status,
            'location' => [
                'latitude' => $this->emergency->latitude,
                'longitude' => $this->emergency->longitude,
                'map_url' => $this->emergency->getMapUrl()
            ],
            'customer_info' => [
                'name' => $this->emergency->customer->user->name,
                'phone' => $this->emergency->customer->user->phone
            ],
            'car_info' => [
                'brand' => $this->emergency->booking->car->brand,
                'model' => $this->emergency->booking->car->model,
                'license_plate' => $this->emergency->booking->car->license_plate
            ],
            'reported_at' => $this->emergency->reported_at->toISOString(),
            'action_url' => $this->getActionUrl($notifiable)
        ];
    }

    /**
     * Generate message for database notification
     */
    private function generateDatabaseMessage(): string
    {
        $typeLabel = $this->emergency->getTypeLabel();
        $customerName = $this->emergency->customer->user->name;
        $carInfo = "{$this->emergency->booking->car->brand} {$this->emergency->booking->car->model}";
        
        $message = "Emergency {$typeLabel} dilaporkan oleh {$customerName} ({$carInfo}). ";
        
        if ($this->emergency->description) {
            $description = substr($this->emergency->description, 0, 100);
            $message .= "Deskripsi: {$description}" . (strlen($this->emergency->description) > 100 ? '...' : '');
        }
        
        return $message;
    }

    /**
     * Get emergency response steps based on type
     */
    private function getEmergencyResponseSteps(): array
    {
        return match($this->emergency->type) {
            'breakdown' => [
                'Hubungi customer untuk konfirmasi kondisi',
                'Kirim teknisi atau derek jika diperlukan',
                'Koordinasi dengan bengkel terdekat',
                'Update status emergency menjadi "responding"'
            ],
            'accident' => [
                'Pastikan customer aman - jika cedera hubungi ambulans',
                'Sarankan customer menghubungi polisi (110)',
                'Koordinasi dengan asuransi kendaraan',
                'Kirim perwakilan ke lokasi jika memungkinkan',
                'Dokumentasi untuk klaim asuransi'
            ],
            'theft' => [
                'Instruksikan customer lapor polisi segera (110)',
                'Jangan kejar pelaku - prioritaskan keselamatan',
                'Koordinasi dengan asuransi untuk klaim',
                'Blokir akses kendaraan jika ada sistem tracking',
                'Siapkan dokumen untuk proses hukum'
            ],
            'harassment' => [
                'Pastikan customer di tempat aman',
                'Sarankan hubungi polisi (110) jika terancam',
                'Koordinasi dengan pihak berwajib',
                'Berikan dukungan dan bantuan hukum',
                'Dokumentasi kejadian untuk laporan'
            ],
            'medical' => [
                'Instruksikan customer hubungi ambulans (118/119)',
                'Berikan bantuan koordinasi dengan rumah sakit',
                'Hubungi keluarga customer jika diperlukan',
                'Koordinasi asuransi kesehatan jika ada',
                'Pastikan kendaraan aman'
            ],
            'other' => [
                'Hubungi customer untuk detail situasi',
                'Berikan bantuan sesuai kebutuhan',
                'Koordinasi dengan pihak terkait',
                'Update status secara berkala'
            ]
        };
    }

    /**
     * Get action URL - always point to booking detail
     */
    private function getActionUrl(object $notifiable): string
    {
        return route('filament.admin.resources.bookings.view', $this->emergency->booking);
    }

    /**
     * Get notification priority for queuing
     */
    public function priority(): int
    {
        return match($this->emergency->getPriorityLevel()) {
            'critical' => 0,  // Highest priority
            'high' => 1,
            'medium' => 2,
            'low' => 3,
            default => 2
        };
    }

    /**
     * Determine the time at which the notification should be sent
     */
    public function delay(): ?\DateTimeInterface
    {
        // Send critical emergencies immediately, others with slight delay for batching
        return $this->emergency->getPriorityLevel() === 'critical' 
            ? null 
            : now()->addSeconds(10);
    }
}