<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionExpiryReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature   = 'subscriptions:send-expiry-reminders';
    protected $description = 'Send email reminders to tenants whose subscription expires in 7 days or 1 day';

    public function handle(): int
    {
        $reminderDays = [7, 3, 1];
        $sent = 0;

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            // Paid plans expiring
            $tenants = Tenant::where('is_active', true)
                ->whereIn('plan', ['basic', 'pro'])
                ->whereDate('subscription_ends_at', $targetDate)
                ->get();

            foreach ($tenants as $tenant) {
                $owner = User::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'admin')
                    ->first();

                if (!$owner) continue;

                try {
                    $owner->notify(new SubscriptionExpiryReminderNotification($days));
                    $sent++;
                    $this->info("Sent {$days}-day reminder to: {$owner->email} ({$tenant->name})");
                } catch (\Exception $e) {
                    Log::error("Failed to send expiry reminder to {$owner->email}: " . $e->getMessage());
                    $this->error("Failed: {$owner->email} — " . $e->getMessage());
                }
            }

            // Free plan trial expiring
            $trialTenants = Tenant::where('is_active', true)
                ->where('plan', 'free')
                ->whereDate('trial_ends_at', $targetDate)
                ->get();

            foreach ($trialTenants as $tenant) {
                $owner = User::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'admin')
                    ->first();

                if (!$owner) continue;

                try {
                    $owner->notify(new SubscriptionExpiryReminderNotification($days));
                    $sent++;
                    $this->info("Sent {$days}-day trial reminder to: {$owner->email} ({$tenant->name})");
                } catch (\Exception $e) {
                    Log::error("Failed to send trial reminder to {$owner->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("Done. Total reminders sent: {$sent}");
        return Command::SUCCESS;
    }
}
