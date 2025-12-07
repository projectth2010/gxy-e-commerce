@component('mail::message')
# Subscription Log Alert

We've detected **{{ $totalLogs }} issues** in your subscription logs over the past **{{ $timePeriod }}**.

@foreach($logs as $level => $logGroup)
## {{ strtoupper($level) }} ({{ count($logGroup) }})

@foreach($logGroup->take(5) as $log)
- **{{ $log['time'] }}**: {{ $log['message'] }}
  ```
  {{ Str::limit($log['stack'], 200) }}
  ```
  
  ---
  
@endforeach

@if(count($logGroup) > 5)
> ... and {{ count($logGroup) - 5 }} more {{ strtoupper($level) }} logs
@endif

---

@endforeach

@component('mail::button', ['url' => url('/admin/logs/subscription'), 'color' => 'primary'])
View All Logs
@endcomponent

@component('mail::subcopy')
Report generated on: {{ $reportTime }}

To adjust alert settings, visit your [notification preferences]({{ url('/admin/settings/notifications') }}).
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
