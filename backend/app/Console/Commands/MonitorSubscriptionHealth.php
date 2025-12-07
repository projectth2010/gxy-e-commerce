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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        
        try {
            $this->info('Starting subscription health check...');
            
            // Run all health checks
            $metrics = $this->checkSubscriptionHealth();
            
            // Check for critical issues
            $this->checkFailedPayments();
            $this->checkHighChurnRate();
            $this->checkExpiringCards();
            $this->checkTrialsEndingSoon();
            $this->checkMrrTrend();
            $this->checkCustomerHealthScore();
            
            // Check for alerts based on metrics
            $alerts = $this->checkForAlerts($metrics);
            
            // Send alerts if any
            if (!empty($alerts)) {
                $this->info(sprintf('Found %d alerts that need attention.', count($alerts)));
                
                foreach ($alerts as $alert) {
                    $this->alertTeam(
                        $alert['message'],
                        $alert['message'],
                        $alert['level']
                    );
                }
            } else {
                $this->info('No critical alerts detected.');
            }
            
            // Log metrics
            $this->logMetrics();
            
            // Generate daily summary if it's the right time
            if ($this->shouldGenerateDailySummary()) {
                $this->info('Generating daily subscription summary...');
                $this->generateDailySummary();
            }
            
            $this->info('Subscription health check completed successfully.');
            
        } catch (\Exception $e) {
            $this->error('Error during subscription health check: ' . $e->getMessage());
            Log::error('Subscription health check failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Alert team about the failure
            $this->alertTeam(
                'Subscription Health Check Failed',
                'An error occurred during the subscription health check: ' . $e->getMessage(),
                'critical'
            );
            
            return 1;
        } finally {
            // Log performance metrics
            $this->logPerformance($startTime);
        }
        
        return 0;
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

    /**
     * Check for expiring payment methods
     */
    protected function checkExpiringCards(int $daysAhead = 30): void
    {
        $expiringCount = $this->metrics->getExpiringCardsCount($daysAhead);
        
        if ($expiringCount > 0) {
            $this->alertTeam(
                'Expiring Payment Methods',
                "$expiringCount payment methods will expire in the next $daysAhead days. Consider notifying customers to update their payment information.",
                'warning'
            );
        }
    }
    
    /**
     * Check for trials ending soon
     */
    protected function checkTrialsEndingSoon(int $daysAhead = 7): void
    {
        $endingSoonCount = $this->metrics->getTrialsEndingSoonCount($daysAhead);
        
        if ($endingSoonCount > 0) {
            $this->alertTeam(
                'Trials Ending Soon',
                "$endingSoonCount trial subscriptions will end in the next $daysAhead days. Consider sending reminders to these users.",
                'info'
            );
        }
    }
    
    /**
     * Calculate and check customer health score
     */
    protected function checkCustomerHealthScore(): void
    {
        $healthScore = $this->metrics->calculateCustomerHealthScore();
        
        if ($healthScore < 50) {
            $this->alertTeam(
                'Low Customer Health Score',
                "Customer health score is low: $healthScore/100. This indicates potential issues with customer satisfaction or engagement.",
                'warning'
            );
        }
    }
    
    /**
     * Check MRR trend for significant changes
     */
    protected function checkMrrTrend(int $days = 30): void
    {
        $trend = $this->metrics->getMRRTrend($days);
        
        if ($trend < -5.0) {
            $this->alertTeam(
                'Declining MRR Trend',
                "MRR has decreased by " . abs($trend) . "% over the last $days days. This could indicate churn or downgrades.",
                'error'
            );
        } elseif ($trend > 10.0) {
            $this->alertTeam(
                'Rapid MRR Growth',
                "MRR has increased by $trend% over the last $days days. This significant growth may require attention.",
                'info'
            );
        }
    }
    
    /**
     * Log performance metrics for the monitoring process
     */
    protected function logPerformance(float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);
        $memory = round(memory_get_peak_usage(true) / 1024 / 1024, 2); // MB
        
        Log::channel('subscription')->info('Subscription monitoring completed', [
            'duration_seconds' => $duration,
            'memory_used_mb' => $memory,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
    
    /**
     * Check if we should generate a daily summary report
     */
    protected function shouldGenerateDailySummary(): bool
    {
        $lastRun = Cache::get('subscription:last_daily_summary');
        
        // Only generate once per day, around the same time
        if ($lastRun && now()->diffInHours($lastRun) < 23) {
            return false;
        }
        
        // Only run during business hours (8 AM to 6 PM)
        $hour = (int) now()->format('H');
        return $hour >= 8 && $hour < 18;
    }
    
    /**
     * Generate and send a daily summary report
     */
    protected function generateDailySummary(): void
    {
        try {
            $metrics = $this->checkSubscriptionHealth();
            
            // Add additional context
            $metrics['report_date'] = now()->format('Y-m-d');
            $metrics['report_generated_at'] = now()->toDateTimeString();
            
            // Send the report
            $adminEmail = config('mail.admin_email');
            if ($adminEmail) {
                Mail::to($adminEmail)
                    ->queue(new \App\Mail\SubscriptionDailySummary($metrics));
                
                // Update last run time
                Cache::put('subscription:last_daily_summary', now(), now()->addDay());
                
                $this->info('Daily subscription summary sent to ' . $adminEmail);
            }
            
        } catch (\Exception $e) {
            Log::channel('subscription')->error('Failed to generate daily summary: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->error('Error generating daily summary: ' . $e->getMessage());
        }
    }
    
    /**
     * Log subscription metrics to the subscription channel
     */
    protected function logMetrics(): void
    {
        $metrics = $this->checkSubscriptionHealth();
        Log::channel('subscription')->info('Subscription metrics collected', $metrics);
    }

    protected function checkSubscriptionHealth(): array
    {
        $metrics = $this->metrics;
        
        // Core metrics
        $activeSubs = $metrics->getActiveSubscriptionsCount();
        $trialSubs = $metrics->getTrialSubscriptionsCount();
        $mrr = $metrics->getMRR();
        $churnRate = $metrics->getChurnRate();
        $trialConversionRate = $metrics->getTrialConversionRate();
        $renewalSuccessRate = $metrics->getRenewalSuccessRate();
        
        // Calculate health score (0-100)
        $healthScore = $this->calculateHealthScore([
            'churn_rate' => $churnRate,
            'trial_conversion' => $trialConversionRate,
            'renewal_success' => $renewalSuccessRate,
            'payment_failures' => $metrics->getRecentPaymentFailures(),
        ]);
        
        // Get trends
        $mrrTrend = $metrics->getMRRTrend(30);
        $churnTrend = $metrics->getChurnTrend(90);
        
        // Check for critical issues
        $alerts = $this->checkForAlerts([
            'churn_rate' => $churnRate,
            'trial_conversion' => $trialConversionRate,
            'renewal_success' => $renewalSuccessRate,
            'payment_failures' => $metrics->getRecentPaymentFailures(),
            'expiring_cards' => $metrics->getExpiringCardsCount(),
            'trials_ending_soon' => $metrics->getTrialsEndingSoonCount(),
        ]);
        
        return [
            // Core Metrics
            'active_subscriptions' => $activeSubs,
            'trial_subscriptions' => $trialSubs,
            'total_customers' => $metrics->getTotalCustomerCount(),
            'paid_users' => $metrics->getPaidUserCount(),
            'mrr' => $mrr,
            'arr' => $metrics->getARR(),
            'arpu' => $metrics->getARPU(),
            'ltv' => $metrics->getLTV(),
            'churn_rate' => $churnRate,
            'trial_conversion_rate' => $trialConversionRate,
            'renewal_success_rate' => $renewalSuccessRate,
            'recent_payment_failures' => $metrics->getRecentPaymentFailures(),
            'new_subscriptions' => $metrics->getNewSubscriptionsCount(),
            'cancellations' => $metrics->getCancellationsCount(),
            'trials_ending_soon' => $metrics->getTrialsEndingSoonCount(),
            'expiring_cards' => $metrics->getExpiringCardsCount(),
            
            // Health & Trends
            'health_score' => $healthScore,
            'mrr_trend' => $mrrTrend,
            'churn_trend' => $churnTrend,
            'alerts' => $alerts,
            'last_checked' => now()->toDateTimeString(),
        ];    

        // Log metrics
        Log::channel('subscription')->info('Subscription metrics collected', $metrics);
        
        return $metrics;
    }

    protected function generateAlerts(array $metrics): void
    {
        // Implement alert generation logic here
    }

    /**
     * Calculate a health score based on key metrics
     */
    protected function calculateHealthScore(array $metrics): int
    {
        $score = 100;
        
        // Deduct points based on churn rate (higher is worse)
        $churnDeduction = min(30, $metrics['churn_rate'] * 2);
        $score -= $churnDeduction;
        
        // Add points for trial conversion (higher is better)
        $conversionBonus = $metrics['trial_conversion'] * 0.2;
        $score += $conversionBonus;
        
        // Deduct for payment failures
        $failureDeduction = min(20, $metrics['payment_failures'] * 2);
        $score -= $failureDeduction;
        
        // Ensure score is between 0 and 100
        return max(0, min(100, (int) round($score)));
    }
    
    /**
     * Check for critical issues that need alerts
     */
    protected function checkForAlerts(array $metrics): array
    {
        $alerts = [];
        
        // High churn rate
        if ($metrics['churn_rate'] > 5.0) {
            $alerts[] = [
                'level' => 'critical',
                'message' => "High churn rate: {$metrics['churn_rate']}% over the last 30 days",
                'metric' => 'churn_rate',
                'value' => $metrics['churn_rate'],
                'threshold' => 5.0
            ];
        }
        
        // Low trial conversion
        if ($metrics['trial_conversion'] < 20.0) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "Low trial conversion rate: {$metrics['trial_conversion']}%",
                'metric' => 'trial_conversion',
                'value' => $metrics['trial_conversion'],
                'threshold' => 20.0
            ];
        }
        
        // Payment failures
        if ($metrics['payment_failures'] > 0) {
            $alerts[] = [
                'level' => $metrics['payment_failures'] > 5 ? 'critical' : 'warning',
                'message' => "{$metrics['payment_failures']} payment failures in the last 24 hours",
                'metric' => 'payment_failures',
                'value' => $metrics['payment_failures'],
                'threshold' => 0
            ];
        }
        
        // Expiring payment methods
        if ($metrics['expiring_cards'] > 0) {
            $alerts[] = [
                'level' => 'info',
                'message' => "{$metrics['expiring_cards']} payment methods expiring soon",
                'metric' => 'expiring_cards',
                'value' => $metrics['expiring_cards'],
                'threshold' => 0
            ];
        }
        
        // Trials ending soon
        if ($metrics['trials_ending_soon'] > 0) {
            $alerts[] = [
                'level' => 'info',
                'message' => "{$metrics['trials_ending_soon']} trials ending soon",
                'metric' => 'trials_ending_soon',
                'value' => $metrics['trials_ending_soon'],
                'threshold' => 0
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Alert the team about a subscription issue
     */
    
    /**
     * Alert the team about a subscription issue
     */
    protected function alertTeam(string $subject, string $message, string $level = 'warning', array $metrics = []): void
    {
        // Skip if alert is throttled
        if ($this->shouldThrottleAlert($subject)) {
            Log::channel('subscription')->info("Alert throttled: $subject");
            return;
        }

        // Log the alert with appropriate level
        $logContext = [
            'subject' => $subject,
            'level' => $level,
            'timestamp' => now()->toDateTimeString(),
            'metrics' => $metrics
        ];

        // Log based on level
        $logMethod = strtolower($level) ?: 'info';
        if (method_exists(Log::channel('subscription'), $logMethod)) {
            Log::channel('subscription')->$logMethod($message, $logContext);
        } else {
            Log::channel('subscription')->info($message, $logContext);
        }

        if (app()->environment('production') || config('subscription.alerts.enable_in_dev', false)) {
            try {
                $adminEmail = config('mail.admin_email');
                
                if (empty($adminEmail)) {
                    $this->warn('No admin email configured for alerts');
                    return;
                }
                
                // Send email notification
                Mail::to($adminEmail)
                    ->cc(config('mail.cc_emails', []))
                    ->queue(new \App\Mail\SubscriptionHealthAlert($subject, $message, $level, $metrics));

                // Send to notification channels
                $this->sendToNotificationChannels($subject, $message, $level, $metrics);
                
                $this->info("Alert sent: $subject");
                
            } catch (\Exception $e) {
                Log::channel('subscription')->error('Failed to send alert: ' . $e->getMessage(), [
                    'exception' => $e,
                    'subject' => $subject,
                    'level' => $level
                ]);
                
                $this->error("Failed to send alert: " . $e->getMessage());
            }
        } else {
            $this->info("[DEV] Alert would be sent: $subject - $message");
        }
    }

    /**
     * Check if an alert should be throttled to prevent notification spam
     */
    protected function shouldThrottleAlert(string $alertKey): bool
    {
        $cacheKey = 'alert_throttle:' . md5($alertKey);
        $throttleMinutes = config('subscription.alerts.throttle_minutes', 60);
        
        if (cache()->has($cacheKey)) {
            return true;
        }
        
        // Cache the alert to prevent duplicates
        cache()->put($cacheKey, true, now()->addMinutes($throttleMinutes));
        return false;
    }
    
    /**
     * Send notifications to all configured channels
     */
    protected function sendToNotificationChannels(string $subject, string $message, string $level, array $metrics = []): void
    {
        $notification = new \App\Notifications\SubscriptionHealthAlert($subject, $message, $level, $metrics);
        
        // Send to Slack for critical/error alerts
        if (in_array($level, ['critical', 'error']) && config('services.slack.webhook_url')) {
            Notification::route('slack', config('services.slack.webhook_url'))
                ->notify($notification);
        }
        
        // Send to Teams if configured
        if (config('services.teams.webhook_url')) {
            $teamsNotification = new \App\Notifications\TeamsSubscriptionAlert($subject, $message, $level, $metrics);
            
            // Only send non-info alerts to Teams by default
            if ($level !== 'info' || config('subscription.alerts.send_info_to_teams', false)) {
                Notification::route('teams', config('services.teams.webhook_url'))
                    ->notify($teamsNotification);
            }
        }
        
        // Add more notification channels here as needed
    }
}
