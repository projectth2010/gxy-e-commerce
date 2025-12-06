<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use App\Notifications\SubscriptionNotification;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    protected $signature = 'notification:test {event} {--tenant=} {--plan=}';
    protected $description = 'Test notification system';

    public function handle()
    {
        $event = $this->argument('event');
        $tenantId = $this->option('tenant');
        $planId = $this->option('plan');

        $tenant = Tenant::find($tenantId) ?? Tenant::first();
        
        if (!$tenant) {
            $this->error('No tenant found. Please create a tenant first.');
            return 1;
        }

        $subscription = null;
        if ($planId) {
            $subscription = TenantPlanAssignment::where('tenant_id', $tenant->id)
                ->where('plan_id', $planId)
                ->first();
        }

        $data = [];
        
        switch ($event) {
            case 'trial_ending':
                $subscription = $subscription ?? $tenant->activeSubscription;
                $data = [
                    'trial_ends_at' => now()->addDays(3)->toDateString(),
                    'plan_name' => $subscription->plan->name ?? 'Test Plan',
                ];
                break;
                
            case 'subscription_ending':
                $subscription = $subscription ?? $tenant->activeSubscription;
                $data = [
                    'ends_at' => now()->addDays(7)->toDateString(),
                    'plan_name' => $subscription->plan->name ?? 'Test Plan',
                ];
                break;
                
            case 'payment_succeeded':
                $data = [
                    'amount' => 29.99,
                    'invoice_url' => 'https://example.com/invoice/123',
                ];
                break;
                
            case 'payment_failed':
                $subscription = $subscription ?? $tenant->activeSubscription;
                if ($subscription) {
                    app(\App\Services\NotificationService::class)->sendPaymentFailed(
                        $tenant,
                        $subscription,
                        'Insufficient funds',
                        now()->addDays(3)->toDateString(),
                        'Please update your payment method to avoid service interruption.'
                    );
                    $this->info('Payment failed notification sent for subscription.');
                    return 0;
                } else {
                    app(\App\Services\NotificationService::class)->sendPaymentFailed(
                        $tenant,
                        29.99,
                        'Insufficient funds',
                        now()->addDays(3)->toDateString(),
                        'Please update your payment method to avoid service interruption.'
                    );
                    $this->info('Payment failed notification sent with amount.');
                    return 0;
                }
        }

        $user = $tenant->users()->first();
        
        if (!$user) {
            $this->error('No users found for this tenant.');
            return 1;
        }

        $user->notify(new SubscriptionNotification($event, $data));
        
        $this->info("Notification sent for event: {$event}");
        $this->line("Sent to: {$user->email}");
        
        return 0;
    }
}
