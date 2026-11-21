<x-layouts.admin :title="'Edit '.$template->name">
    <form method="POST" action="{{ route('admin.game-templates.update', $template) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('admin.game-templates.form')

        <div class="mt-6 flex justify-end gap-3">
            <x-button variant="secondary" :href="route('admin.game-templates.index')">Cancel</x-button>
            <x-button>Save changes</x-button>
        </div>
    </form>

    <x-card
        class="mt-8"
        title="Reviews"
        :description="$reviews->isEmpty()
            ? 'No one has reviewed this game yet. Clients rate games on their page in Kiosk Studio\'s marketplace.'
            : number_format($template->averageRating(), 1).' out of 5 from '.trans_choice('{1} :count review|[2,*] :count reviews', $reviews->count()).'. Remove any that break the rules.'"
    >
        @if ($reviews->isNotEmpty())
            <ul class="-my-4 divide-y divide-slate-100">
                @foreach ($reviews as $review)
                    <li class="flex items-start gap-4 py-4">
                        <x-avatar :user="$review->user" size="sm" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <p class="font-medium text-slate-900">{{ $review->user->name }}</p>
                                <x-stars :rating="$review->rating" />
                                <span class="text-xs text-slate-400">{{ $review->updated_at->diffForHumans() }}</span>
                            </div>
                            @if ($review->comment)
                                <p class="mt-1.5 text-sm whitespace-pre-line text-slate-600">{{ $review->comment }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.game-templates.reviews.destroy', [$template, $review]) }}" data-confirm="Remove {{ $review->user->name }}'s review? This can't be undone.">
                            @csrf
                            @method('DELETE')
                            <x-button variant="secondary" size="sm">Remove</x-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layouts.admin>
