<?php

namespace App\Notifications;

use App\Models\LateReturnReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateReturnReportedToVendorNotification extends Notification
{

    public function __construct(
        public readonly LateReturnReport $report,
        public readonly ?\App\Models\Booking $conflictingBooking = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking  = $this->report->booking;
        $reporter = $this->report->reporterLabel();
        $hours    = $this->report->estimated_late_hours;

        $mail = (new MailMessage)
            ->subject("⚠️ Laporan Keterlambatan — Booking {$booking->code}")
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line("{$reporter} melaporkan bahwa booking **{$booking->code}** akan terlambat dikembalikan.")
            ->line("**Perkiraan keterlambatan:** {$hours} jam")
            ->line('**Alasan:** ' . ($this->report->reason ?? '—'));

        if ($this->conflictingBooking) {
            $mail->line('---')
                 ->line("⚠️ **PERINGATAN BENTROK JADWAL:** Mobil ini memiliki booking berikutnya (**{$this->conflictingBooking->code}**) atas nama **{$this->conflictingBooking->customer?->full_name}** pada {$this->conflictingBooking->start_at?->format('d M Y H:i')}. Harap segera persiapkan unit pengganti!")
                 ->line('---');
        }

        return $mail->when($this->report->hasLocation(), fn ($m) =>
                $m->line('**Lokasi saat lapor:** ' . ($this->report->location_address ?? $this->report->latitude . ',' . $this->report->longitude))
            )
            ->action('Lihat Detail Booking', url('/vendor/bookings'))
            ->salutation('Tim Rental Mobil');
    }

    public function toArray(object $notifiable): array
    {
        $msg = $this->report->reporterLabel()
            . ' melaporkan keterlambatan booking '
            . $this->report->booking?->code
            . ' ~' . $this->report->estimated_late_hours . ' jam.';

        if ($this->conflictingBooking) {
            $msg .= ' ⚠️ BENTROK dengan booking ' . $this->conflictingBooking->code . '!';
        }

        return [
            'type'            => 'late_return_reported',
            'booking_id'      => $this->report->booking_id,
            'booking_code'    => $this->report->booking?->code,
            'report_id'       => $this->report->id,
            'reporter_type'   => $this->report->reporter_type,
            'estimated_hours' => $this->report->estimated_late_hours,
            'has_conflict'    => $this->conflictingBooking !== null,
            'conflict_code'   => $this->conflictingBooking?->code,
            'message'         => $msg,
        ];
    }
}
