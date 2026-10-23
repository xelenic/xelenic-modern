@props(['name', 'label', 'value' => '1', 'checked' => false, 'description' => null])

<label @class([
    'flex items-start gap-3',
    'cursor-pointer' => ! $attributes->get('disabled'),
    'cursor-not-allowed opacity-50' => $attributes->get('disabled'),
])>
    <input
        type="checkbox"
        name="{{ $name }}"
        value="{{ $value }}"
        @checked($checked)
        {{ $attributes->class('mt-0.5 size-4 shrink-0 rounded border-slate-300 bg-white accent-blue-600') }}
    >
    <span>
        <span class="block text-sm text-slate-700">{{ $label }}</span>
        @if ($description)
            <span class="mt-0.5 block text-xs text-slate-500">{{ $description }}</span>
        @endif
    </span>
</label>
