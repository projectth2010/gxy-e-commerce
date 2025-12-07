<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class TeamsSubscriptionAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subject;
    protected $message;
    protected $level;
    protected $metrics;

    public function __construct(string $subject, string $message, string $level = 'info', array $metrics = [])
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->level = $level;
        $this->metrics = $metrics;
    }

    public function via($notifiable)
    {
        return ['teams'];
    }

    public function toTeams($notifiable)
    {
        $card = [
            'type' => 'MessageCard',
            'context' => 'http://schema.org/extensions',
            'themeColor' => $this->getThemeColor(),
            'summary' => $this->subject,
            'sections' => [
                [
                    'activityTitle' => $this->subject,
                    'activitySubtitle' => 'Subscription Alert',
                    'activityImage' => $this->getAlertIcon(),
                    'facts' => $this->getMetricFacts(),
                    'markdown' => true,
                    'text' => $this->message,
                ]
            ],
            'potentialAction' => [
                [
                    '@context' => 'http://schema.org',
                    '@type' => 'ViewAction',
                    'name' => 'View in Dashboard',
                    'target' => [
                        route('admin.subscriptions.dashboard')
                    ]
                ]
            ]
        ];

        return $card;
    }

    protected function getThemeColor(): string
    {
        return [
            'critical' => '#FF0000',
            'error' => '#FF6B6B',
            'warning' => '#FFD93D',
            'info' => '#36A2EB',
            'success' => '#4CAF50',
        ][$this->level] ?? '#36A2EB';
    }

    protected function getAlertIcon(): string
    {
        return [
            'critical' => 'https://img.icons8.com/color/96/000000/high-priority.png',
            'error' => 'https://img.icons8.com/color/96/000000/error.png',
            'warning' => 'https://img.icons8.com/color/96/000000/warning-shield.png',
            'info' => 'https://img.icons8.com/color/96/000000/info.png',
            'success' => 'https://img.icons8.com/color/96/000000/ok--v1.png',
        ][$this->level] ?? 'https://img.icons8.com/color/96/000000/info.png';
    }

    protected function getMetricFacts(): array
    {
        if (empty($this->metrics)) {
            return [];
        }

        $facts = [];
        foreach ($this->metrics as $key => $value) {
            if (is_numeric($value) && strpos($key, 'rate') !== false) {
                $value = number_format($value, 2) . '%';
            } elseif (is_numeric($value) && strpos($key, 'mrr') !== false) {
                $value = '$' . number_format($value, 2);
            }
            
            $facts[] = [
                'name' => ucwords(str_replace('_', ' ', $key)),
                'value' => $value
            ];
        }
        
        return $facts;
    }
}
