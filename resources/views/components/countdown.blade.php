@props(['date'])

{{-- Server-rendered initial values; countdown.js takes over (and hides the timer once expired). --}}
@php($remaining = now()->diff($date))
<div {{ $attributes->class(['countdown']) }} data-date="{{ $date->format('Y-m-d') }}">
    @foreach (['days' => ['Days', $remaining->days], 'hours' => ['Hours', $remaining->h], 'minutes' => ['Minutes', $remaining->i], 'seconds' => ['Seconds', $remaining->s]] as $unit => [$heading, $value])
        <div class="countdown-container {{ $unit }}">
            <span class="countdown-value">{{ $value }}</span>
            <span class="countdown-heading">{{ $heading }}</span>
        </div>
    @endforeach
</div>
