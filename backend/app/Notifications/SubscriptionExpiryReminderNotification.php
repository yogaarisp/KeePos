<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $daysLeft) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        $plan = strtoupper($notifiable->tenant?->plan ?? 'FREE');
        return [
            'title'     => 'Langganan Akan Berakhir',
            'message'   => "Paket {$plan} Anda akan berakhir dalam {$this->daysLeft} hari. Perpanjang sekarang.",
            'days_left' => $this->daysLeft,
            'url'       => '/app/billing',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName     = \App\Models\PlatformSetting::getValue('app_name', 'Kee POS');
        $plan        = strtoupper($notifiable->tenant?->plan ?? 'FREE');
        $endsAt      = $notifiable->tenant?->subscription_ends_at?->format('d M Y') ?? '-';
        $frontendUrl = env('FRONTEND_URL', 'https://pos.keetech.my.id');

        $urgency = $this->daysLeft <= 1 ? '🚨 HARI INI' : "⚠️ {$this->daysLeft} HARI LAGI";

        return (new MailMessage)
            ->subject("{$urgency} — Langganan {$appName} Anda Akan Berakhir")
            ->greeting("Halo {$notifiable->full_name}!")
            ->line("Langganan paket **{$plan}** Anda akan berakhir pada **{$endsAt}** ({$this->daysLeft} hari lagi).")
            ->line("Setelah masa aktif berakhir, akses ke fitur premium akan dinonaktifkan.")
            ->action('Perpanjang Sekarang', $frontendUrl . '/app/billing')
            ->line("Perpanjang sekarang untuk memastikan bisnis Anda tetap berjalan tanpa gangguan.");
    }
}
