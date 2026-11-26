{{-- Job applications from the Developer Careers page (Admin\JobApplicationController@index). --}}
@use('App\Enums\ApplicationStatus')

<x-layouts.admin title="Careers">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('careers')" target="_blank">
            <x-icon name="eye" class="size-4" />
            View careers page
        </x-button>
    </x-slot:actions>

    <p class="mb-6 max-w-2xl text-sm text-slate-500">
        Applications from the <a href="{{ route('careers') }}" target="_blank" class="font-medium text-blue-600 hover:text-blue-700">Developer Careers</a> page.
        The open roles are listed in <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">app/Support/Careers.php</code>.
    </p>

    <nav aria-label="Filter by status" class="mb-6 flex flex-wrap gap-2">
        @foreach ([null, ...ApplicationStatus::cases()] as $tab)
            @php($active = $status === $tab)
            <a href="{{ route('admin.careers.index', array_filter(['status' => $tab?->value])) }}" @if ($active) aria-current="page" @endif @class([
                'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                'bg-blue-600 text-white shadow-sm' => $active,
                'bg-white text-slate-600 ring-1 ring-slate-200 ring-inset hover:bg-slate-50 hover:text-slate-900' => ! $active,
            ])>
                {{ $tab?->label() ?? 'All' }}
                <span @class([
                    'rounded-full px-1.5 text-xs tabular-nums',
                    'bg-white/20' => $active,
                    'bg-amber-100 text-amber-800' => ! $active && $tab === ApplicationStatus::New && ($counts[$tab->value] ?? 0),
                    'bg-slate-100 text-slate-500' => ! $active && ! ($tab === ApplicationStatus::New && ($counts[$tab->value] ?? 0)),
                ])>{{ $tab ? ($counts[$tab->value] ?? 0) : $counts->sum() }}</span>
            </a>
        @endforeach
    </nav>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">Applicant</th>
                        <th scope="col" class="px-6 py-3">Role</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="hidden px-6 py-3 md:table-cell">Applied</th>
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($applications as $application)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 text-xs shrink-0 items-center justify-center rounded-full bg-blue-50 font-semibold text-blue-700">{{ Str::of($application->name)->explode(' ')->filter()->take(2)->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('') }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.careers.show', $application) }}" class="block truncate font-medium text-slate-900 hover:text-blue-700">{{ $application->name }}</a>
                                        <p class="truncate text-slate-500">{{ $application->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-slate-900">{{ $application->role_title }}</p>
                                <p class="flex gap-3 text-xs text-slate-500">
                                    @if ($application->cv_path) <span>CV attached</span> @endif
                                    @if ($application->portfolio_url) <span>Portfolio link</span> @endif
                                </p>
                            </td>
                            <td class="px-6 py-4"><x-badge :color="$application->status->color()">{{ $application->status->label() }}</x-badge></td>
                            <td class="hidden px-6 py-4 whitespace-nowrap text-slate-500 md:table-cell">{{ $application->created_at->format('j M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <x-button :variant="$application->status === ApplicationStatus::New ? 'primary' : 'secondary'" size="sm" :href="route('admin.careers.show', $application)">
                                    {{ $application->status === ApplicationStatus::New ? 'Review' : 'View' }}
                                </x-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">{{ $status ? 'No applications here.' : 'No applications yet. They show here as people apply.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $applications->links('partials.pagination') }}
    </div>
</x-layouts.admin>
