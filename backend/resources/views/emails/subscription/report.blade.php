@component('mail::layout')
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }} - Subscription Report
        @endcomponent
    @endslot

    # Subscription Health Report
    
    **Period:** {{ $report['period']['start'] }} to {{ $report['period']['end'] }}  
    **Generated at:** {{ $report['period']['generated_at'] }}

    @component('mail::panel')
        ## Overview
        - **Active Subscriptions:** {{ number_format($report['overview']['active_subscriptions']) }}
        - **Trial Subscriptions:** {{ number_format($report['overview']['trial_subscriptions']) }}
        - **Total Customers:** {{ number_format($report['overview']['total_customers']) }}
        - **Paid Users:** {{ number_format($report['overview']['paid_users']) }}
    @endcomponent

    @component('mail::panel')
        ## Financials
        - **Monthly Recurring Revenue (MRR):** ${{ number_format($report['financials']['mrr'], 2) }}
        - **Annual Recurring Revenue (ARR):** ${{ number_format($report['financials']['arr'], 2) }}
        - **Avg. Revenue Per User (ARPU):** ${{ number_format($report['financials']['arpu'], 2) }}
        - **Lifetime Value (LTV):** ${{ number_format($report['financials']['ltv'], 2) }}
        - **MRR Growth (30d):** {{ number_format($report['financials']['mrr_growth'], 2) }}%
    @endcomponent

    @component('mail::panel')
        ## Health Metrics
        - **Churn Rate:** {{ number_format($report['health_metrics']['churn_rate'], 2) }}%
        - **Trial Conversion Rate:** {{ number_format($report['health_metrics']['trial_conversion_rate'], 2) }}%
        - **Renewal Success Rate:** {{ number_format($report['health_metrics']['renewal_success_rate'], 2) }}%
        - **Recent Payment Failures (24h):** {{ $report['health_metrics']['recent_payment_failures'] }}
    @endcomponent

    @component('mail::panel')
        ## Recent Activity
        - **New Subscriptions:** {{ number_format($report['activity']['new_subscriptions']) }}
        - **Cancellations:** {{ number_format($report['activity']['cancellations']) }}
        - **Trials Ending Soon:** {{ number_format($report['activity']['trials_ending_soon']) }}
        - **Expiring Cards (30d):** {{ number_format($report['activity']['expiring_cards']) }}
    @endcomponent

    @component('mail::button', ['url' => route('admin.subscriptions.dashboard')])
        View Dashboard
    @endcomponent

    Thanks,  
    {{ config('app.name') }} Team

    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        @endcomponent
    @endslot
@endcomponent
