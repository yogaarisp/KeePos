<?php

namespace App\Notifications;

use App\Models\SubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SubscriptionInvoice $invoice,
        public string $reason = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName     = \App\Models\PlatformSetting::getValue('app_name', 'Kee POS');
        $plan        = strtoupper($this->invoice->plan);
        $amount      = 'Rp ' . number_format($this->invoice->amount, 0, ',', '.');
        $frontendUrl = env('FRONTEND_URL', 'https://pos.keetech.my.id');

        $mail = (new MailMessage)
            ->subject("❌ Bukti Pembayaran Ditolak — {$appName}")
            ->greeting("Halo {$notifiable->full_name},")
            ->line("Maaf, bukti pembayaran Anda untuk paket **{$plan}** ({$amount}) tidak dapat kami konfirmasi.");

        if ($this->reason) {
            $mail->line("**Alasan:** {$this->reason}");
        }

        $mail->line("Silakan upload ulang bukti pembayaran yang valid atau hubungi tim support kami.")
             ->action('Upload Ulang Bukti', $frontendUrl . '/app/billing')
             ->line("Jika ada pertanyaan, hubungi kami melalui WhatsApp atau email support.");

        return $mail;
    }
}
