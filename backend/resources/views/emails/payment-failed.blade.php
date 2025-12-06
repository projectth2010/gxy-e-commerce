@component('mail::message')
# Payment Failed

Hello {{ $user->name }},

We were unable to process your payment of **{{ $amount }}** for your subscription to **{{ $plan_name }}**.

**Reason:** {{ $reason }}

@if($next_retry_date)
We'll automatically retry the payment on **{{ $next_retry_date }}**.
@endif

@if($update_payment_url)
To avoid any interruption to your service, please update your payment method:

@component('mail::button', ['url' => $update_payment_url])
Update Payment Method
@endcomponent
@endif

If you need assistance, please contact our support team.

Thanks,  
{{ config('app.name') }}
@endcomponent
