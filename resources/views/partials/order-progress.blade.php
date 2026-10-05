{{-- Steps of the order as they really stand ($steps from App\Services\Orders\CustomerProgress). --}}
<ol class="kova-thanks-steps">
    @foreach ($steps as $step)
        <li @class(['is-done' => $step['state'] === 'done', 'is-current' => $step['state'] === 'current']) @if ($step['state'] === 'current') aria-current="step" @endif>
            <span class="kova-thanks-steps__dot"><i class="fa-regular {{ $step['state'] === 'done' ? 'fa-check' : $step['icon'] }}"></i></span>
            <strong>{{ $step['label'] }}</strong>
            <span>
                @if ($step['state'] === 'done' && $step['at'] && $step['key'] !== 'received')
                    {{ $step['detail'] }} · {{ $step['at']->format('d/m à H\hi') }}
                @elseif ($step['key'] === 'received')
                    {{ $step['at']->format('d/m à H\hi') }}
                @else
                    {{ $step['detail'] }}
                @endif
            </span>
        </li>
    @endforeach
</ol>
