{{-- A one-colour horizontal bar list: each row is its label, a thin bar sized
     against the largest value, and the value as text, so nothing relies on
     colour. Rows share one grid (subgrid), so the label column fits the
     longest label and every bar starts at the same line. Rows with an href
     link to the matching filtered list.
     items: [['label' => string, 'value' => int, 'href' => ?string, 'icon' => ?string], …] --}}
@props(['items'])

@php
    $max = max(1, ...array_column($items, 'value'));
@endphp

<ul {{ $attributes->class('grid grid-cols-[fit-content(11rem)_1fr_auto] gap-y-0.5') }}>
    @foreach ($items as $item)
        @php($tag = isset($item['href']) ? 'a' : 'div')
        <li class="col-span-3 grid grid-cols-subgrid">
            <{{ $tag }}
                @isset($item['href']) href="{{ $item['href'] }}" @endisset
                class="group col-span-3 grid grid-cols-subgrid items-center gap-x-3 rounded-lg px-2 py-2 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-blue-600"
            >
                <span class="flex min-w-0 items-center gap-2 text-sm leading-tight text-slate-600 group-hover:text-slate-900">
                    @isset($item['icon'])
                        <x-icon :name="$item['icon']" class="size-4 shrink-0 text-slate-400" />
                    @endisset
                    {{ $item['label'] }}
                </span>
                <span aria-hidden="true" class="h-2.5 min-w-8 rounded-r-[4px] bg-blue-50">
                    <span class="block h-full rounded-r-[4px] bg-blue-500 transition group-hover:bg-blue-600" style="width: {{ round($item['value'] / $max * 100, 1) }}%"></span>
                </span>
                <span class="min-w-6 text-right text-sm font-semibold text-slate-900 tabular-nums">{{ number_format($item['value']) }}</span>
            </{{ $tag }}>
        </li>
    @endforeach
</ul>
