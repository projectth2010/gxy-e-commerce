<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionHealthAlert extends Mailable
{
    use Queueable, SerializesModels;

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
     * Create a new message instance.
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
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $prefix = '';
        
        // Add prefix based on alert level
        switch ($this->level) {
            case 'critical':
                $prefix = '[CRITICAL] ';
                break;
            case 'error':
                $prefix = '[ERROR] ';
                break;
            case 'warning':
                $prefix = '[WARNING] ';
                break;
        }
        
        return new Envelope(
            subject: $prefix . $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription.health-alert',
            with: [
                'subject' => $this->subject,
                'message' => $this->message,
                'level' => $this->level,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
