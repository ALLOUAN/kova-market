@props([
    'id',
    'dialogClass' => 'modal-dialog-centered',
    'contentClass' => null,
    'folder' => true,
    'labelled' => false,
])

{{-- Storefront modal shell: optional "folder" corner shape and the round close button. --}}
<div {{ $attributes->class(['rbt-default-modal modal fade', 'has-rbt-top-folder-shape' => $folder]) }} id="{{ $id }}" tabindex="-1" @if ($labelled) role="dialog" aria-modal="true" aria-labelledby="{{ $id }}Label" @endif aria-hidden="true">
    <div class="modal-dialog {{ $dialogClass }}">
        <div @class(['modal-content', $contentClass])>
            @if ($folder)
                <div class="rbt-folder-shape-right-portion">
                    <svg xmlns="http://www.w3.org/2000/svg" width="85" height="90" viewBox="0 0 85 90" fill="none">
                        <path d="M0 0H11.1844C14.5695 0 17.7971 1.42971 20.0716 3.93671L82.1927 72.4059C83.9992 74.397 84.9999 76.9893 84.9999 79.6778C84.9999 85.6547 85.0001 90 85.0001 90H0V0Z" fill="white"></path>
                    </svg>
                </div>
            @endif
            <div class="modal-header">
                <button type="button" class="rbt-round-btn rbt-modal-dis-btn" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
