@props(['user', 'size' => 'md'])

<span aria-hidden="true" {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center rounded-full bg-linear-to-br from-blue-500 to-sky-400 font-semibold text-white',
    'size-8 text-xs' => $size === 'sm',
    'size-9 text-sm' => $size === 'md',
    'size-14 text-lg' => $size === 'lg',
]) }}>{{ $user->initials() }}</span>
