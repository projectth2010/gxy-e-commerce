<?php

namespace App\Console\Commands;

use App\Models\TenantPlanAssignment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:send-reminders';
    protected $description = 'Send email reminders for expiring trials and subscriptions';

    public function handle(NotificationService $notificationService)
    {
        $this->info('Sending subscription reminders...');
        
        // Send trial ending reminders (3 days before)
        $this->sendTrialEndingReminders($notificationService);
        
        // Send subscription ending reminders (7 days before)
        $this->sendSubscriptionEndingReminders($notificationService);
        
        $this->info('Reminders sent successfully!');
    }
    
    protected function sendTrialEndingReminders(NotificationService $notificationService)
    {
        $upcomingTrials = TenantPlanAssignment::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now()->addDays(3))
            ->where('trial_ends_at', '>', now())
            ->whereDoesntHave('notifications', function($query) {
                $query->where('type', 'App\\Notifications\\SubscriptionNotification')
                    ->where('data->event', 'trial_ending');
            })
            ->with('tenant')
            ->get();
            
        foreach ($upcomingTrials as $subscription) {
            $notificationService->sendTrialEndingNotification(
                $subscription->tenant, 
                $subscription,
                3 // days before
            );
            
            $this->info("Trial ending reminder sent for tenant: " . $subscription->tenant->id);
        }
    }
    
    protected function sendSubscriptionEndingReminders(NotificationService $notificationService)
    {
        $upcomingSubscriptions = TenantPlanAssignment::whereNotNull('ends_at')
            ->where('ends_at', '<=', now()->addDays(7))
            ->where('ends_at', '>', now())
            ->whereDoesntHave('notifications', function($query) {
                $query->where('type', 'App\\Notifications\\SubscriptionNotification')
                    ->where('data->event', 'subscription_ending');
            })
            ->with('tenant')
            ->get();
            
        foreach ($upcomingSubscriptions as $subscription) {
            $notificationService->sendSubscriptionEndingNotification(
                $subscription->tenant, 
                $subscription,
                7 // days before
            );
            
            $this->info("Subscription ending reminder sent for tenant: " . $subscription->tenant->id);
        }
    }
}
