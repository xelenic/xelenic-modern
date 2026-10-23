@props(['type' => 'success'])

<div role="status" {{ $attributes->class([
    'rounded-xl border px-4 py-3 text-sm',
    'border-emerald-200 bg-emerald-50 text-emerald-800' => $type === 'success',
    'border-red-200 bg-red-50 text-red-700' => $type === 'error',
    'border-blue-200 bg-blue-50 text-blue-800' => $type === 'info',
]) }}>
    {{ $slot }}
</div>
