<?php

namespace App\Notifications;

use App\Models\SubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SubscriptionInvoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'          => 'Pembayaran Dikonfirmasi',
            'message'        => 'Paket ' . strtoupper($this->invoice->plan) . ' selama ' . ($this->invoice->months ?? 1) . ' bulan telah aktif.',
            'invoice_number' => $this->invoice->invoice_number,
            'plan'           => $this->invoice->plan,
            'amount'         => $this->invoice->amount,
            'url'            => '/app/billing',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName  = \App\Models\PlatformSetting::getValue('app_name', 'Kee POS');
        $plan     = strtoupper($this->invoice->plan);
        $months   = $this->invoice->months ?? 1;
        $amount   = 'Rp ' . number_format($this->invoice->amount, 0, ',', '.');
        $endsAt   = $notifiable->tenant?->subscription_ends_at
                        ? $notifiable->tenant->subscription_ends_at->format('d M Y')
                        : '-';
        $frontendUrl = env('FRONTEND_URL', 'https://pos.keetech.my.id');

        return (new MailMessage)
            ->subject("✅ Pembayaran Dikonfirmasi — Paket {$plan} | {$appName}")
            ->greeting("Halo {$notifiable->full_name}!")
            ->line("Pembayaran Anda untuk paket **{$plan}** selama **{$months} bulan** telah dikonfirmasi.")
            ->line("**Detail Transaksi:**")
            ->line("- Invoice: #{$this->invoice->invoice_number}")
            ->line("- Paket: {$plan}")
            ->line("- Nominal: {$amount}")
            ->line("- Aktif sampai: {$endsAt}")
            ->action('Buka Dashboard', $frontendUrl . '/app')
            ->line("Terima kasih telah berlangganan {$appName}. Selamat menggunakan layanan kami!");
    }
}
