<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName     = \App\Models\PlatformSetting::getValue('app_name', 'Kee POS');
        $trialDays   = (int) \App\Models\PlatformSetting::getValue('default_trial_days', 20);
        $whatsapp    = \App\Models\PlatformSetting::getValue('app_whatsapp', '');
        $frontendUrl = env('FRONTEND_URL', 'https://pos.keetech.my.id');
        $waLink      = $whatsapp ? "https://wa.me/{$whatsapp}" : null;

        $mail = (new MailMessage)
            ->subject("🎉 Selamat Datang di {$appName}!")
            ->greeting("Halo {$notifiable->full_name}! 👋")
            ->line("Akun Anda telah berhasil diverifikasi. Selamat datang di **{$appName}**!")
            ->line("Anda mendapatkan **{$trialDays} hari masa trial gratis** untuk mencoba semua fitur.")
            ->line("**Mulai dari mana?**")
            ->line("1. Tambahkan menu/produk di halaman **Kelola Menu**")
            ->line("2. Atur meja di halaman **Kelola Meja**")
            ->line("3. Mulai transaksi di **Kasir POS**")
            ->action('Buka Dashboard Sekarang', $frontendUrl . '/app');

        if ($waLink) {
            $mail->line("Butuh bantuan? Hubungi kami via [WhatsApp]({$waLink}).");
        }

        return $mail->line("Selamat berbisnis! 🚀");
    }
}
