<x-layouts.app title="Dashboard">
    {{-- Greeting --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-linear-to-br from-blue-50 via-white to-sky-50 p-6 shadow-sm sm:p-8">
        <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-10 size-72 rounded-full bg-blue-200/40 blur-3xl"></div>

        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
            <x-avatar :user="$user" size="lg" />
            <div class="flex-1">
                <p class="text-sm text-slate-500">{{ now()->format('l, j F Y') }}</p>
                <h2 class="mt-1 font-display text-2xl font-semibold text-slate-900">
                    Welcome back, {{ Str::before($user->name, ' ') }}
                </h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($roles as $role)
                        <x-badge :role="$role" />
                    @empty
                        <x-badge color="slate">No role yet</x-badge>
                    @endforelse
                </div>
            </div>
            <x-button variant="secondary" :href="route('profile.edit')">Edit profile</x-button>
        </div>
    </div>

    {{-- Staff get a way into the admin panel --}}
    @can('access-admin')
        <a href="{{ route('admin.dashboard') }}" class="group mt-6 flex items-center gap-4 rounded-2xl border border-blue-200 bg-blue-50/60 p-5 transition hover:border-blue-300 hover:bg-blue-50">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-sky-400 text-white">
                <x-icon name="shield" class="size-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block font-display font-semibold text-slate-900">Admin panel</span>
                <span class="block text-sm text-slate-600">Review project requests, manage users and set up roles.</span>
            </span>
            <x-icon name="arrow-right" class="size-5 text-blue-600 transition group-hover:translate-x-0.5" />
        </a>
    @endcan

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Your projects" description="What you've asked the Xelenic team to build.">
                <x-slot:actions>
                    <a href="{{ route('projects.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
                </x-slot:actions>

                @if ($projects->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 px-6 py-8 text-center">
                        <p class="text-sm text-slate-500">No projects yet. Tell us what you'd like to build.</p>
                        <x-button size="sm" :href="route('projects.create')" class="mt-4">
                            <x-icon name="plus" class="size-3.5" />
                            Create project
                        </x-button>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($projects as $project)
                            <li>
                                <a href="{{ route('projects.show', $project) }}" class="group flex items-center gap-3 py-3">
                                    <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
                                        <x-icon :name="$project->type->icon()" class="size-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-900 group-hover:text-blue-700">{{ $project->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $project->type->label() }} · {{ $project->created_at->format('j M Y') }}</p>
                                    </div>
                                    <x-badge :color="$project->status->color()">{{ $project->status->label() }}</x-badge>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Quick links" description="Jump straight to what you need.">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-quick-link :href="route('profile.edit')" icon="user" title="Your profile">Keep your name and email up to date.</x-quick-link>
                    <x-quick-link :href="route('profile.edit').'#password'" icon="key" title="Change password">Use a strong password you don't use elsewhere.</x-quick-link>
                    <x-quick-link :href="route('home')" icon="globe" title="Explore Xelenic">AI middleware, business systems and kiosk apps.</x-quick-link>
                    <x-quick-link :href="route('projects.create')" icon="folder" title="Start a project">Kiosks, software, ecommerce, mobile apps and more.</x-quick-link>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Your access" description="What your roles let you do.">
                @role('admin')
                    <p class="text-sm text-slate-600">
                        You're an admin, so you have <span class="font-medium text-slate-900">full access</span> to everything, including the admin panel.
                    </p>
                @else
                    @if ($permissions->isEmpty())
                        <p class="text-sm text-slate-500">You have standard account access. An admin can give you more.</p>
                    @else
                        <ul class="space-y-2.5">
                            @foreach ($permissions as $permission)
                                <li class="flex items-center gap-2.5 text-sm text-slate-700">
                                    <x-icon name="check" class="size-4 text-emerald-600" />
                                    {{ ucfirst($permission) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endrole
            </x-card>

            <x-card title="Account">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Email</dt>
                        <dd class="mt-1 break-all text-slate-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Member since</dt>
                        <dd class="mt-1 text-slate-900">{{ $user->created_at->format('j F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Last updated</dt>
                        <dd class="mt-1 text-slate-900">{{ $user->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts.app>
