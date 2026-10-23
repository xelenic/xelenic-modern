<x-layouts.app title="Create project">
    <div class="max-w-5xl">
        <div class="mb-8">
            <h2 class="font-display text-2xl font-semibold text-slate-900">What would you like to build?</h2>
            <p class="mt-2 text-slate-500">Pick the option that fits best. You'll answer a few quick questions next.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($types as $type)
                <a href="{{ route('projects.create.type', $type) }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-600/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-500/20">
                        <x-icon :name="$type->icon()" class="size-6" />
                    </span>
                    <h3 class="mt-5 font-display text-lg font-semibold text-slate-900">{{ $type->label() }}</h3>
                    <p class="mt-2 flex-1 text-sm text-slate-500">{{ $type->description() }}</p>
                    <span class="mt-5 flex items-center gap-1 text-sm font-medium text-blue-600">
                        Get started
                        <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
