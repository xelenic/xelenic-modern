{{-- A project type's icon on a tile in that type's own colour. --}}
@props(['type', 'size' => 'size-11'])

@use('App\Enums\ProjectType')

@php
    $tint = match ($type) {
        ProjectType::KioskApp => 'bg-blue-50 text-blue-600 ring-blue-100',
        ProjectType::SoftwareSolution => 'bg-violet-50 text-violet-600 ring-violet-100',
        ProjectType::HireEngineers => 'bg-amber-50 text-amber-600 ring-amber-100',
        ProjectType::Ecommerce => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        ProjectType::MobileApp => 'bg-sky-50 text-sky-600 ring-sky-100',
        ProjectType::GrowBusiness => 'bg-rose-50 text-rose-600 ring-rose-100',
    };
@endphp

<span {{ $attributes->class([$size, $tint, 'flex shrink-0 items-center justify-center rounded-xl ring-1 ring-inset']) }} aria-hidden="true">
    <x-icon :name="$type->icon()" class="size-5" />
</span>
