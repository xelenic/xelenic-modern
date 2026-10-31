{{-- Kiosk step 3: the screen, with its live preview. Uses $preview from
     projects.kiosk-form. --}}
@use('App\Support\KioskSettings')

<x-card title="Screen" description="Size, resolution and which way up it stands.">
    <div class="space-y-5">
        @include('projects.screen-preview')

        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.select name="settings[screen_size]" label="Screen size" :options="KioskSettings::sizeOptions()" :value="$preview['screen_size']" :placeholder="null" />
            <x-form.select name="settings[resolution]" label="Resolution" :options="KioskSettings::resolutionOptions()" :value="$preview['resolution']" :placeholder="null" />
        </div>

        <fieldset>
            <legend class="text-sm font-medium text-slate-700">Orientation</legend>
            <div class="mt-2 grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1">
                @foreach (KioskSettings::ORIENTATIONS as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="settings[orientation]" value="{{ $value }}" class="peer sr-only" @checked($preview['orientation'] === $value)>
                        <span class="flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:outline-2 peer-focus-visible:outline-blue-600">
                            <x-icon :name="$value" class="size-4" />
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('settings.orientation')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>
    </div>
</x-card>
