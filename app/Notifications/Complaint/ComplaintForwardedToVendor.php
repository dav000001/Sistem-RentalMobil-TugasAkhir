<?php

namespace App\Notifications\Complaint;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintForwardedToVendor extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Complaint $complaint
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $complaint  = $this->complaint;
        $dueAt      = $complaint->vendor_due_at?->format('d M Y H:i') ?? '-';
        $category   = $complaint->category?->name ?? '-';
        $customerName = $complaint->reporter?->name ?? 'Customer';
        $bookingCode  = $complaint->booking?->code ?? '-';

        return (new MailMessage)
            ->subject("⚠️ Komplain Diteruskan ke Anda — {$complaint->reference}")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Admin telah meneruskan komplain dari customer kepada Anda.")
            ->line("**Referensi:** {$complaint->reference}")
            ->line("**Kategori:** {$category}")
            ->line("**Customer:** {$customerName}")
            ->line("**Kode Booking:** {$bookingCode}")
            ->line("**Batas Waktu Respons:** {$dueAt}")
            ->line("Silakan login ke panel vendor dan berikan respons Anda sebelum batas waktu.")
            ->action('Lihat Komplain', url('/vendor/complaints/' . $this->complaint->id))
            ->line('Jika tidak direspons tepat waktu, admin akan mengambil keputusan berdasarkan informasi yang ada.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'complaint_forwarded',
            'title'         => '⚠️ Komplain Diteruskan ke Anda',
            'message'       => 'Komplain ' . $this->complaint->reference . ' diteruskan. Respons sebelum ' . ($this->complaint->vendor_due_at?->format('d M Y H:i') ?? '-') . '.',
            'complaint_id'  => $this->complaint->id,
            'reference'     => $this->complaint->reference,
            'vendor_due_at' => $this->complaint->vendor_due_at?->toIso8601String(),
            'url'           => '/vendor/complaints/' . $this->complaint->id,
        ];
    }
}
