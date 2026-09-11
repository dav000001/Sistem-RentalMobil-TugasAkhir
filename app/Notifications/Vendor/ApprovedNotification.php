<?php

namespace App\Notifications\Vendor;

use App\Enums\VendorPlan;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Vendor $vendor) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $plan = $this->vendor->plan instanceof VendorPlan
            ? $this->vendor->plan
            : VendorPlan::tryFrom($this->vendor->plan ?? 'free') ?? VendorPlan::Free;

        return (new MailMessage)
            ->subject('🎉 Selamat! Akun Vendor ' . $this->vendor->business_name . ' Telah Disetujui')
            ->view('emails.vendor.approved', [
                'notifiable'     => $notifiable,
                'vendor'         => $this->vendor,
                'planLabel'      => $plan->label(),
                'commissionRate' => $plan->commissionRate() * 100,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'format'    => 'filament',
            'type'      => 'vendor_approved',
            'title'     => '🎉 Akun Vendor Disetujui!',
            'body'      => 'Akun vendor ' . $this->vendor->business_name . ' telah aktif. Mulai tambahkan armada dan terima booking sekarang!',
            'icon'      => 'heroicon-o-check-badge',
            'color'     => 'success',
            'url'       => url('/vendor'),
            'vendor_id' => $this->vendor->id,
        ];
    }
}
