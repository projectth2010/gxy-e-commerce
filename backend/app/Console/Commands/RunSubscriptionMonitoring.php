<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionMetricsService;

class RunSubscriptionMonitoring extends Command
{
    protected $signature = 'subscriptions:run-monitoring';
    protected $description = 'Run the subscription monitoring process with detailed output';

    protected $subscriptionMonitor;

    public function __construct(SubscriptionMetricsService $metrics)
    {
        parent::__construct();
        $this->subscriptionMonitor = new MonitorSubscriptionHealth($metrics);
    }

    public function handle()
    {
        $this->info('Starting subscription monitoring process...');
        
        // Run the monitoring
        $result = $this->subscriptionMonitor->handle();
        
        if ($result === 0) {
            $this->info('Subscription monitoring completed successfully.');
        } else {
            $this->error('Subscription monitoring completed with errors.');
        }
        
        return $result;
    }
}
