{{-- The client's play link for a kiosk app (Project::share()): turn it on,
     copy or share it, swap it for a new one, or turn it off. Copy and share
     are in resources/js/play-link.js. --}}
<x-card title="Play link">
    @if ($project->isShared())
        <p class="text-sm text-slate-600">Anyone with this link can play your kiosk app in their browser, on a phone, tablet or computer.</p>

        <label for="play-link" class="sr-only">Play link</label>
        <div class="mt-4 flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 py-1 pr-1 pl-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20">
            <x-icon name="link" class="size-4 shrink-0 text-slate-400" />
            <input id="play-link" type="text" readonly value="{{ $project->playUrl() }}" data-play-link
                class="min-w-0 flex-1 border-0 bg-transparent p-0 py-1.5 font-mono text-xs text-slate-700 outline-none focus:ring-0">
            <button type="button" data-copy-play-link
                class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <x-icon name="copy" class="size-3.5" />
                <span data-copy-label>Copy</span>
            </button>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2">
            <x-button variant="secondary" size="sm" :href="$project->playUrl()" target="_blank" rel="noopener">
                <x-icon name="play" class="size-3.5" />
                Open
            </x-button>
            <x-button variant="secondary" size="sm" type="button" data-share-play-link :data-title="'Play '.($project->kioskDesign()['name'] ?? $project->name)" hidden>
                <x-icon name="share" class="size-3.5" />
                Share
            </x-button>
        </div>

        <p class="mt-3 text-xs text-slate-500">
            On since {{ $project->shared_at?->format('j M Y') }}. Players' form entries there aren't saved, and the link shows your latest saved design.
        </p>

        <div class="mt-4 flex items-center gap-5 border-t border-slate-100 pt-4 text-sm">
            <form method="POST" action="{{ route('projects.play-link', $project) }}" data-confirm="Make a new play link? The current link will stop working.">
                @csrf
                <button class="font-medium text-blue-600 hover:text-blue-700">New link</button>
            </form>
            <form method="POST" action="{{ route('projects.play-link.destroy', $project) }}" data-confirm="Turn off the play link? Anyone who has it will no longer be able to play.">
                @csrf
                @method('DELETE')
                <button class="font-medium text-red-600 hover:text-red-700">Turn off</button>
            </form>
        </div>
    @else
        <div class="flex items-start gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <x-icon name="gamepad" class="size-4" />
            </span>
            <p class="text-sm text-slate-600">Share a link so anyone can play your kiosk app in their browser. Handy for testing with your team or showing it off before the kiosk arrives.</p>
        </div>
        <form method="POST" action="{{ route('projects.play-link', $project) }}" class="mt-5">
            @csrf
            <x-button class="w-full">
                <x-icon name="link" class="size-4" />
                Create play link
            </x-button>
        </form>
    @endif
</x-card>
