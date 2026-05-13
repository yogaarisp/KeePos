<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // 1. Configure Global Mailer (for SaaS level notifications like Registration/Password Reset)
        try {
                if (Schema::hasTable('platform_settings')) {
                    $smtpSettings = \App\Models\PlatformSetting::where('group', 'email')
                        ->get()
                        ->pluck('value', 'key');

                    if ($smtpSettings->isNotEmpty()) {
                        config([
                            'mail.default' => 'smtp',
                            'mail.mailers.smtp.host' => $smtpSettings->get('smtp_host', config('mail.mailers.smtp.host')),
                            'mail.mailers.smtp.port' => $smtpSettings->get('smtp_port', config('mail.mailers.smtp.port')),
                            'mail.mailers.smtp.username' => $smtpSettings->get('smtp_username', config('mail.mailers.smtp.username')),
                            'mail.mailers.smtp.password' => $smtpSettings->get('smtp_password', config('mail.mailers.smtp.password')),
                            'mail.mailers.smtp.encryption' => $smtpSettings->get('smtp_encryption', config('mail.mailers.smtp.encryption')),
                            'mail.from.address' => $smtpSettings->get('smtp_from_address', config('mail.from.address')),
                            'mail.from.name' => $smtpSettings->get('smtp_from_name', config('mail.from.name')),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Fail silently during boot to prevent app crash
            }
    }
}
