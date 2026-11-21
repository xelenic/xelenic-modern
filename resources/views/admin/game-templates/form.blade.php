{{-- Fields shared by the create and edit forms. $template is a new, unsaved
     GameTemplate when adding one. The form needs enctype="multipart/form-data". --}}
@php
    $adding = ! $template->exists;
@endphp

<div class="grid gap-6 xl:grid-cols-3 xl:items-start">
    <div class="space-y-6 xl:col-span-2">
        @unless ($template->isBuiltin())
            <x-card
                title="Template package"
                :description="$adding
                    ? 'A Kiosk Studio game template .zip: a manifest.json, plus game.js and any assets for a custom game.'
                    : 'Upload a new version of '.$template->slug.'.zip. Its id has to stay '.$template->slug.'.'"
            >
                <div class="space-y-4">
                    @unless ($adding)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            @include('admin.game-templates.icon')
                            <div class="min-w-0 text-sm">
                                <p class="truncate font-mono text-slate-700">{{ $template->slug }}.zip</p>
                                <p class="text-xs text-slate-500">
                                    v{{ $template->version }}@if ($template->author) · by {{ $template->author }}@endif
                                    · {{ $template->kind === 'preset' ? 'Preset of '.ucfirst($template->extends) : 'Custom game' }}
                                </p>
                            </div>
                            <x-button variant="secondary" size="sm" :href="route('game-templates.download', $template)" class="ml-auto shrink-0">Download</x-button>
                        </div>
                    @endunless
                    <x-form.file
                        name="package"
                        :label="$adding ? 'Template .zip' : 'New version'"
                        accept=".zip,application/zip"
                        :hint="'Up to '.(App\Support\GameTemplatePackage::MAX_KILOBYTES / 1024).' MB. The version, author, icon and colour come from its manifest.json. To start one, use Insert → Game… → Sample template in Kiosk Studio.'"
                        :required="$adding"
                    />
                </div>
            </x-card>
        @endunless

        <x-card title="In the marketplace" description="How the game is named in Kiosk Studio.">
            <div class="space-y-5">
                <x-form.input
                    name="name"
                    label="Name"
                    :value="$template->name"
                    :required="! $adding"
                    :hint="$adding ? 'Leave blank to use the name in the template\'s manifest.json.' : null"
                />
                <x-form.textarea
                    name="description"
                    label="Description"
                    :value="$template->description"
                    rows="3"
                    :hint="$adding ? 'One or two sentences, up to 500 characters. Leave blank to use the manifest\'s.' : 'One or two sentences, up to 500 characters.'"
                />
            </div>
        </x-card>

        <x-card title="Store page" description="What clients see when they open the game in Kiosk Studio's marketplace, above its reviews.">
            <div class="space-y-6">
                <x-form.textarea
                    name="details"
                    label="About this game"
                    :value="$template->details"
                    rows="8"
                    class="font-mono leading-relaxed"
                    hint="How it plays and what it's good for. Markdown: ## Heading, **bold**, - list item. Up to 5,000 characters."
                />

                <div>
                    <p class="text-sm font-medium text-slate-700">Screenshots</p>
                    @if ($template->screenshots)
                        <div class="mt-2 mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($template->screenshotUrls() as $i => $url)
                                <label class="relative block cursor-pointer overflow-hidden rounded-xl border border-slate-200 bg-slate-50 has-checked:border-red-300 has-checked:opacity-60">
                                    <img src="{{ $url }}" alt="Screenshot {{ $i + 1 }}" class="aspect-[3/4] w-full object-cover" loading="lazy">
                                    <span class="absolute inset-x-0 bottom-0 flex items-center gap-2 bg-white/90 px-2.5 py-1.5 text-xs font-medium text-slate-700">
                                        <input type="checkbox" name="remove_screenshots[]" value="{{ $template->screenshots[$i] }}" @checked(in_array($template->screenshots[$i], old('remove_screenshots', []), true)) class="size-3.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                        Remove
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <x-form.file
                        id="screenshots"
                        name="screenshots[]"
                        :label="$template->screenshots ? 'Add more' : 'Add screenshots'"
                        accept="image/png,image/jpeg,image/webp"
                        multiple
                        :hint="'PNG, JPG or WebP up to 4 MB each, '.App\Models\GameTemplate::MAX_SCREENSHOTS.' at most. Choose several at once. Without any, the studio shows previews of the game itself.'"
                    />
                    @foreach (array_unique([...$errors->get('screenshots'), ...collect($errors->get('screenshots.*'))->flatten()->all()]) as $message)
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        @if ($template->isBuiltin())
            <x-card title="Built-in game">
                <div class="flex items-center gap-3">
                    @include('admin.game-templates.icon')
                    <p class="text-sm text-slate-500">{{ $template->slug }} is one of Kiosk Studio's own games. It can be renamed, moved or hidden, but not replaced or removed.</p>
                </div>
            </x-card>
        @endif

        <x-card title="Cover photo">
            <div class="space-y-4">
                @if ($template->thumbnail_path)
                    <img src="{{ $template->thumbnailUrl() }}" alt="Current cover photo" class="aspect-video w-full rounded-xl border border-slate-200 object-cover">
                    <x-form.checkbox name="remove_thumbnail" label="Remove this photo" :checked="(bool) old('remove_thumbnail')" />
                @endif
                <x-form.file
                    name="thumbnail"
                    :label="$template->thumbnail_path ? 'Replace photo' : 'Photo'"
                    accept="image/png,image/jpeg,image/webp"
                    hint="PNG, JPG or WebP up to 2 MB, on the game's card and at the top of its page. Wide (16:9) works best. Without one, the studio shows a live preview of the game."
                />
            </div>
        </x-card>

        <x-card title="Offering">
            <div class="space-y-5">
                <x-form.toggle name="is_active" label="Offered" description="Show it in Kiosk Studio's marketplace." :checked="$template->is_active" />
                <x-form.input name="sort_order" type="number" label="Position" :value="$template->sort_order" min="0" required hint="Lower numbers come first." />
            </div>
        </x-card>
    </div>
</div>
