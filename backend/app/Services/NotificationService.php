<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantPlanAssignment;
use App\Notifications\SubscriptionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function sendSubscriptionCreated(Tenant $tenant, TenantPlanAssignment $subscription)
    {
        $this->notifyTenant(
            $tenant,
            'subscription_created',
            [
                'plan_name' => $subscription->plan->name,
                'amount' => $subscription->plan->price,
                'billing_cycle' => $subscription->billing_cycle,
                'trial_ends_at' => $subscription->trial_ends_at?->toDateString(),
            ]
        );
    }

    public function sendSubscriptionUpdated(Tenant $tenant, TenantPlanAssignment $subscription)
    {
        $this->notifyTenant(
            $tenant,
            'subscription_updated',
            [
                'plan_name' => $subscription->plan->name,
                'amount' => $subscription->plan->price,
                'billing_cycle' => $subscription->billing_cycle,
            ]
        );
    }

    public function sendSubscriptionCancelled(Tenant $tenant, TenantPlanAssignment $subscription)
    {
        $this->notifyTenant(
            $tenant,
            'subscription_cancelled',
            [
                'plan_name' => $subscription->plan->name,
                'ends_at' => $subscription->ends_at?->toDateString(),
            ]
        );
    }

    public function sendPaymentSucceeded(Tenant $tenant, $amount, $invoiceUrl = null)
    {
        $this->notifyTenant(
            $tenant,
            'payment_succeeded',
            [
                'amount' => $amount,
                'invoice_url' => $invoiceUrl,
                'paid_at' => now()->toDateTimeString(),
            ]
        );
    }

    public function sendPaymentFailed(Tenant $tenant, $amount, $nextAction = null)
    {
        $this->notifyTenant(
            $tenant,
            'payment_failed',
            [
                'amount' => $amount,
                'failed_at' => now()->toDateTimeString(),
                'next_action' => $nextAction,
            ]
        );
    }

    public function sendTrialEndingNotification(Tenant $tenant, TenantPlanAssignment $subscription, int $daysBefore = 3)
    {
        if (!$subscription->onTrial()) {
            return;
        }

        $trialEndsAt = $subscription->trial_ends_at;
        $notificationDate = $trialEndsAt->copy()->subDays($daysBefore);

        if (now()->greaterThanOrEqualTo($notificationDate)) {
            $this->notifyTenant(
                $tenant,
                'trial_ending',
                [
                    'plan_name' => $subscription->plan->name,
                    'trial_ends_at' => $trialEndsAt->toDateString(),
                    'days_remaining' => now()->diffInDays($trialEndsAt),
                ]
            );
        }
    }

    public function sendSubscriptionEndingNotification(Tenant $tenant, TenantPlanAssignment $subscription, int $daysBefore = 7)
    {
        if (!$subscription->ends_at) {
            return;
        }

        $endsAt = $subscription->ends_at;
        $notificationDate = $endsAt->copy()->subDays($daysBefore);

        if (now()->greaterThanOrEqualTo($notificationDate)) {
            $this->notifyTenant(
                $tenant,
                'subscription_ending',
                [
                    'plan_name' => $subscription->plan->name,
                    'ends_at' => $endsAt->toDateString(),
                    'days_remaining' => now()->diffInDays($endsAt),
                ]
            );
        }
    }

    protected function notifyTenant(Tenant $tenant, string $event, array $data = [])
    {
        // Get users who should receive notifications
        $users = $tenant->users()->where('receive_notifications', true)->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new SubscriptionNotification($event, $data));
        }

        // Log the notification
        \Log::info('Notification sent', [
            'tenant_id' => $tenant->id,
            'event' => $event,
            'data' => $data,
            'users_notified' => $users->count(),
        ]);
    }
}
