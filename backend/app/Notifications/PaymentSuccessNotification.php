<?php

namespace App\Notifications;

use App\Models\SubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SubscriptionInvoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName     = \App\Models\PlatformSetting::getValue('app_name', 'Kee POS');
        $plan        = strtoupper($this->invoice->plan);
        $months      = $this->invoice->months ?? 1;
        $amount      = 'Rp ' . number_format($this->invoice->amount, 0, ',', '.');
        $endsAt      = $notifiable->tenant?->subscription_ends_at
                           ? $notifiable->tenant->subscription_ends_at->format('d M Y')
                           : '-';
        $frontendUrl = env('FRONTEND_URL', 'https://pos.keetech.my.id');

        return (new MailMessage)
            ->subject("✅ Pembayaran Berhasil — Paket {$plan} | {$appName}")
            ->greeting("Halo {$notifiable->full_name}!")
            ->line("Pembayaran via Midtrans untuk paket **{$plan}** selama **{$months} bulan** berhasil diproses otomatis.")
            ->line("**Detail Transaksi:**")
            ->line("- Invoice: #{$this->invoice->invoice_number}")
            ->line("- Paket: {$plan}")
            ->line("- Nominal: {$amount}")
            ->line("- Aktif sampai: {$endsAt}")
            ->action('Buka Dashboard', $frontendUrl . '/app')
            ->line("Terima kasih telah berlangganan {$appName}. Selamat menggunakan layanan kami!");
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
