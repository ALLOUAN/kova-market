@props(['ticket', 'message'])

{{-- Files of an assistance message: pictures as thumbnails, PDF as a labelled link; each opens in a new tab. --}}
@if ($message->files())
    <ul class="kova-ticket-files">
        @foreach ($message->files() as $file)
            @php($url = route('account.support.attachment', [$ticket, $message, $file['index']]))
            <li>
                <a href="{{ $url }}" target="_blank" rel="noopener" @class(['kova-ticket-file', 'is-image' => $file['image']])>
                    @if ($file['image'])
                        <img src="{{ $url }}" alt="{{ $file['name'] }}" loading="lazy">
                    @else
                        <i class="fa-regular fa-file-pdf" aria-hidden="true"></i>
                        <span>{{ $file['name'] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
@endif
