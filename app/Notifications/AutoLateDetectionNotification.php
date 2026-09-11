<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class AutoLateDetectionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Booking $booking;
    private array $detection;

    public function __construct(Booking $booking, array $detection)
    {
        $this->booking = $booking;
        $this->detection = $detection;
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
        $severity = $this->detection['severity'] ?? 'warning';
        $isLate = $this->detection['is_late'] ?? false;
        
        $subject = $isLate 
            ? "🚨 Keterlambatan Terdeteksi - Booking #{$this->booking->id}"
            : "⚠️ Potensi Keterlambatan - Booking #{$this->booking->id}";

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo {$notifiable->name},")
            ->line($isLate 
                ? "Sistem kami mendeteksi bahwa customer terlambat mengembalikan kendaraan."
                : "Sistem kami mendeteksi potensi keterlambatan pengembalian kendaraan."
            );

        // Booking details
        $mailMessage->line("**Detail Booking:**")
            ->line("• Nomor Booking: #{$this->booking->id}")
            ->line("• Customer: {$this->booking->customer->user->name}")
            ->line("• Kendaraan: {$this->booking->car->brand} {$this->booking->car->model}")
            ->line("• Batas Waktu: {$this->booking->end_at->format('d M Y H:i')}")
            ->line("• Status: " . ucfirst($this->booking->status));

        // Detection details
        if (!empty($this->detection['reasons'])) {
            $mailMessage->line("**Alasan Deteksi:**");
            foreach ($this->detection['reasons'] as $reason) {
                $mailMessage->line("• {$reason}");
            }
        }

        // Estimated late hours
        if (isset($this->detection['estimated_late_hours']) && $this->detection['estimated_late_hours'] > 0) {
            $hours = $this->detection['estimated_late_hours'];
            $mailMessage->line("• Estimasi keterlambatan: {$hours} jam");
        }

        // Confidence level
        if (isset($this->detection['confidence'])) {
            $confidence = $this->detection['confidence'];
            $mailMessage->line("• Tingkat kepercayaan: {$confidence}%");
        }

        // Recommendations
        if (!empty($this->detection['recommendations'])) {
            $mailMessage->line("**Rekomendasi Tindakan:**");
            foreach (array_slice($this->detection['recommendations'], 0, 3) as $recommendation) {
                $mailMessage->line("• {$recommendation}");
            }
        }

        // Action button
        $actionUrl = route('filament.vendor.resources.bookings.view', $this->booking);
        $mailMessage->action('Lihat Detail Booking', $actionUrl);

        $mailMessage->line('Silakan segera ambil tindakan yang diperlukan.')
            ->line('Terima kasih!')
            ->salutation('Salam, Tim ' . config('app.name'));

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $isLate = $this->detection['is_late'] ?? false;
        
        return [
            'type' => 'auto_late_detection',
            'booking_id' => $this->booking->id,
            'title' => $isLate 
                ? 'Keterlambatan Terdeteksi' 
                : 'Potensi Keterlambatan',
            'message' => $this->generateDatabaseMessage(),
            'severity' => $this->detection['severity'] ?? 'warning',
            'is_late' => $isLate,
            'estimated_late_hours' => $this->detection['estimated_late_hours'] ?? 0,
            'confidence' => $this->detection['confidence'] ?? 0,
            'detection_data' => $this->detection,
            'action_url' => route('filament.vendor.resources.bookings.view', $this->booking),
            'customer_name' => $this->booking->customer->user->name,
            'car_info' => "{$this->booking->car->brand} {$this->booking->car->model}",
            'end_time' => $this->booking->end_at->toISOString()
        ];
    }

    /**
     * Generate message for database notification
     */
    private function generateDatabaseMessage(): string
    {
        $isLate = $this->detection['is_late'] ?? false;
        $customerName = $this->booking->customer->user->name;
        $carInfo = "{$this->booking->car->brand} {$this->booking->car->model}";
        
        if ($isLate) {
            $hours = $this->detection['estimated_late_hours'] ?? 0;
            $hourText = $hours > 0 ? " (~{$hours} jam)" : "";
            return "Customer {$customerName} terlambat mengembalikan {$carInfo}{$hourText}. Segera hubungi customer.";
        } else {
            return "Customer {$customerName} berpotensi terlambat mengembalikan {$carInfo}. Pertimbangkan untuk mengirim reminder.";
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Determine if notification should be sent
     */
    public function shouldSend(object $notifiable): bool
    {
        // Don't send if booking is no longer ongoing
        if ($this->booking->status !== 'ongoing') {
            return false;
        }

        // Don't send if confidence is too low
        if (($this->detection['confidence'] ?? 0) < 50) {
            return false;
        }

        return true;
    }

    /**
     * Get notification priority for queuing
     */
    public function priority(): int
    {
        $isLate = $this->detection['is_late'] ?? false;
        $severity = $this->detection['severity'] ?? 'warning';

        if ($isLate && $severity === 'critical') {
            return 1; // High priority
        } elseif ($isLate) {
            return 2; // Medium-high priority
        } else {
            return 3; // Normal priority
        }
    }
}