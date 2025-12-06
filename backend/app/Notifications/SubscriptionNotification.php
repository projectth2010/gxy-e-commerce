<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;
    protected $data;

    public function __construct($event, $data = [])
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = $this->getSubject();
        $greeting = $this->getGreeting();
        $message = $this->getMessage();
        
        $actionUrl = url('/dashboard/subscription');
        $actionText = 'View Subscription';
        
        if ($this->event === 'payment_failed' && !empty($this->data['update_payment_method_url'])) {
            $actionUrl = $this->data['update_payment_method_url'];
            $actionText = 'Update Payment Method';
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.subscription', [
                'greeting' => $greeting,
                'message' => $message,
                'actionUrl' => $actionUrl,
                'actionText' => $actionText,
                'outroLines' => [
                    'If you have any questions, please contact our support team.',
                    'Thank you for using our application!',
                ]
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'event' => $this->event,
            'message' => $this->getMessage(),
            'data' => $this->data,
            'url' => '/dashboard/subscription',
        ];
    }

    protected function getSubject()
    {
        $subjects = [
            'subscription_created' => 'Your Subscription Has Been Created',
            'subscription_updated' => 'Your Subscription Has Been Updated',
            'subscription_cancelled' => 'Your Subscription Has Been Cancelled',
            'payment_succeeded' => 'Payment Received',
            'payment_failed' => 'Payment Failed',
            'trial_ending' => 'Your Trial is Ending Soon',
            'subscription_ending' => 'Your Subscription is Ending Soon',
        ];

        return $subjects[$this->event] ?? 'Notification';
    }

    protected function getGreeting()
    {
        return match($this->event) {
            'payment_failed' => 'Payment Failed!',
            'subscription_cancelled' => 'Subscription Cancelled',
            default => 'Hello!',
        };
    }

    protected function getMessage()
    {
        $messages = [
            'subscription_created' => 'Your subscription has been successfully created.',
            'subscription_updated' => 'Your subscription has been updated.',
            'subscription_cancelled' => 'Your subscription has been cancelled.',
            'payment_succeeded' => 'Your payment of ' . ($this->data['amount'] ?? '') . ' has been processed.',
            'payment_failed' => 'We were unable to process your payment. Please update your payment information.',
            'trial_ending' => 'Your trial period will end on ' . ($this->data['trial_ends_at'] ?? '') . '.',
            'subscription_ending' => 'Your subscription will end on ' . ($this->data['ends_at'] ?? '') . '.',
        ];

        return $messages[$this->event] ?? 'You have a new notification.';
    }
}
