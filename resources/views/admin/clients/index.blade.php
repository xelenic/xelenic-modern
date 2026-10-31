<x-layouts.admin title="Clients">
    <x-slot:actions>
        <x-button :href="route('admin.clients.create')">
            <x-icon name="plus" class="size-4" />
            Add client
        </x-button>
    </x-slot:actions>

    <p class="mb-6 max-w-2xl text-sm text-slate-500">
        Published clients scroll across the <a href="{{ route('home') }}#clients" class="font-medium text-blue-600 hover:text-blue-700">home page</a>
        in this order, split over two rows. A logo opens the client's story page once it has a description, image, video or article.
    </p>

    @if ($clients->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <x-icon name="star" class="size-6" />
            </span>
            <h2 class="mt-4 font-display text-lg font-semibold text-slate-900">No clients yet</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">Add a client's logo to show it on the home page.</p>
            <x-button :href="route('admin.clients.create')" class="mt-6">
                <x-icon name="plus" class="size-4" />
                Add client
            </x-button>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach ($clients as $client)
                <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div @class([
                        'relative flex h-32 items-center justify-center border-b border-slate-100 p-6',
                        'bg-[#0c2038]' => $client->dark_logo,
                        'bg-white' => ! $client->dark_logo,
                    ])>
                        <img src="{{ $client->logoUrl() }}" alt="{{ $client->name }}" class="max-h-16 w-auto max-w-full object-contain" loading="lazy">
                        <span class="absolute top-3 left-3 rounded-md bg-slate-900/70 px-1.5 py-0.5 text-[11px] font-semibold text-white tabular-nums">#{{ $client->sort_order }}</span>
                    </div>

                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-display font-semibold text-slate-900">{{ $client->name }}</h2>
                            @if ($client->is_published)
                                <x-badge color="emerald">Published</x-badge>
                            @else
                                <x-badge color="slate">Hidden</x-badge>
                            @endif
                        </div>

                        {{-- What the story page has --}}
                        <ul class="mt-3 flex flex-1 flex-wrap content-start gap-x-3 gap-y-1.5 text-xs text-slate-500">
                            @if (filled($client->summary))
                                <li class="flex items-center gap-1"><x-icon name="check" class="size-3.5 text-emerald-600" /> Description</li>
                            @endif
                            @if ($client->image_path)
                                <li class="flex items-center gap-1"><x-icon name="image" class="size-3.5 text-slate-400" /> Image</li>
                            @endif
                            @if ($client->video_path || $client->video_url)
                                <li class="flex items-center gap-1"><x-icon name="video" class="size-3.5 text-slate-400" /> Video</li>
                            @endif
                            @if (filled($client->article))
                                <li class="flex items-center gap-1"><x-icon name="article" class="size-3.5 text-slate-400" /> Article</li>
                            @endif
                            @unless ($client->hasStory())
                                <li>Logo only, no story page yet</li>
                            @endunless
                        </ul>

                        <div class="mt-5 flex items-center gap-2 border-t border-slate-100 pt-4">
                            <x-button variant="secondary" size="sm" :href="route('admin.clients.edit', $client)">Edit</x-button>
                            @if ($client->is_published && $client->hasStory())
                                <x-button variant="secondary" size="sm" :href="route('clients.show', $client)" target="_blank">
                                    <x-icon name="eye" class="size-3.5" />
                                    View story
                                </x-button>
                            @endif
                            <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" data-confirm="Delete {{ $client->name }}? Their logo, image, video and story are removed too." class="ml-auto">
                                @csrf
                                @method('DELETE')
                                <x-button variant="danger" size="sm">Delete</x-button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
