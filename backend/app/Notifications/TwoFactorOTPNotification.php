<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorOTPNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $code;

    /**
     * Create a new notification instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
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
        return (new MailMessage)
            ->subject('Kode Keamanan 2FA Login - ' . config('app.name'))
            ->greeting('Halo ' . $notifiable->full_name . '!')
            ->line('Anda mendeteksi upaya login baru ke akun Anda.')
            ->line('Gunakan kode OTP keamanan 2FA di bawah ini untuk melanjutkan login:')
            ->line('**' . $this->code . '**')
            ->line('Kode keamanan ini hanya berlaku selama 10 menit.')
            ->line('Jika Anda tidak merasa melakukan upaya login ini, segera ganti password akun Anda.');
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
