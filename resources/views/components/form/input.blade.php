{{-- A labelled input with its validation error. Use `bag` for forms that
     validate into a named error bag (Fortify's profile and password forms).
     Password fields get a show/hide button; `strength` adds a strength meter
     for choosing a new password, and `confirms="password"` checks that a
     confirmation matches that field (resources/js/password-fields.js). --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'bag' => 'default', 'hint' => null, 'strength' => false, 'confirms' => null])

@php
    // "details[kiosk_count]" is "details.kiosk_count" to old() and $errors.
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '-', $key));
    $error = $errors->getBag($bag)->first($key);
    $password = $type === 'password';
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <div class="relative mt-2">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if (! $password) value="{{ old($key, $value) }}" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('id')->class([
                'block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs outline-none transition placeholder:text-slate-400 focus:ring-2',
                'pr-11' => $password,
                'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20' => ! $error,
                'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $error,
            ]) }}
        >
        @if ($password)
            {{-- Hidden until the script that runs it has loaded. --}}
            <button type="button" data-password-toggle="{{ $id }}" aria-controls="{{ $id }}" aria-label="Show password" aria-pressed="false" hidden
                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition hover:text-slate-600 focus-visible:text-blue-600 focus-visible:outline-none">
                <x-icon name="eye" data-password-shown="false" />
                <x-icon name="eye-off" data-password-shown="true" hidden />
            </button>
        @endif
    </div>

    @if ($strength)
        <div class="mt-2" data-password-strength="{{ $id }}" hidden>
            <div class="flex gap-1" aria-hidden="true">
                @for ($i = 0; $i < 4; $i++)
                    <span class="h-1.5 flex-1 rounded-full bg-slate-200 transition-colors"></span>
                @endfor
            </div>
            <p class="mt-1.5 text-xs text-slate-500" aria-live="polite"><span class="font-medium" data-strength-label></span><span data-strength-tip></span></p>
        </div>
    @endif

    @if ($confirms)
        <p class="mt-2 flex items-center gap-1.5 text-xs" data-password-match="{{ $confirms }}" data-for="{{ $id }}" aria-live="polite" hidden>
            <x-icon name="check" class="size-4" data-match-icon />
            <span data-match-text></span>
        </p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="mt-2 text-xs text-red-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
