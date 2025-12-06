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
        // Use custom template for payment failed notifications
        if ($this->event === 'payment_failed') {
            return $this->buildPaymentFailedMail($notifiable);
        }

        $subject = $this->getSubject();
        $greeting = $this->getGreeting();
        $message = $this->getMessage();
        
        $actionUrl = url('/dashboard/subscription');
        $actionText = 'View Subscription';

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

    /**
     * Build the payment failed mail message.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildPaymentFailedMail($notifiable)
    {
        $data = array_merge([
            'user' => $notifiable,
            'amount' => $this->data['amount'] ?? null,
            'plan_name' => $this->data['plan_name'] ?? null,
            'reason' => $this->data['reason'] ?? 'Payment processing failed',
            'next_retry_date' => $this->data['next_retry_date'] ?? null,
            'update_payment_url' => $this->data['update_payment_url'] ?? null,
        ], $this->data);

        return (new MailMessage)
            ->subject('Payment Failed - ' . config('app.name'))
            ->view('emails.payment-failed', $data);
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
