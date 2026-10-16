{{-- Conversation of an assistance request (App\Filament\Resources\SupportTickets\Schemas\SupportTicketInfolist): the
     customer on the left, the team on the right, with the files of each message. Styles in
     public/assets/admin/kova-admin.css. --}}
@php
    /** @var \App\Models\SupportTicket $ticket */
    $ticket = $getRecord();
@endphp
<ol class="kova-thread">
    @foreach ($ticket->messages()->with('author')->oldest('id')->get() as $message)
        <li @class(['kova-thread__message', 'is-staff' => $message->from_staff])>
            <p class="kova-thread__who">
                {{ $message->from_staff ? ($message->author?->name ?? 'Équipe').' · équipe' : ($message->author?->name ?? 'Client') }}
                <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d/m/Y à H:i') }}</time>
            </p>
            @if (filled($message->body))
                <div class="kova-thread__body">{!! nl2br(e($message->body)) !!}</div>
            @endif
            @if ($message->files())
                <ul class="kova-thread__files">
                    @foreach ($message->files() as $file)
                        @php($url = route('account.support.attachment', [$ticket, $message, $file['index']]))
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" @class(['kova-thread__file', 'is-image' => $file['image']])>
                                @if ($file['image'])
                                    <img src="{{ $url }}" alt="{{ $file['name'] }}" loading="lazy">
                                @else
                                    <x-filament::icon icon="heroicon-o-document-text" class="kova-thread__file-icon" />
                                    <span>{{ $file['name'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ol>
