{{-- The top of a project's page, for the client (projects.show) and the team
     (admin.projects.show): what it is and where it stands, with a progress
     bar except on kiosk projects. $designEditable puts the Kiosk Studio button
     here for the client. --}}
@php
    $designEditable ??= false;
    $design = $project->kioskDesign();
    $step = $project->status->step();
    $road = collect(\App\Enums\ProjectStatus::cases())
        ->filter(fn ($status) => $status->step() !== null)
        ->sortBy(fn ($status) => $status->step());
@endphp

<section class="rounded-2xl border border-slate-200 bg-white shadow-xs">
    <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center">
        <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
            <x-icon :name="$project->type->icon()" class="size-7" />
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-slate-500">{{ $project->type->label() }}</span>
                <x-badge :color="$project->status->color()">{{ $project->status->label() }}</x-badge>
            </div>
            <h2 class="mt-1 font-display text-2xl font-semibold text-slate-900">{{ $project->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                Submitted {{ $project->created_at->format('j F Y') }} · Updated {{ $project->updated_at->diffForHumans() }}
            </p>
        </div>
        @if ($designEditable)
            <x-button type="button" data-studio-open class="shrink-0">
                <x-icon name="kiosk" class="size-4" />
                {{ $design && \App\Support\KioskDesign::exists($project) ? 'Edit in Kiosk Studio' : 'Design in Kiosk Studio' }}
            </x-button>
        @endif
    </div>

    @unless ($project->type === \App\Enums\ProjectType::KioskApp)
        <div class="border-t border-slate-100 px-6 py-5">
            @if ($step === null)
                <p class="text-sm text-slate-500">This project was cancelled, so it's no longer moving forward.</p>
            @else
                <ol class="grid grid-cols-4 gap-3" aria-label="Progress">
                    @foreach ($road as $stop)
                        @php
                            $done = $stop->step() < $step;
                            $now = $stop->step() === $step;
                        @endphp
                        <li @if ($now) aria-current="step" @endif>
                            <span @class(['block h-1.5 rounded-full', 'bg-blue-600' => $done || $now, 'bg-slate-200' => ! $done && ! $now])></span>
                            <span @class([
                                'mt-2 flex items-center gap-1 text-xs',
                                'font-medium text-slate-700' => $done,
                                'font-semibold text-blue-700' => $now,
                                'text-slate-400' => ! $done && ! $now,
                            ])>
                                @if ($done)
                                    <x-icon name="check" class="size-3.5 shrink-0 text-blue-600" />
                                @endif
                                <span class="truncate">{{ $stop->label() }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @endunless
</section>
