<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendSubscriptionReminders::class,
        \App\Console\Commands\MonitorSubscriptionHealth::class,
        \App\Console\Commands\RunSubscriptionMonitoring::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run subscription health check daily at 9 AM
        $schedule->command('subscriptions:run-monitoring')
                 ->dailyAt('09:00')
                 ->onOneServer()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/subscription-monitor.log'));
                 
        // Monitor subscription logs every hour
        $schedule->command('subscription:monitor-logs', [
            '--hours' => 24,
            '--level' => 'error'
        ])->hourly()
          ->onOneServer()
          ->runInBackground()
          ->emailOutputTo(config('mail.admin_email'));
                 
        // Optional: Run more frequently during business hours
        if (app()->environment('production')) {
            $schedule->command('subscriptions:run-monitoring')
                     ->weekdays()
                     ->hourly()
                     ->between('9:00', '18:00')
                     ->onOneServer()
                     ->runInBackground()
                     ->appendOutputTo(storage_path('logs/subscription-monitor-hourly.log'));
        }
        
        // Send subscription reminders daily at 9 AM
        $schedule->command('subscriptions:send-reminders')
                 ->dailyAt('09:00');
                 
        // Update subscription metrics hourly
        $schedule->command('subscription:update-metrics')
                 ->hourly()
                 ->onOneServer()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/subscription-metrics.log'));
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
