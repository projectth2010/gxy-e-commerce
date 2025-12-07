<?php

namespace App\Console\Commands;

use App\Services\SubscriptionMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class GenerateSubscriptionReport extends Command
{
    protected $signature = 'subscriptions:generate-report {--days=30 : Number of days to include in the report} {--email= : Email address to send the report to}';
    protected $description = 'Generate a subscription health report';

    protected $metrics;

    public function __construct(SubscriptionMetricsService $metrics)
    {
        parent::__construct();
        $this->metrics = $metrics;
    }

    public function handle()
    {
        $days = (int) $this->option('days');
        $email = $this->option('email');
        
        $this->info("Generating subscription health report for the last {$days} days...");
        
        // Collect metrics
        $report = [
            'period' => [
                'start' => now()->subDays($days)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
                'generated_at' => now()->toDateTimeString(),
            ],
            'overview' => [
                'active_subscriptions' => $this->metrics->getActiveSubscriptionsCount(),
                'trial_subscriptions' => $this->metrics->getTrialSubscriptionsCount(),
                'total_customers' => $this->metrics->getTotalCustomerCount(),
                'paid_users' => $this->metrics->getPaidUserCount(),
            ],
            'financials' => [
                'mrr' => $this->metrics->getMRR(),
                'arr' => $this->metrics->getARR(),
                'arpu' => $this->metrics->getARPU(),
                'ltv' => $this->metrics->getLTV(),
                'mrr_growth' => $this->metrics->getMRRGrowth($days),
            ],
            'health_metrics' => [
                'churn_rate' => $this->metrics->getChurnRate($days),
                'trial_conversion_rate' => $this->metrics->getTrialConversionRate($days),
                'renewal_success_rate' => $this->metrics->getRenewalSuccessRate($days),
                'recent_payment_failures' => $this->metrics->getRecentPaymentFailures(24),
            ],
            'activity' => [
                'new_subscriptions' => $this->metrics->getNewSubscriptionsCount($days),
                'cancellations' => $this->metrics->getCancellationsCount($days),
                'trials_ending_soon' => $this->metrics->getTrialsEndingSoonCount(7),
                'expiring_cards' => $this->metrics->getExpiringCardsCount(30),
            ],
            'trends' => [
                'mrr_trend' => $this->metrics->getMRRTrend($days),
                'churn_trend' => $this->metrics->getChurnTrend(min($days * 2, 90)),
            ],
        ];

        // Format and display the report
        $this->displayReport($report);
        
        // Send email if requested
        if ($email) {
            $this->sendEmailReport($email, $report);
        }
        
        return 0;
    }
    
    protected function displayReport(array $report): void
    {
        $this->newLine();
        $this->info('=== SUBSCRIPTION HEALTH REPORT ===');
        $this->info("Period: {$report['period']['start']} to {$report['period']['end']}");
        $this->info("Generated at: {$report['period']['generated_at']}");
        $this->newLine();
        
        $this->info('--- OVERVIEW ---');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Active Subscriptions', number_format($report['overview']['active_subscriptions'])],
                ['Trial Subscriptions', number_format($report['overview']['trial_subscriptions'])],
                ['Total Customers', number_format($report['overview']['total_customers'])],
                ['Paid Users', number_format($report['overview']['paid_users'])],
            ]
        );
        
        $this->info('--- FINANCIALS ---');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Monthly Recurring Revenue (MRR)', '$' . number_format($report['financials']['mrr'], 2)],
                ['Annual Recurring Revenue (ARR)', '$' . number_format($report['financials']['arr'], 2)],
                ['Avg. Revenue Per User (ARPU)', '$' . number_format($report['financials']['arpu'], 2)],
                ['Lifetime Value (LTV)', '$' . number_format($report['financials']['ltv'], 2)],
                ['MRR Growth (30d)', number_format($report['financials']['mrr_growth'], 2) . '%'],
            ]
        );
        
        $this->info('--- HEALTH METRICS ---');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Churn Rate', number_format($report['health_metrics']['churn_rate'], 2) . '%'],
                ['Trial Conversion Rate', number_format($report['health_metrics']['trial_conversion_rate'], 2) . '%'],
                ['Renewal Success Rate', number_format($report['health_metrics']['renewal_success_rate'], 2) . '%'],
                ['Recent Payment Failures (24h)', $report['health_metrics']['recent_payment_failures']],
            ]
        );
        
        $this->info('--- RECENT ACTIVITY ---');
        $this->table(
            ['Metric', 'Value'],
            [
                ['New Subscriptions', number_format($report['activity']['new_subscriptions'])],
                ['Cancellations', number_format($report['activity']['cancellations'])],
                ['Trials Ending Soon', number_format($report['activity']['trials_ending_soon'])],
                ['Expiring Cards (30d)', number_format($report['activity']['expiring_cards'])],
            ]
        );
    }
    
    protected function sendEmailReport(string $email, array $report): void
    {
        try {
            Mail::to($email)
                ->send(new \App\Mail\SubscriptionReport($report));
                
            $this->info("Report sent to {$email}");
        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
        }
    }
}
