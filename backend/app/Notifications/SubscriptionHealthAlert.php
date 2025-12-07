<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SubscriptionHealthAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The alert subject.
     *
     * @var string
     */
    public $subject;

    /**
     * The alert message.
     *
     * @var string
     */
    public $message;

    /**
     * The alert level (info, warning, error, critical).
     *
     * @var string
     */
    public $level;

    /**
     * Create a new notification instance.
     *
     * @param string $subject
     * @param string $message
     * @param string $level
     * @return void
     */
    public function __construct(string $subject, string $message, string $level = 'info')
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->level = strtolower($level);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'slack'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject("[Action Required] " . $this->subject)
            ->line($this->message)
            ->line('This is an automated alert from the subscription monitoring system.');

        // Add different styling based on alert level
        switch ($this->level) {
            case 'critical':
            case 'error':
                $mailMessage->error();
                $mailMessage->line('**This issue requires immediate attention.**');
                break;
            case 'warning':
                $mailMessage->warning();
                $mailMessage->line('**Please review this issue at your earliest convenience.**');
                break;
            default:
                $mailMessage->line('No immediate action is required.');
                break;
        }

        $mailMessage->action('View Dashboard', url('/admin/subscriptions'))
                   ->line('Thank you for using our service!');

        return $mailMessage;
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable)
    {
        $slackMessage = (new SlackMessage)
            ->content('*' . $this->subject . '*\n' . $this->message);

        // Set appropriate emoji and color based on alert level
        switch ($this->level) {
            case 'critical':
                $slackMessage->error()
                    ->content('🚨 *CRITICAL*: ' . $this->subject . '\n' . $this->message);
                break;
            case 'error':
                $slackMessage->error()
                    ->content('⚠️ *ERROR*: ' . $this->subject . '\n' . $this->message);
                break;
            case 'warning':
                $slackMessage->warning()
                    ->content('⚠️ *WARNING*: ' . $this->subject . '\n' . $this->message);
                break;
            default:
                $slackMessage->success()
                    ->content('ℹ️ *INFO*: ' . $this->subject . '\n' . $this->message);
                break;
        }

        return $slackMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
            'level' => $this->level,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
