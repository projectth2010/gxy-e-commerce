<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendSubscriptionReminders::class,
        \App\Console\Commands\MonitorSubscriptionHealth::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Send subscription reminders daily at 9 AM
        $schedule->command('subscriptions:send-reminders')
                 ->dailyAt('09:00');
                 
        // Monitor subscription health daily at 3 AM
        $schedule->command('subscriptions:monitor')
                 ->dailyAt('03:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
