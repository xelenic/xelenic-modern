{{-- Kiosk Studio (the kiosk engine in public/nignest) over the whole page. The
     page's part goes in the studio's own menu bar (resources/js/kiosk-studio.js
     passes it in): `action` beside Play and first in the File menu, then `skip`
     if there is one; `title` and the `back` button on the right of the menu
     bar. It works with the page's form marked data-kiosk-studio. --}}
@props(['title', 'action', 'back', 'skip' => null])

<div data-studio-overlay hidden role="dialog" aria-modal="true" aria-label="Kiosk Studio"
    data-studio-title="{{ $title }}" data-studio-action="{{ $action }}" data-studio-back="{{ $back }}"
    @if ($skip) data-studio-skip="{{ $skip }}" @endif
    class="fixed inset-0 z-50 bg-slate-950">
    <iframe data-studio-frame title="Kiosk Studio" class="block size-full border-0"></iframe>
</div>
