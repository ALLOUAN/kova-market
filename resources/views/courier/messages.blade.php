@extends('courier.layout')

{{-- "Messages": the courier's chat with the back-office, drawn like a WhatsApp conversation (beige background,
     the courier's messages in green on the right, the team's in white on the left, ✓✓ blue once read). Sends without
     reloading and fetches what is new every 5 seconds while the page is visible. --}}
@section('title', 'Messages')
@section('tab', 'messages')

@section('head')
    <style>
        body { background-color: #efeae2; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Cg fill='none' stroke='%23ded5c5' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 22h22a6 6 0 0 1 6 6v10a6 6 0 0 1-6 6H28l-7 6v-6h-3a6 6 0 0 1-6-6V28a6 6 0 0 1 6-6z'/%3E%3Cpath d='M112 30c-4-7-14-5-14 3 0 7 14 14 14 14s14-7 14-14c0-8-10-10-14-3z'/%3E%3Cpath d='M70 76l3 7 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z'/%3E%3Crect x='112' y='92' width='26' height='22' rx='3'/%3E%3Cpath d='M112 100h26M125 92v8'/%3E%3Cpath d='M24 112a10 10 0 1 0 20 0a10 10 0 1 0-20 0M31 110h.01M37 110h.01M30 116q4 4 8 0'/%3E%3Cpath d='M86 132q6-8 12 0t12 0'/%3E%3Ccircle cx='140' cy='60' r='3'/%3E%3Ccircle cx='60' cy='140' r='2.5'/%3E%3Ccircle cx='58' cy='26' r='2'/%3E%3C/g%3E%3C/svg%3E"); background-size: 160px 160px; background-attachment: fixed; }
        main { padding-bottom: 170px; }
        .wa-thread { display: flex; flex-direction: column; gap: 6px; }
        .wa-msg { position: relative; max-width: 82%; padding: 6px 9px 8px 10px; border-radius: 10px; background: #fff; color: #111b21; font-size: 15px; line-height: 1.4; box-shadow: 0 1px 1px rgb(11 20 26 / .13); align-self: flex-start; border-top-left-radius: 0; }
        .wa-msg::before { content: ""; position: absolute; top: 0; left: -8px; border: 8px solid transparent; border-top-color: #fff; border-right-color: #fff; border-width: 0 8px 10px 0; }
        .wa-msg--mine { align-self: flex-end; background: #d9fdd3; border-top-left-radius: 10px; border-top-right-radius: 0; }
        .wa-msg--mine::before { left: auto; right: -8px; border-color: transparent; border-top-color: #d9fdd3; border-left-color: #d9fdd3; border-width: 0 0 10px 8px; }
        .wa-msg__author { margin: 0 0 2px; font-size: 13px; font-weight: 700; color: #cc4400; }
        .wa-msg__text { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; }
        .wa-msg__meta { float: right; display: inline-flex; align-items: center; gap: 3px; margin: 6px 0 -4px 10px; font-size: 11px; color: #667781; }
        .wa-ticks { width: 16px; height: 11px; color: #8696a0; }
        .wa-ticks.is-read { color: #53bdeb; }
        .wa-empty { align-self: center; max-width: 320px; margin: 24px 0; padding: 14px 16px; border-radius: 10px; background: #ffeecd; color: #54656f; font-size: 14px; text-align: center; box-shadow: 0 1px 1px rgb(11 20 26 / .08); }
        .wa-composer { position: fixed; left: 0; right: 0; bottom: calc(65px + env(safe-area-inset-bottom)); z-index: 19; padding: 8px 10px; }
        .wa-composer form { max-width: 620px; margin: 0 auto; display: flex; align-items: flex-end; gap: 8px; }
        .wa-composer textarea { flex: 1; min-height: 48px; max-height: 140px; padding: 12px 16px; border: 0; border-radius: 24px; background: #fff; font-size: 16px; line-height: 1.4; resize: none; box-shadow: 0 1px 2px rgb(11 20 26 / .15); }
        .wa-composer textarea:focus { outline: none; box-shadow: 0 0 0 2px #25d366; }
        .wa-send { flex: none; width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 50%; background: #00a884; color: #fff; cursor: pointer; box-shadow: 0 1px 2px rgb(11 20 26 / .2); }
        .wa-send:disabled { opacity: .6; cursor: progress; }
        .wa-send:focus-visible { outline: 3px solid rgb(0 168 132 / .4); outline-offset: 2px; }
    </style>
@endsection

@section('hero')
    <header class="ch-hero">
        @include('courier.partials.hero-top', ['title' => 'Messages', 'subtitle' => 'Discussion avec l’équipe '.config('storefront.name')])
    </header>
@endsection

@section('content')
    <div class="wa-thread" data-thread data-poll="{{ route('courier.messages.poll') }}" aria-live="polite">
        <p class="wa-empty" data-empty @if ($messages->isNotEmpty()) hidden @endif>
            Une question sur une livraison, un client injoignable, un souci ? Écrivez à l’équipe ici, elle vous répond au plus vite.
        </p>
        @foreach ($messages as $message)
            <div @class(['wa-msg', 'wa-msg--mine' => $message['mine']]) data-id="{{ $message['id'] }}">
                @if (! $message['mine'] && $message['author'])
                    <p class="wa-msg__author">{{ $message['author'] }}</p>
                @endif
                <p class="wa-msg__text">{{ $message['body'] }}</p>
                <span class="wa-msg__meta">{{ $message['time'] }}
                    @if ($message['mine'])
                        <svg @class(['wa-ticks', 'is-read' => $message['read']]) viewBox="0 0 16 11" aria-label="{{ $message['read'] ? 'Lu' : 'Envoyé' }}"><path fill="currentColor" d="M11.07.65 4.85 7.4 2.3 4.98 1.25 6.07l3.65 3.48 7.2-7.84zM14.7.65 8.48 7.4l-.62-.6-1.05 1.1 1.72 1.65 7.2-7.84z"/></svg>
                    @endif
                </span>
            </div>
        @endforeach
    </div>

    <div class="wa-composer">
        <form method="POST" action="{{ route('courier.messages.store') }}" data-composer>
            @csrf
            <label class="visually-hidden" for="body">Votre message</label>
            <textarea id="body" name="body" rows="1" maxlength="{{ \App\Models\CourierMessage::MAX_LENGTH }}" placeholder="Écrire un message" required></textarea>
            <button type="submit" class="wa-send" aria-label="Envoyer">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </form>
        @error('body')<p class="error" style="max-width: 620px; margin: 4px auto 0;">{{ $message }}</p>@enderror
    </div>

    <script>
        (() => {
            const thread = document.querySelector('[data-thread]');
            const form = document.querySelector('[data-composer]');
            const field = form.querySelector('textarea');
            const button = form.querySelector('button');
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const ticks = '<path fill="currentColor" d="M11.07.65 4.85 7.4 2.3 4.98 1.25 6.07l3.65 3.48 7.2-7.84zM14.7.65 8.48 7.4l-.62-.6-1.05 1.1 1.72 1.65 7.2-7.84z"/>';
            const lastId = () => Math.max(0, ...[...thread.querySelectorAll('[data-id]')].map((bubble) => Number(bubble.dataset.id)));
            const toBottom = () => window.scrollTo({ top: document.body.scrollHeight });

            // Built with textContent: a message is never read as HTML.
            const bubble = (message) => {
                const item = document.createElement('div');
                item.className = 'wa-msg' + (message.mine ? ' wa-msg--mine' : '');
                item.dataset.id = message.id;
                if (!message.mine && message.author) {
                    const author = document.createElement('p');
                    author.className = 'wa-msg__author';
                    author.textContent = message.author;
                    item.append(author);
                }
                const text = document.createElement('p');
                text.className = 'wa-msg__text';
                text.textContent = message.body;
                const meta = document.createElement('span');
                meta.className = 'wa-msg__meta';
                meta.textContent = message.time + ' ';
                if (message.mine) {
                    meta.insertAdjacentHTML('beforeend', '<svg class="wa-ticks' + (message.read ? ' is-read' : '') + '" viewBox="0 0 16 11" aria-label="Envoyé">' + ticks + '</svg>');
                }
                item.append(text, meta);
                return item;
            };

            const add = (messages) => {
                messages.filter((message) => !thread.querySelector('[data-id="' + message.id + '"]')).forEach((message) => thread.append(bubble(message)));
                thread.querySelector('[data-empty]').hidden = thread.querySelector('[data-id]') !== null;
            };

            field.addEventListener('input', () => { field.style.height = 'auto'; field.style.height = Math.min(field.scrollHeight, 140) + 'px'; });
            field.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey && window.matchMedia('(min-width: 768px)').matches) { event.preventDefault(); form.requestSubmit(); }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!field.value.trim()) return;
                button.disabled = true;
                try {
                    const response = await fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: new FormData(form) });
                    if (!response.ok) throw new Error();
                    add([(await response.json()).message]);
                    field.value = '';
                    field.style.height = '';
                    toBottom();
                } catch {
                    form.submit();
                } finally {
                    button.disabled = false;
                    field.focus();
                }
            });

            const poll = async () => {
                if (document.hidden) return;
                try {
                    const response = await fetch(thread.dataset.poll + '?apres=' + lastId(), { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const data = await response.json();
                    const atBottom = window.innerHeight + window.scrollY >= document.body.scrollHeight - 80;
                    add(data.messages);
                    thread.querySelectorAll('.wa-msg--mine').forEach((item) => {
                        if (Number(item.dataset.id) <= data.read_up_to) item.querySelector('.wa-ticks')?.classList.add('is-read');
                    });
                    if (data.messages.length && atBottom) toBottom();
                } catch {}
            };

            toBottom();
            setInterval(poll, 5000);
            document.addEventListener('visibilitychange', poll);
        })();
    </script>
@endsection
