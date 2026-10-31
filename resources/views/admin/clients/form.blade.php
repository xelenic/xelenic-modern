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
            <x-form.textarea
                name="article"
                label="Article"
                :value="$client->article"
                rows="18"
                class="font-mono leading-relaxed"
                hint="Written in Markdown: ## Heading, **bold**, *italic*, - list item, > quote, [link text](https://…). Leave a blank line between paragraphs."
            />
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
