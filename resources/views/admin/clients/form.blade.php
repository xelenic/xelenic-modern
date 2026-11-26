{{-- Fields shared by the create and edit forms. $client is a new, unsaved
     Client when creating. The form needs enctype="multipart/form-data". --}}
<div class="grid gap-6 xl:grid-cols-3 xl:items-start">
    <div class="space-y-6 xl:col-span-2">
        <x-card title="Client" description="How the client is named on the site.">
            <div class="space-y-5">
                <x-form.input name="name" label="Name" :value="$client->name" required autofocus />
                <x-form.input name="slug" label="Web address" :value="$client->slug" :hint="'Their story lives at '.url('clients').'/'.($client->slug ?: 'name').'. Leave blank to use the name.'" />
                <x-form.textarea name="summary" label="Description" :value="$client->summary" rows="3" hint="One or two sentences, shown at the top of the story page. Up to 500 characters." />
            </div>
        </x-card>

        <x-card title="Article" description="The story itself: the challenge, what Xelenic built and the results.">
            {{-- Markdown with a toolbar, and a preview as the page will show it
                 (resources/js/markdown-editor.js). --}}
            <div data-md-editor data-preview-url="{{ route('admin.clients.preview-article') }}">
                <div class="flex items-end justify-between gap-3">
                    <label for="article" class="block text-sm font-medium text-slate-700">Article</label>
                    <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-semibold" role="tablist" aria-label="Article view">
                        <button type="button" role="tab" data-md-mode="write" aria-selected="true" class="rounded-md px-3 py-1 text-slate-500 transition aria-selected:bg-white aria-selected:text-slate-900 aria-selected:shadow-xs">Write</button>
                        <button type="button" role="tab" data-md-mode="preview" aria-selected="false" class="rounded-md px-3 py-1 text-slate-500 transition aria-selected:bg-white aria-selected:text-slate-900 aria-selected:shadow-xs">Preview</button>
                    </div>
                </div>

                <div @class([
                    'mt-2 overflow-hidden rounded-lg border bg-white shadow-xs transition focus-within:ring-2',
                    'border-slate-300 focus-within:border-blue-500 focus-within:ring-blue-500/20' => ! $errors->has('article'),
                    'border-red-400 focus-within:ring-red-500/20' => $errors->has('article'),
                ])>
                    <div class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-1.5 py-1" role="toolbar" aria-label="Formatting" data-md-toolbar>
                        @foreach ([
                            ['h2', 'Heading', 'H2'], ['h3', 'Subheading', 'H3'], ['bold', 'Bold (⌘B)', 'B'], ['italic', 'Italic (⌘I)', 'I'],
                            ['ul', 'Bulleted list', '• List'], ['ol', 'Numbered list', '1. List'], ['quote', 'Quote', '❝ Quote'], ['link', 'Link', 'Link'], ['hr', 'Divider', '―'],
                        ] as [$action, $title, $text])
                            <button type="button" data-md="{{ $action }}" title="{{ $title }}" aria-label="{{ $title }}" @class([
                                'rounded-md px-2 py-1 text-xs text-slate-600 transition hover:bg-white hover:text-slate-900 disabled:opacity-40 disabled:hover:bg-transparent',
                                'font-bold' => $action === 'bold' || str_starts_with($action, 'h'),
                                'italic font-serif' => $action === 'italic',
                                'font-medium' => ! in_array($action, ['bold', 'italic', 'h2', 'h3', 'hr'], true),
                            ])>{{ $text }}</button>
                        @endforeach
                    </div>
                    <textarea id="article" name="article" rows="18" data-md-input
                        @if ($errors->has('article')) aria-invalid="true" aria-describedby="article-error" @endif
                        placeholder="## The challenge&#10;&#10;What they needed, and why.&#10;&#10;## What we built&#10;&#10;…"
                        class="block w-full resize-y border-0 px-3.5 py-3 font-mono text-sm leading-relaxed text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0">{{ old('article', $client->article) }}</textarea>
                    <div data-md-preview hidden aria-live="polite" class="min-h-[28rem] px-5 py-4 text-sm leading-relaxed text-slate-700 [&_a]:text-blue-600 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-slate-200 [&_blockquote]:pl-4 [&_blockquote]:italic [&_h2]:mt-6 [&_h2]:font-display [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-slate-900 [&_h3]:mt-5 [&_h3]:font-semibold [&_h3]:text-slate-900 [&_hr]:my-6 [&_hr]:border-slate-200 [&_img]:rounded-lg [&_li]:mt-1 [&_ol]:mt-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mt-3 [&_strong]:font-semibold [&_strong]:text-slate-900 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5 [&>:first-child]:mt-0"></div>
                </div>

                @error('article')
                    <p id="article-error" class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @else
                    <p class="mt-2 text-xs text-slate-500">Use the toolbar, or write Markdown: ## Heading, **bold**, *italic*, - list item, > quote, [link text](https://…). Leave a blank line between paragraphs.</p>
                @enderror
            </div>
        </x-card>

        <x-card title="Photos & videos" description="A gallery on the story page, after the article. Add as many photos, video files and YouTube or Vimeo links as you like, each with an optional caption.">
            <div class="space-y-6">
                @if ($client->exists && $client->media->isNotEmpty())
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($client->media as $item)
                            @php
                                $key = "media.{$item->id}";
                            @endphp
                            <li class="flex gap-3 rounded-xl border border-slate-200 bg-white p-3 transition has-checked:border-red-200 has-checked:bg-red-50/60">
                                <div class="relative size-20 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                    @if ($item->isImage())
                                        <img src="{{ $item->fileUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                                    @elseif ($item->isVideo())
                                        <video src="{{ $item->fileUrl() }}#t=0.5" muted playsinline preload="metadata" class="size-full object-cover"></video>
                                        <span class="absolute inset-0 flex items-center justify-center bg-black/25 text-white"><x-icon name="play" class="size-5" /></span>
                                    @else
                                        <span class="flex size-full flex-col items-center justify-center gap-1 text-white">
                                            <x-icon name="video" class="size-5" />
                                            <span class="text-[10px] font-semibold">{{ $item->provider() }}</span>
                                        </span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <label for="media-{{ $item->id }}-caption" class="sr-only">Caption</label>
                                    <input id="media-{{ $item->id }}-caption" type="text" name="media[{{ $item->id }}][caption]" value="{{ old($key.'.caption', $item->caption) }}" maxlength="200" placeholder="Caption (optional)"
                                        class="block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-sm text-slate-900 shadow-xs outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                    <div class="flex items-center gap-3 text-xs text-slate-500">
                                        <label class="flex items-center gap-1.5">
                                            Position
                                            <input type="number" min="0" max="9999" name="media[{{ $item->id }}][sort_order]" value="{{ old($key.'.sort_order', $item->sort_order) }}"
                                                class="w-16 rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                        </label>
                                        <label class="ml-auto flex cursor-pointer items-center gap-1.5 font-medium text-red-600">
                                            <input type="checkbox" name="media[{{ $item->id }}][remove]" value="1" @checked(old($key.'.remove')) class="size-3.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                            Remove
                                        </label>
                                    </div>
                                    @if ($item->isLink())
                                        <p class="truncate text-xs text-slate-400">{{ $item->url }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div>
                    <x-form.file
                        id="new_media"
                        name="new_media[]"
                        :label="$client->exists && $client->media->isNotEmpty() ? 'Add more photos and videos' : 'Add photos and videos'"
                        accept="image/png,image/jpeg,image/webp,video/mp4,video/webm"
                        multiple
                        hint="PNG, JPG or WebP photos up to 5 MB, and MP4 or WebM videos up to 100 MB. Choose several at once; they go after what's there."
                    />
                    @foreach (array_unique([...$errors->get('new_media'), ...collect($errors->get('new_media.*'))->flatten()->all()]) as $message)
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @endforeach
                </div>

                <x-form.textarea name="new_links" label="Add YouTube or Vimeo videos" rows="2" placeholder="https://www.youtube.com/watch?v=…" hint="Paste links, one per line." />
            </div>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card title="Logo">
            <div class="space-y-4">
                @if ($client->logo_path)
                    <div @class([
                        'flex h-28 items-center justify-center rounded-xl border border-slate-200 p-5',
                        'bg-[#0c2038]' => $client->dark_logo,
                        'bg-white' => ! $client->dark_logo,
                    ])>
                        <img src="{{ $client->logoUrl() }}" alt="Current logo" class="max-h-16 w-auto max-w-full object-contain">
                    </div>
                @endif
                <x-form.file
                    name="logo"
                    :label="$client->logo_path ? 'Replace logo' : 'Logo'"
                    accept="image/png,image/jpeg,image/webp"
                    hint="PNG, JPG or WebP up to 2 MB. A wide logo on a transparent background looks best."
                    :required="! $client->logo_path"
                />
                <x-form.toggle name="dark_logo" label="Light logo" description="Show it on a navy tile instead of white." :checked="$client->dark_logo" />
            </div>
        </x-card>

        <x-card title="Featured image">
            <div class="space-y-4">
                @if ($client->image_path)
                    <img src="{{ $client->imageUrl() }}" alt="Current featured image" class="aspect-video w-full rounded-xl border border-slate-200 object-cover">
                    <x-form.checkbox name="remove_image" label="Remove this image" :checked="(bool) old('remove_image')" />
                @endif
                <x-form.file name="image" :label="$client->image_path ? 'Replace image' : 'Image'" accept="image/png,image/jpeg,image/webp" hint="PNG, JPG or WebP up to 5 MB, shown wide on the story page." />
            </div>
        </x-card>

        <x-card title="Video" description="Upload a short clip, or link to YouTube or Vimeo for longer videos.">
            <div class="space-y-4">
                @if ($client->video_path)
                    <video src="{{ $client->videoUrl() }}" controls preload="metadata" class="aspect-video w-full rounded-xl border border-slate-200 bg-black"></video>
                    <x-form.checkbox name="remove_video" label="Remove this video" :checked="(bool) old('remove_video')" />
                @endif
                <x-form.file name="video" :label="$client->video_path ? 'Replace video' : 'Upload video'" accept="video/mp4,video/webm" hint="MP4 or WebM up to 100 MB." />
                <x-form.input name="video_url" type="url" label="…or video link" :value="$client->video_url" placeholder="https://www.youtube.com/watch?v=…" hint="Used when no video is uploaded." />
            </div>
        </x-card>

        <x-card title="On the site">
            <div class="space-y-5">
                <x-form.toggle name="is_published" label="Published" description="Show the logo on the home page and the story page." :checked="$client->is_published" />
                <x-form.input name="sort_order" type="number" label="Position" :value="$client->sort_order" min="0" hint="Lower numbers come first." />
            </div>
        </x-card>
    </div>
</div>
