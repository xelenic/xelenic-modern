{{-- The one-page form for every project type except Kiosk App, which has its
     own steps (projects.kiosk-form). --}}
<x-layouts.app title="Create project">
    <div class="max-w-3xl">
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="type" value="{{ $type->value }}">

            {{-- The type picked on the previous step --}}
            <div class="flex items-center gap-4 rounded-2xl border border-blue-200 bg-blue-50/60 p-4">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-sky-400 text-white">
                    <x-icon :name="$type->icon()" class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-display font-semibold text-slate-900">{{ $type->label() }}</p>
                    <p class="text-sm text-slate-600">{{ $type->description() }}</p>
                </div>
                <a href="{{ route('projects.create') }}" class="shrink-0 text-sm font-medium text-blue-600 hover:text-blue-700">Change</a>
            </div>

            <x-card title="About your project" description="The basics, so we know where to start.">
                <div class="space-y-5">
                    <x-form.input name="name" label="Project name" placeholder="Give your project a short name" required />
                    <x-form.textarea name="description" label="Tell us about it" hint="What do you want to achieve, and who is it for?" required />
                    @include('projects.partials.budget-timeline')
                </div>
            </x-card>

            <x-card title="A few more details" description="Optional, but it helps us prepare.">
                <div class="space-y-6">
                    @foreach ($type->questions() as $question)
                        @include('projects.question')
                    @endforeach
                </div>
            </x-card>

            <div class="flex justify-end gap-3">
                <x-button variant="secondary" :href="route('projects.index')">Cancel</x-button>
                <x-button>Submit project</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
