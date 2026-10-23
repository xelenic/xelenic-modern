@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif {{ $attributes->class([
    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-blue-50 text-blue-700 ring-1 ring-blue-100' => $active,
    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
]) }}>
    <x-icon :name="$icon" class="size-5 {{ $active ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}" />
    {{ $slot }}
</a>
