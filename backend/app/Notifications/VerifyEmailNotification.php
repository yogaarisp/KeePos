<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        
        // Generate a temporary signed route for the API, then adapt it for the frontend
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        // Convert backend API URL to frontend URL
        // From: http://api.com/api/email/verify/1/hash?expires=...&signature=...
        // To: http://frontend.com/verify-email/1/hash?expires=...&signature=...
        $url = str_replace(url('/api'), $frontendUrl, $verificationUrl);

        return (new MailMessage)
            ->subject('Verifikasi Email - ' . config('app.name'))
            ->greeting('Halo ' . $notifiable->full_name . '!')
            ->line('Terima kasih telah bergabung dengan ' . config('app.name') . '!')
            ->line('Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Email', $url)
            ->line('Jika Anda tidak merasa mendaftar di layanan kami, silakan abaikan email ini.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
