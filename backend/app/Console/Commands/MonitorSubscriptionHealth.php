<?php

namespace App\Console\Commands;

use App\Services\SubscriptionMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorSubscriptionHealth extends Command
{
    protected $signature = 'subscriptions:monitor';
    protected $description = 'Monitor subscription health and send alerts if needed';

    protected SubscriptionMetricsService $metrics;

    public function __construct(SubscriptionMetricsService $metrics)
    {
        parent::__construct();
        $this->metrics = $metrics;
    }

    public function handle()
    {
        $this->checkFailedPayments();
        $this->checkHighChurnRate();
        $this->logMetrics();
    }

    protected function checkFailedPayments(int $threshold = 5): void
    {
        $failedPayments = $this->metrics->getFailedPayments(1); // Last 24 hours
        
        if ($failedPayments >= $threshold) {
            $message = "High number of failed payments detected: {$failedPayments}";
            $this->alertTeam($message, 'Failed Payment Alert');
        }
    }

    protected function checkHighChurnRate(float $threshold = 5.0): void
    {
        $churnRate = $this->metrics->getChurnRate(7); // 7-day churn rate
        
        if ($churnRate >= $threshold) {
            $message = "High churn rate detected: {$churnRate}% over the last 7 days";
            $this->alertTeam($message, 'High Churn Rate Alert');
        }
    }

    protected function logMetrics(): void
    {
        $metrics = [
            'active_subscriptions' => $this->metrics->getActiveSubscriptionsCount(),
            'trial_subscriptions' => $this->metrics->getTrialSubscriptionsCount(),
            'mrr' => $this->metrics->getMRR(),
            'churn_rate_7d' => $this->metrics->getChurnRate(7),
            'churn_rate_30d' => $this->metrics->getChurnRate(30),
        ];

        Log::info('Subscription metrics', $metrics);
    }

    protected function alertTeam(string $message, string $subject): void
    {
        // Log the alert
        Log::warning($subject . ': ' . $message);

        // In production, you might want to send an email or notification
        if (app()->environment('production')) {
            // Example: Send email to admin
            // Mail::to(config('app.admin_email'))->send(new AlertMail($subject, $message));
            
            // Or send to a notification channel (Slack, Discord, etc.)
            // Notification::route('slack', config('services.slack.webhook_url'))
            //     ->notify(new SubscriptionAlert($subject, $message));
        }
    }
}
