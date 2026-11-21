{{-- A template's tile: its picture, or its emoji on its colour. --}}
<span
    class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl text-2xl {{ $template->color ? '' : 'bg-slate-800' }}"
    @if ($template->color) style="background-color: {{ $template->color }}" @endif
    aria-hidden="true"
>
    @if ($template->icon_image)
        <img src="{{ $template->icon_image }}" alt="" class="size-full object-cover">
    @else
        {{ $template->icon ?: '🎮' }}
    @endif
</span>
