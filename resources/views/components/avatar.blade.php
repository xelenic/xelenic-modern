@props(['user', 'size' => 'md'])

<span aria-hidden="true" {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center rounded-full bg-blue-600 font-semibold text-white',
    'size-8 text-xs' => $size === 'sm',
    'size-9 text-sm' => $size === 'md',
    'size-14 text-lg' => $size === 'lg',
]) }}>{{ $user->initials() }}</span>
