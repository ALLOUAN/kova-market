{{-- "Joindre des fichiers" under an assistance message: photos or PDF, the chosen names listed (script below). --}}
@php
    use App\Models\SupportTicketMessage;
@endphp

<div class="kova-attach" data-attach>
    <label class="kova-attach__button">
        <i class="fa-regular fa-paperclip" aria-hidden="true"></i>
        <span>Joindre des fichiers</span>
        <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf" data-attach-input>
    </label>
    <span class="kova-attach__hint">Photos ou PDF · {{ SupportTicketMessage::MAX_ATTACHMENTS }} fichiers, {{ SupportTicketMessage::ATTACHMENT_MAX_KB / 1024 }} Mo chacun au plus</span>
    <ul class="kova-attach__list" data-attach-list aria-live="polite"></ul>
    @error('attachments')<span class="d-block mt--4 b4 rbt-text-color-danger">{{ $message }}</span>@enderror
    @foreach ($errors->get('attachments.*') as $messages)
        <span class="d-block mt--4 b4 rbt-text-color-danger">{{ $messages[0] }}</span>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-attach]').forEach((box) => {
                const input = box.querySelector('[data-attach-input]');
                const list = box.querySelector('[data-attach-list]');
                const max = {{ SupportTicketMessage::MAX_ATTACHMENTS }};
                const maxBytes = {{ SupportTicketMessage::ATTACHMENT_MAX_KB }} * 1024;

                input.addEventListener('change', () => {
                    list.replaceChildren(...[...input.files].map((file, index) => {
                        const item = document.createElement('li');
                        const tooBig = file.size > maxBytes;
                        item.className = 'kova-attach__file' + (tooBig || index >= max ? ' is-invalid' : '');
                        item.textContent = file.name + ' · ' + (file.size / 1024 / 1024).toFixed(1).replace('.', ',') + ' Mo'
                            + (tooBig ? ' — trop lourd' : (index >= max ? ' — au-delà de ' + max + ' fichiers' : ''));
                        return item;
                    }));
                });
            });
        </script>
    @endpush
@endonce
