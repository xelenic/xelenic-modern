<x-layouts.admin title="Game templates">
    <x-slot:actions>
        <x-button :href="route('admin.game-templates.create')">
            <x-icon name="plus" class="size-4" />
            Add template
        </x-button>
    </x-slot:actions>

    <p class="mb-6 max-w-2xl text-sm text-slate-500">
        The games clients can add to their kiosk app in Kiosk Studio's game templates marketplace, in this order.
        Kiosk Studio's own games can be hidden or moved but not removed. Designs that already use a game keep their own copy of it.
    </p>

    @if ($templates->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <x-icon name="gamepad" class="size-6" />
            </span>
            <h2 class="mt-4 font-display text-lg font-semibold text-slate-900">No game templates yet</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">Upload a game template .zip to offer it in Kiosk Studio.</p>
            <x-button :href="route('admin.game-templates.create')" class="mt-6">
                <x-icon name="plus" class="size-4" />
                Add template
            </x-button>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach ($templates as $template)
                <div class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    @if ($template->thumbnail_path)
                        <img src="{{ $template->thumbnailUrl() }}" alt="" class="-mx-5 -mt-5 mb-4 aspect-[2/1] w-[calc(100%+2.5rem)] max-w-none rounded-t-2xl object-cover" loading="lazy">
                    @endif
                    <div class="flex items-start gap-3">
                        @include('admin.game-templates.icon')
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="truncate font-display font-semibold text-slate-900">{{ $template->name }}</h2>
                                @if ($template->is_active)
                                    <x-badge color="emerald">Offered</x-badge>
                                @else
                                    <x-badge color="slate">Hidden</x-badge>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate font-mono text-xs text-slate-400">#{{ $template->sort_order }} · {{ $template->slug }} · v{{ $template->version }}</p>
                        </div>
                    </div>

                    <p class="mt-3 line-clamp-3 flex-1 text-sm text-slate-500">{{ $template->description ?: 'No description.' }}</p>

                    <p class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                        @if ($template->reviews_count)
                            <x-stars :rating="$template->reviews_avg_rating" size="size-3.5" />
                            <span class="font-semibold text-slate-700">{{ number_format($template->averageRating(), 1) }}</span>
                            <span>{{ trans_choice('{1} :count review|[2,*] :count reviews', $template->reviews_count) }}</span>
                        @else
                            <x-stars :rating="0" size="size-3.5" />
                            <span>No reviews yet</span>
                        @endif
                        @if ($template->screenshots)
                            <span class="ml-auto">{{ trans_choice('{1} :count screenshot|[2,*] :count screenshots', count($template->screenshots)) }}</span>
                        @endif
                    </p>

                    <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        @if ($template->isBuiltin())
                            <x-badge color="blue">Built in</x-badge>
                        @elseif ($template->kind === 'preset')
                            <x-badge color="violet">Preset of {{ ucfirst($template->extends) }}</x-badge>
                        @else
                            <x-badge color="amber">Custom game</x-badge>
                        @endif
                        @if ($template->author)
                            <span class="truncate">by {{ $template->author }}</span>
                        @endif
                    </p>

                    <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-4">
                        <x-button variant="secondary" size="sm" :href="route('admin.game-templates.edit', $template)">Edit</x-button>
                        @unless ($template->isBuiltin())
                            <form method="POST" action="{{ route('admin.game-templates.destroy', $template) }}" data-confirm="Remove {{ $template->name }} from the marketplace? Its .zip is deleted too. Designs that already use it keep working." class="ml-auto">
                                @csrf
                                @method('DELETE')
                                <x-button variant="danger" size="sm">Remove</x-button>
                            </form>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
