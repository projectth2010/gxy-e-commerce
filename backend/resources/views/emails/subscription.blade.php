@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    {{-- Body --}}
    # {{ $greeting ?? 'Hello!' }}

    {!! $message !!}

    @if(isset($actionText))
        @component('mail::button', ['url' => $actionUrl, 'color' => $color ?? 'primary'])
            {{ $actionText }}
        @endcomponent
    @endif

    @if(isset($outroLines))
        @foreach ($outroLines as $line)
            {{ $line }}
        @endforeach
    @endif

    {{-- Subcopy --}}
    @isset($actionText)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ __('If you’re having trouble clicking the "' . $actionText . '" button, copy and paste the URL below into your web browser:') }}
                <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
        @endcomponent
    @endslot
@endcomponent
