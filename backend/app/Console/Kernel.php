<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendSubscriptionReminders::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Send subscription reminders daily at 9 AM
        $schedule->command('subscriptions:send-reminders')
                 ->dailyAt('09:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
