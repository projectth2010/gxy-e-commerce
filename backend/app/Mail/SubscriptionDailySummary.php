<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionDailySummary extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The subscription metrics data.
     *
     * @var array
     */
    public $report;

    /**
     * Create a new message instance.
     *
     * @param array $report
     * @return void
     */
    public function __construct(array $report)
    {
        $this->report = $report;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $date = $this->report['date'] ?? now()->subDay()->format('Y-m-d');
        return new Envelope(
            subject: 'Daily Subscription Report - ' . $date,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Format numbers for display
        $this->formatReportData();
        
        return new Content(
            view: 'emails.subscription.daily-summary',
            with: [
                'report' => $this->report,
                'date' => $this->report['date'] ?? now()->subDay()->format('Y-m-d'),
                'generatedAt' => $this->report['generated_at'] ?? now()->toDateTimeString(),
            ],
        );
    }

    /**
     * Format report data for display
     */
    protected function formatReportData(): void
    {
        // Format currency values
        if (isset($this->report['mrr'])) {
            $this->report['formatted_mrr'] = '$' . number_format($this->report['mrr'], 2);
        }
        
        if (isset($this->report['arr'])) {
            $this->report['formatted_arr'] = '$' . number_format($this->report['arr'], 2);
        }
        
        // Format percentages
        if (isset($this->report['churn_rate'])) {
            $this->report['formatted_churn_rate'] = number_format($this->report['churn_rate'], 2) . '%';
        }
        
        // Format dates
        if (isset($this->report['date'])) {
            $date = \Carbon\Carbon::parse($this->report['date']);
            $this->report['formatted_date'] = $date->format('F j, Y');
        }
        
        // Calculate and format deltas if previous day data is available
        if (isset($this->report['previous_day'])) {
            $prev = $this->report['previous_day'];
            
            // Calculate MRR change
            if (isset($this->report['mrr'], $prev['mrr']) && $prev['mrr'] > 0) {
                $mrrChange = (($this->report['mrr'] - $prev['mrr']) / $prev['mrr']) * 100;
                $this->report['mrr_change'] = [
                    'value' => abs($mrrChange),
                    'direction' => $mrrChange >= 0 ? 'up' : 'down',
                    'class' => $mrrChange >= 0 ? 'text-success' : 'text-danger'
                ];
                $this->report['mrr_change_formatted'] = number_format(abs($mrrChange), 2) . '% ' . 
                    ($mrrChange >= 0 ? '↑' : '↓');
            }
            
            // Calculate subscriber change
            if (isset($this->report['active_subscriptions'], $prev['active_subscriptions'])) {
                $subChange = $this->report['active_subscriptions'] - $prev['active_subscriptions'];
                $this->report['subscriber_change'] = [
                    'value' => abs($subChange),
                    'direction' => $subChange >= 0 ? 'up' : 'down',
                    'class' => $subChange >= 0 ? 'text-success' : 'text-danger'
                ];
                $this->report['subscriber_change_formatted'] = 
                    ($subChange >= 0 ? '+' : '') . $subChange;
            }
        }
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
