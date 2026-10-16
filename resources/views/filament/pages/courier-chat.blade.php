{{-- Livraison › Messagerie livreurs (App\Filament\Pages\CourierChat), drawn like WhatsApp Web: the couriers on the
     left, the conversation on the beige background on the right. Styles in public/assets/admin/kova-admin.css
     (.kcc-*): the panel has no Tailwind build of its own. Refreshed every 5 seconds. --}}
@php
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp

<div class="kcc" wire:poll.5s>
    <aside @class(['kcc-list', 'has-selection' => $courier])>
        <header class="kcc-list__head">
            <h1 class="kcc-list__title">Messagerie livreurs</h1>
            <p class="kcc-list__lead">{{ $couriers->sum('unread') > 0 ? $couriers->sum('unread').' message(s) non lu(s)' : 'Tout est lu' }}</p>
        </header>
        <ul class="kcc-list__items">
            @forelse ($couriers as $item)
                <li>
                    <button type="button" wire:click="open({{ $item['id'] }})" @class(['kcc-contact', 'is-active' => $courier?->id === $item['id'], 'is-unread' => $item['unread'] > 0])>
                        <span class="kcc-avatar" aria-hidden="true">{{ $initials($item['name']) }}</span>
                        <span class="kcc-contact__main">
                            <span class="kcc-contact__row">
                                <strong>{{ $item['name'] }}</strong>
                                @if ($item['last_at'])<time>{{ $item['last_at'] }}</time>@endif
                            </span>
                            <span class="kcc-contact__row">
                                <span class="kcc-contact__last">{{ $item['last'] ?? ($item['suspended'] ? 'Compte suspendu' : 'Aucun message') }}</span>
                                @if ($item['unread'] > 0)<span class="kcc-badge">{{ $item['unread'] }}</span>@endif
                            </span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="kcc-list__empty">Aucun livreur pour le moment.</li>
            @endforelse
        </ul>
    </aside>

    <section class="kcc-chat">
        @if ($courier)
            <header class="kcc-chat__head">
                <button type="button" class="kcc-back" wire:click="$set('courierId', null)" aria-label="Retour à la liste">
                    <x-filament::icon icon="heroicon-o-arrow-left" class="kcc-icon" />
                </button>
                <span class="kcc-avatar" aria-hidden="true">{{ $initials($courier->name()) }}</span>
                <div class="kcc-chat__who">
                    <strong>{{ $courier->name() }}</strong>
                    <span>{{ $courier->formattedPhone() }}{{ $courier->isSuspended() ? ' · compte suspendu' : '' }}</span>
                </div>
                <a class="kcc-chat__call" href="tel:{{ $courier->user->phone }}" aria-label="Appeler {{ $courier->name() }}">
                    <x-filament::icon icon="heroicon-o-phone" class="kcc-icon" />
                </a>
            </header>

            <div class="kcc-thread" id="kcc-thread" x-data x-init="$el.scrollTop = $el.scrollHeight; $wire.on('courier-chat-sent', () => $nextTick(() => $el.scrollTop = $el.scrollHeight))">
                @forelse ($messages as $message)
                    <div @class(['kcc-msg', 'kcc-msg--mine' => $message['mine']]) wire:key="kcc-msg-{{ $message['id'] }}">
                        @if ($message['mine'] && $message['author'])
                            <p class="kcc-msg__author">{{ $message['author'] }}</p>
                        @endif
                        <p class="kcc-msg__text">{{ $message['body'] }}</p>
                        <span class="kcc-msg__meta">{{ $message['time'] }}
                            @if ($message['mine'])
                                <svg @class(['kcc-ticks', 'is-read' => $message['read']]) viewBox="0 0 16 11" aria-label="{{ $message['read'] ? 'Lu' : 'Envoyé' }}"><path fill="currentColor" d="M11.07.65 4.85 7.4 2.3 4.98 1.25 6.07l3.65 3.48 7.2-7.84zM14.7.65 8.48 7.4l-.62-.6-1.05 1.1 1.72 1.65 7.2-7.84z"/></svg>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="kcc-empty">Aucun message avec {{ $courier->name() }} pour l’instant. Écrivez-lui : il le verra dans l’onglet « Messages » de son application.</p>
                @endforelse
            </div>

            <form class="kcc-composer" wire:submit="send">
                <label class="kcc-sr" for="kcc-draft">Votre message</label>
                <textarea id="kcc-draft" wire:model="draft" rows="1" maxlength="{{ \App\Models\CourierMessage::MAX_LENGTH }}" placeholder="Écrire un message"
                    x-data x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit(); }"></textarea>
                <button type="submit" class="kcc-send" aria-label="Envoyer" wire:loading.attr="disabled" wire:target="send">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </form>
            @error('draft')<p class="kcc-error">{{ $message }}</p>@enderror
        @else
            <div class="kcc-welcome">
                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="kcc-welcome__icon" />
                <h2>Choisissez un livreur</h2>
                <p>Ses messages et les vôtres s’affichent ici. Il les reçoit dans l’onglet « Messages » de son application.</p>
            </div>
        @endif
    </section>
</div>
