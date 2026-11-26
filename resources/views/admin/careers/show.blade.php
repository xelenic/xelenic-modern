{{-- One job application (Admin\JobApplicationController@show). --}}
<x-layouts.admin :title="$application->name">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('admin.careers.index')">All applications</x-button>
    </x-slot:actions>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <span class="flex size-12 text-base shrink-0 items-center justify-center rounded-full bg-blue-50 font-semibold text-blue-700">{{ Str::of($application->name)->explode(' ')->filter()->take(2)->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('') }}</span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="truncate font-display text-2xl font-semibold text-slate-900">{{ $application->name }}</h2>
                <x-badge :color="$application->status->color()">{{ $application->status->label() }}</x-badge>
            </div>
            <p class="mt-1 text-slate-500">{{ $application->role_title }} · applied {{ $application->created_at->format('j M Y, H:i') }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            <x-card title="Contact">
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-500">Email</dt>
                        <dd class="mt-0.5 text-sm"><a href="mailto:{{ $application->email }}" class="font-medium text-blue-600 hover:text-blue-700">{{ $application->email }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Phone</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">
                            @if ($application->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $application->phone) }}" class="font-medium text-blue-600 hover:text-blue-700">{{ $application->phone }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Based in</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $application->location ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-500">Portfolio</dt>
                        <dd class="mt-0.5 truncate text-sm">
                            @if ($application->portfolio_url)
                                <a href="{{ $application->portfolio_url }}" target="_blank" rel="noopener noreferrer nofollow" class="font-medium text-blue-600 hover:text-blue-700">{{ $application->portfolio_url }}</a>
                            @else
                                <span class="text-slate-900">—</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="CV">
                @if ($application->hasCv())
                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><x-icon name="article" class="size-5" /></span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-900">{{ $application->cv_name }}</span>
                        <x-button size="sm" :href="route('admin.careers.cv', $application)">Download</x-button>
                    </div>
                @else
                    <p class="text-sm text-slate-500">No CV attached{{ $application->portfolio_url ? '; see their portfolio link.' : '.' }}</p>
                @endif
            </x-card>

            <x-card title="Why Xelenic?">
                @if (filled($application->message))
                    <p class="text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $application->message }}</p>
                @else
                    <p class="text-sm text-slate-500">They didn’t add a message.</p>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Progress">
                <form method="POST" action="{{ route('admin.careers.update', $application) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <x-form.select name="status" label="Status" :placeholder="null" :value="$application->status->value"
                        :options="collect(App\Enums\ApplicationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                    <x-form.textarea name="notes" label="Team notes" rows="5" :value="$application->notes" hint="Only the team sees these." />
                    <x-button class="w-full">Save</x-button>
                </form>
            </x-card>

            <form method="POST" action="{{ route('admin.careers.destroy', $application) }}" data-confirm="Delete {{ $application->name }}’s application and CV? This can’t be undone.">
                @csrf
                @method('DELETE')
                <x-button variant="danger" class="w-full">Delete application</x-button>
            </form>
        </aside>
    </div>
</x-layouts.admin>
