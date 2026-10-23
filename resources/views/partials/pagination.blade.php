@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="flex items-center justify-between gap-4">
        <p class="text-sm text-slate-500">
            Showing <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
        </p>

        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-400">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-xs hover:bg-slate-50">Previous</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-xs hover:bg-slate-50">Next</a>
            @else
                <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-400">Next</span>
            @endif
        </div>
    </nav>
@endif
