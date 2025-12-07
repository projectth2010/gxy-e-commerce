@component('mail::layout')
@slot('header')
    @component('mail::header', ['url' => config('app.url')])
        {{ config('app.name') }} Subscription Alert
    @endcomponent
@endslot

@php
    // Set colors based on alert level
    $colors = [
        'critical' => [
            'bg' => '#dc3545',
            'text' => '#ffffff',
            'border' => '#dc3545',
            'icon' => '⚠️',
            'title' => 'Critical Alert',
        ],
        'error' => [
            'bg' => '#fd7e14',
            'text' => '#212529',
            'border' => '#fd7e14',
            'icon' => '⚠️',
            'title' => 'Error Alert',
        ],
        'warning' => [
            'bg' => '#ffc107',
            'text' => '#212529',
            'border' => '#ffc107',
            'icon' => 'ℹ️',
            'title' => 'Warning',
        ],
        'info' => [  
            'bg' => '#17a2b8',
            'text' => '#ffffff',
            'border' => '#17a2b8',
            'icon' => 'ℹ️',
            'title' => 'Information',
        ],
        'default' => [
            'bg' => '#6c757d',
            'text' => '#ffffff',
            'border' => '#6c757d',
            'icon' => 'ℹ️',
            'title' => 'Alert',
        ],
    ];

    $level = $level ?? 'info';
    $alert = $colors[$level] ?? $colors['default'];
@endphp

<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid {{ $alert['border'] }}; border-radius: 5px; overflow: hidden;">
    <!-- Header -->
    <div style="background-color: {{ $alert['bg'] }}; color: {{ $alert['text'] }}; padding: 15px 20px; font-size: 18px; font-weight: bold;">
        {{ $alert['icon'] }} {{ $alert['title'] }}
    </div>
    
    <!-- Content -->
    <div style="padding: 20px; background-color: #ffffff; color: #212529;">
        <h1 style="margin-top: 0; color: #212529; font-size: 22px;">{{ $subject }}</h1>
        
        <div style="background-color: #f8f9fa; border-left: 4px solid {{ $alert['border'] }}; padding: 15px; margin: 15px 0; font-size: 15px; line-height: 1.5;">
            {!! nl2br(e($message)) !!}
        </div>
        
        @if(isset($metrics))
        <div style="margin: 20px 0; border: 1px solid #e9ecef; border-radius: 5px; overflow: hidden;
                    ">
            <div style="background-color: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #e9ecef; font-weight: bold;">
                📊 Metrics Snapshot
            </div>
            <div style="padding: 15px;">
                <div style="display: flex; flex-wrap: wrap; margin: -5px;">
                    <div style="flex: 1; min-width: 150px; padding: 5px;">
                        <div style="font-size: 13px; color: #6c757d; margin-bottom: 3px;">Active Subs</div>
                        <div style="font-weight: bold;">{{ $metrics['active_subscriptions'] ?? 0 }}</div>
                    </div>
                    <div style="flex: 1; min-width: 150px; padding: 5px;">
                        <div style="font-size: 13px; color: #6c757d; margin-bottom: 3px;">MRR</div>
                        <div style="font-weight: bold;">${{ number_format($metrics['mrr'] ?? 0, 2) }}</div>
                    </div>
                    <div style="flex: 1; min-width: 150px; padding: 5px;">
                        <div style="font-size: 13px; color: #6c757d; margin-bottom: 3px;">Churn Rate</div>
                        <div style="font-weight: bold; color: {{ ($metrics['churn_rate'] ?? 0) > 0.05 ? '#dc3545' : '#28a745' }};">
                            {{ number_format(($metrics['churn_rate'] ?? 0) * 100, 2) }}%
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 150px; padding: 5px;">
                        <div style="font-size: 13px; color: #6c757d; margin-bottom: 3px;">Payment Issues</div>
                        <div style="font-weight: bold; color: {{ ($metrics['recent_payment_failures'] ?? 0) > 0 ? '#dc3545' : '#28a745' }};">
                            {{ $metrics['recent_payment_failures'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <p style="margin-bottom: 20px; font-size: 14px; line-height: 1.5;">
            This is an automated alert from the subscription monitoring system. 
            @if(in_array($level, ['critical', 'error', 'warning']))
                <strong>Please review this issue as soon as possible.</strong>
            @else
                No immediate action is required.
            @endif
        </p>
        
        @component('mail::button', ['url' => url('/admin/subscriptions'), 'color' => $level])
            View Subscriptions Dashboard
        @endcomponent
    </div>
    
    <!-- Footer -->
    <div style="background-color: #f8f9fa; padding: 15px 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef;">
        <p style="margin: 5px 0;">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
        <p style="margin: 5px 0; font-size: 11px; color: #adb5bd;">
            This is an automated message. Please do not reply to this email.
        </p>
    </div>
</div>

@slot('footer')
    @component('mail::footer')
        @if (isset($unsubscribeUrl) && $unsubscribeUrl)
            <a href="{{ $unsubscribeUrl }}" style="color: #6c757d; text-decoration: none;">Unsubscribe</a> |
        @endif
        
        <a href="{{ config('app.url') }}/contact" style="color: #6c757d; text-decoration: none;">Contact Support</a> |
        <a href="{{ config('app.url') }}/privacy" style="color: #6c757d; text-decoration: none;">Privacy Policy</a>
    @endcomponent
@endslot
@endcomponent
