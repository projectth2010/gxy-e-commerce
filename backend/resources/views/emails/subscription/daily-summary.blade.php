@extends('layouts.email')

@section('content')
<div class="email-container" style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
    <!-- Header -->
    <div class="email-header" style="background-color: #4f46e5; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Daily Subscription Report</h1>
        <p style="margin: 5px 0 0; opacity: 0.9;">{{ $report['formatted_date'] ?? now()->format('F j, Y') }}</p>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards" style="display: flex; flex-wrap: wrap; margin: 20px 0;">
        <!-- MRR Card -->
        <div class="card" style="flex: 1; min-width: 200px; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 10px; padding: 15px; text-align: center;">
            <div class="metric-value" style="font-size: 24px; font-weight: bold; color: #4f46e5;">
                {{ $report['formatted_mrr'] ?? '$0.00' }}
                @if(isset($report['mrr_change']))
                    <span style="font-size: 14px; {{ $report['mrr_change']['class'] }}">
                        {{ $report['mrr_change_formatted'] }}
                    </span>
                @endif
            </div>
            <div class="metric-label" style="color: #6b7280; font-size: 14px;">Monthly Recurring Revenue</div>
        </div>
        
        <!-- Active Subscribers Card -->
        <div class="card" style="flex: 1; min-width: 200px; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 10px; padding: 15px; text-align: center;">
            <div class="metric-value" style="font-size: 24px; font-weight: bold; color: #4f46e5;">
                {{ number_format($report['active_subscriptions'] ?? 0) }}
                @if(isset($report['subscriber_change']))
                    <span style="font-size: 14px; {{ $report['subscriber_change']['class'] }}">
                        {{ $report['subscriber_change_formatted'] }}
                    </span>
                @endif
            </div>
            <div class="metric-label" style="color: #6b7280; font-size: 14px;">Active Subscriptions</div>
        </div>
        
        <!-- Trial Subscribers Card -->
        <div class="card" style="flex: 1; min-width: 200px; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 10px; padding: 15px; text-align: center;">
            <div class="metric-value" style="font-size: 24px; font-weight: bold; color: #4f46e5;">
                {{ number_format($report['trial_subscriptions'] ?? 0) }}
            </div>
            <div class="metric-label" style="color: #6b7280; font-size: 14px;">Trial Subscriptions</div>
        </div>
        
        <!-- Churn Rate Card -->
        <div class="card" style="flex: 1; min-width: 200px; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 10px; padding: 15px; text-align: center;">
            <div class="metric-value" style="font-size: 24px; font-weight: bold; color: {{ ($report['churn_rate'] ?? 0) > 5 ? '#ef4444' : '#10b981' }};">
                {{ $report['formatted_churn_rate'] ?? '0.00%' }}
            </div>
            <div class="metric-label" style="color: #6b7280; font-size: 14px;">30-Day Churn Rate</div>
        </div>
    </div>
    
    <!-- New vs Cancellations -->
    <div class="new-vs-cancellations" style="background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0; padding: 20px;">
        <h2 style="margin-top: 0; color: #1f2937; font-size: 18px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;">
            Subscriber Activity
        </h2>
        
        <div style="display: flex; justify-content: space-between; margin-top: 15px;">
            <div style="text-align: center; flex: 1; padding: 10px;">
                <div style="font-size: 20px; font-weight: bold; color: #10b981;">
                    +{{ number_format($report['new_subscriptions'] ?? 0) }}
                </div>
                <div style="color: #6b7280; font-size: 14px;">New Subscriptions</div>
            </div>
            
            <div style="text-align: center; flex: 1; padding: 10px;">
                <div style="font-size: 20px; font-weight: bold; color: #ef4444;">
                    -{{ number_format($report['cancellations'] ?? 0) }}
                </div>
                <div style="color: #6b7280; font-size: 14px;">Cancellations</div>
            </div>
            
            <div style="text-align: center; flex: 1; padding: 10px;">
                <div style="font-size: 20px; font-weight: bold; color: #4f46e5;">
                    {{ number_format(($report['new_subscriptions'] ?? 0) - ($report['cancellations'] ?? 0)) }}
                </div>
                <div style="color: #6b7280; font-size: 14px;">Net Change</div>
            </div>
        </div>
    </div>
    
    <!-- Recent Alerts -->
    @if(!empty($report['recent_alerts']))
    <div class="recent-alerts" style="background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0; padding: 20px;">
        <h2 style="margin-top: 0; color: #1f2937; font-size: 18px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px;">
            Recent Alerts
        </h2>
        
        <ul style="margin: 15px 0 0; padding-left: 20px;">
            @foreach($report['recent_alerts'] as $alert)
            <li style="margin-bottom: 8px; color: #6b7280; font-size: 14px;">
                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; 
                    background-color: 
                        {{ $alert['level'] === 'critical' ? '#ef4444' : 
                          ($alert['level'] === 'warning' ? '#f59e0b' : '#3b82f6') }}; 
                        margin-right: 8px;">
                </span>
                {{ $alert['message'] }}
                <span style="color: #9ca3af; font-size: 12px; margin-left: 5px;">
                    {{ $alert['time_ago'] ?? '' }}
                </span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
    
    <!-- Footer -->
    <div class="email-footer" style="margin-top: 30px; text-align: center; color: #9ca3af; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>Generated on {{ $generatedAt ?? now()->toDateTimeString() }}</p>
        <p>
            <a href="{{ config('app.url') }}/admin/subscriptions" style="color: #4f46e5; text-decoration: none;">
                View in Dashboard
            </a>
        </p>
    </div>
</div>
@endsection
