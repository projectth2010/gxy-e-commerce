<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MonitorSubscriptionLogs extends Command
{
    protected $signature = 'subscription:monitor-logs {--hours=24 : Number of hours to look back} {--level=error : Minimum log level to check}';
    protected $description = 'Monitor subscription logs for errors and send alerts';

    protected $logLevels = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7,
    ];

    public function handle()
    {
        $logFile = storage_path('logs/subscription.log');
        $hours = (int) $this->option('hours');
        $minLevel = strtolower($this->option('level'));
        
        if (!file_exists($logFile)) {
            $this->info('No subscription log file found.');
            return 0;
        }

        $logs = $this->parseLogFile($logFile, $hours, $minLevel);
        
        if (empty($logs)) {
            $this->info('No matching log entries found.');
            return 0;
        }

        $this->sendLogReport($logs, $hours);
        $this->info('Log monitoring completed. Found ' . count($logs) . ' matching entries.');
        
        return 0;
    }

    protected function parseLogFile(string $filePath, int $hours, string $minLevel): array
    {
        $minLevelValue = $this->logLevels[$minLevel] ?? $this->logLevels['error'];
        $cutoffTime = now()->subHours($hours);
        $logs = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return [];
        }

        $currentLog = [];
        
        while (($line = fgets($handle)) !== false) {
            // Try to match the log entry pattern
            if (preg_match('/^\[(.*?)\]\s(\w+)\.(\w+):\s(.*)$/', $line, $matches)) {
                // If we have a current log, save it before starting a new one
                if (!empty($currentLog)) {
                    $this->processLogEntry($currentLog, $logs, $minLevelValue, $cutoffTime);
                }
                
                $currentLog = [
                    'timestamp' => $matches[1],
                    'level' => strtolower($matches[3]),
                    'message' => $matches[4],
                    'stack' => ''
                ];
            } elseif (!empty($currentLog)) {
                // Append to the current log's stack trace
                $currentLog['stack'] .= $line;
            }
        }
        
        // Process the last log entry
        if (!empty($currentLog)) {
            $this->processLogEntry($currentLog, $logs, $minLevelValue, $cutoffTime);
        }
        
        fclose($handle);
        
        return $logs;
    }
    
    protected function processLogEntry(array $log, array &$logs, int $minLevelValue, Carbon $cutoffTime): void
    {
        if (!isset($this->logLevels[$log['level']])) {
            return;
        }
        
        $logLevelValue = $this->logLevels[$log['level']];
        $logTime = Carbon::createFromFormat('Y-m-d H:i:s', $log['timestamp']);
        
        if ($logLevelValue >= $minLevelValue && $logTime->greaterThanOrEqualTo($cutoffTime)) {
            $logs[] = [
                'time' => $log['timestamp'],
                'level' => strtoupper($log['level']),
                'message' => $log['message'],
                'stack' => $log['stack']
            ];
        }
    }
    
    protected function sendLogReport(array $logs, int $hours): void
    {
        $subject = 'Subscription Log Alert - ' . count($logs) . ' issues found in the last ' . $hours . ' hours';
        
        // Group logs by level
        $groupedLogs = collect($logs)->groupBy('level')->sortKeys();
        
        $emailContent = view('emails.subscription.log-alert', [
            'logs' => $groupedLogs,
            'totalLogs' => count($logs),
            'timePeriod' => $hours . ' hours',
            'reportTime' => now()->format('Y-m-d H:i:s T')
        ]);
        
        // Send email to admin
        if (app()->environment('production')) {
            try {
                $adminEmail = config('mail.admin_email');
                if ($adminEmail) {
                    Mail::to($adminEmail)
                        ->cc(config('mail.cc_emails', []))
                        ->send(new \App\Mail\SubscriptionLogAlert($subject, $emailContent->render()));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send subscription log alert: ' . $e->getMessage());
            }
        }
        
        // Output to console for local development
        $this->table(
            ['Time', 'Level', 'Message'],
            array_map(function($log) {
                return [
                    $log['time'],
                    $log['level'],
                    substr($log['message'], 0, 100) . (strlen($log['message']) > 100 ? '...' : '')
                ];
            }, $logs)
        );
    }
}
