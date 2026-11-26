<x-layouts.app title="Cloud">
    {{-- Order something new --}}
    <section class="rounded-3xl border border-sky-100 bg-[#eaf5ff] p-6 sm:p-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-sky-700">Xelenic Cloud</p>
                <h2 class="mt-1 font-display text-2xl font-semibold text-slate-900 sm:text-3xl">Servers, hosting and domains</h2>
                <p class="mt-2 max-w-2xl text-slate-600">Choose what you need and place an order. Our team confirms it, sets it up for you, and looks after it with you.</p>
            </div>
        </div>

        @unless ($available)
            <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Ordering online opens soon. In the meantime, <a href="{{ route('home') }}#contact" class="font-semibold underline">contact us</a> and we'll set things up for you.
            </p>
        @endunless

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($products as $product)
                <a href="{{ route('cloud.create', $product) }}" class="group flex flex-col rounded-2xl border border-white bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md">
                    <span class="flex size-11 items-center justify-center rounded-xl ring-1 ring-inset {{ $product->tint() }}">
                        <x-icon :name="$product->icon()" class="size-5" />
                    </span>
                    <span class="mt-4 text-xs font-semibold tracking-wide text-slate-400 uppercase">{{ $product->kind() }}</span>
                    <span class="mt-0.5 font-display text-lg font-semibold text-slate-900 group-hover:text-sky-700">{{ $product->label() }}</span>
                    <span class="mt-1 flex-1 text-sm text-slate-500">{{ $product->blurb() }}</span>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-sky-700">
                        {{ $product === App\Enums\CloudProduct::Domain ? 'Find a domain' : 'Choose and order' }}
                        <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- What they have --}}
    <section class="mt-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="font-display text-lg font-semibold text-slate-900">Your services</h2>
                <p class="mt-1 text-sm text-slate-500">Everything you've ordered, newest first.</p>
            </div>
        </div>

        @if ($services->isEmpty())
            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <x-icon name="cloud" class="size-6" />
                </span>
                <h3 class="mt-4 font-display font-semibold text-slate-900">Nothing here yet</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Your servers, hosting and domains will show here once you order them.</p>
            </div>
        @else
            <ul class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach ($services as $service)
                    <li>
                        <a href="{{ route('cloud.show', $service) }}" class="group flex gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $service->product->tint() }}">
                                <x-icon :name="$service->product->icon()" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-start justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-slate-900 group-hover:text-sky-700">{{ $service->name }}</span>
                                        <span class="block truncate text-xs text-slate-500">{{ $service->product->label() }} · {{ $service->plan }}</span>
                                    </span>
                                    <x-badge :color="$service->status->color()" class="shrink-0">{{ $service->status->label() }}</x-badge>
                                </span>
                                <span class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                    <span class="font-medium text-slate-700">{{ $service->priceLabel() }}</span>
                                    @if ($service->remote('ip'))
                                        <span class="font-mono">{{ $service->remote('ip') }}</span>
                                    @endif
                                    @if ($service->isActive() && $service->next_due_at)
                                        <span>Renews {{ $service->next_due_at->format('j M Y') }}</span>
                                    @else
                                        <span>Ordered {{ $service->created_at->format('j M Y') }}</span>
                                    @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
