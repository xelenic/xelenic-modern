{{-- Cloud orders and services (Admin\CloudController@index). Orders waiting
     for approval and ones that need attention come first. --}}
@use('App\Enums\CloudStatus')

<x-layouts.admin title="Cloud">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('admin.cloud.settings')">Settings</x-button>
    </x-slot:actions>

    @if ($errors->any())
        <x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    {{-- The InterServer account everything is bought on --}}
    <div @class([
        'mb-6 flex flex-col gap-3 rounded-2xl border px-5 py-4 text-sm sm:flex-row sm:items-center sm:justify-between',
        'border-amber-200 bg-amber-50 text-amber-800' => ! $connection['configured'] || $connection['reachable'] === false,
        'border-slate-200 bg-white text-slate-600' => $connection['configured'] && $connection['reachable'],
    ])>
        <p class="flex items-center gap-2.5">
            @if (! $connection['configured'])
                <x-icon name="key" class="size-4 shrink-0" />
                <span>No InterServer API key yet, so clients can't order online. Add <code class="font-mono text-xs">INTERSERVER_API_KEY</code> to the server's <code class="font-mono text-xs">.env</code>.</span>
            @elseif ($connection['reachable'] === false)
                <x-icon name="cloud" class="size-4 shrink-0" />
                <span>InterServer isn't answering to the API key. Check the key, or try again in a few minutes.</span>
            @else
                <span class="size-2 shrink-0 rounded-full bg-emerald-500"></span>
                <span>Connected to InterServer. Approving an order buys it there, paid from the account's prepay balance.</span>
            @endif
        </p>
        <a href="https://my.interserver.net" target="_blank" rel="noopener" class="shrink-0 font-medium text-blue-600 hover:text-blue-700">InterServer account</a>
    </div>

    <nav aria-label="Filter by status" class="mb-6 flex flex-wrap gap-2">
        @foreach ([null, ...CloudStatus::cases()] as $tab)
            @php($active = $status === $tab)
            <a href="{{ route('admin.cloud.index', array_filter(['status' => $tab?->value])) }}" @if ($active) aria-current="page" @endif @class([
                'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                'bg-blue-600 text-white shadow-sm' => $active,
                'bg-white text-slate-600 ring-1 ring-slate-200 ring-inset hover:bg-slate-50 hover:text-slate-900' => ! $active,
            ])>
                {{ $tab?->label() ?? 'All' }}
                <span @class([
                    'rounded-full px-1.5 text-xs tabular-nums',
                    'bg-white/20' => $active,
                    'bg-amber-100 text-amber-800' => ! $active && $tab === CloudStatus::Requested && ($counts[$tab->value] ?? 0),
                    'bg-slate-100 text-slate-500' => ! $active && ! ($tab === CloudStatus::Requested && ($counts[$tab->value] ?? 0)),
                ])>{{ $tab ? ($counts[$tab->value] ?? 0) : $counts->sum() }}</span>
            </a>
        @endforeach
    </nav>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th scope="col" class="px-6 py-3">Service</th>
                        <th scope="col" class="px-6 py-3">Client</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="hidden px-6 py-3 text-right lg:table-cell">Cost</th>
                        <th scope="col" class="px-6 py-3 text-right">Price</th>
                        <th scope="col" class="hidden px-6 py-3 md:table-cell">Ordered</th>
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($services as $service)
                        <tr class="transition hover:bg-slate-50">
                            <td class="max-w-xs px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset {{ $service->product->tint() }}">
                                        <x-icon :name="$service->product->icon()" class="size-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.cloud.show', $service) }}" class="block truncate font-medium text-slate-900 hover:text-blue-700">{{ $service->name }}</a>
                                        <p class="truncate text-slate-500" title="{{ $service->plan }}">{{ $service->product->label() }} · {{ $service->plan }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="truncate text-slate-900">{{ $service->user->name }}</p>
                                <p class="truncate text-slate-500">{{ $service->user->email }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-start gap-1">
                                    <x-badge :color="$service->status->color()">{{ $service->status->label() }}</x-badge>
                                    @if ($service->cancel_requested_at && $service->status->isOpen())
                                        <span class="text-xs font-medium text-amber-700">Cancel requested</span>
                                    @endif
                                </div>
                            </td>
                            <td class="hidden px-6 py-4 text-right whitespace-nowrap text-slate-500 tabular-nums lg:table-cell">${{ number_format($service->cost, 2) }}</td>
                            <td class="px-6 py-4 text-right whitespace-nowrap tabular-nums">
                                <span class="font-medium text-slate-900">${{ number_format($service->price, 2) }}</span>
                                <span class="block text-xs text-slate-500">{{ $service->period === 1 ? ($service->product === App\Enums\CloudProduct::Domain ? 'a year' : 'a month') : "{$service->period} months" }}</span>
                            </td>
                            <td class="hidden px-6 py-4 whitespace-nowrap text-slate-500 md:table-cell">{{ $service->created_at->format('j M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <x-button :variant="in_array($service->status, [CloudStatus::Requested, CloudStatus::Failed], true) ? 'primary' : 'secondary'" size="sm" :href="route('admin.cloud.show', $service)">
                                    {{ $service->status === CloudStatus::Requested ? 'Review' : 'View' }}
                                </x-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">{{ $status ? 'Nothing here right now.' : 'No cloud orders yet. They show here as clients place them.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $services->links('partials.pagination') }}
    </div>
</x-layouts.admin>
