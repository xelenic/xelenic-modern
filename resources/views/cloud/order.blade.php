{{-- Ordering a cloud service (CloudController@create). Each product's form is
     in cloud/order/{product}.blade.php; resources/js/cloud-order.js keeps the
     summary and price up to date as choices change. Nothing is bought here:
     the order waits for the team's approval. --}}
<x-layouts.app :title="$product === App\Enums\CloudProduct::Domain ? 'Order a domain' : 'Order '.$product->label()">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('cloud.index')">Back to Cloud</x-button>
    </x-slot:actions>

    <div class="flex items-start gap-4">
        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $product->tint() }}">
            <x-icon :name="$product->icon()" class="size-6" />
        </span>
        <div>
            <p class="text-xs font-semibold tracking-wide text-sky-700 uppercase">{{ $product->kind() }}</p>
            <h2 class="mt-0.5 font-display text-2xl font-semibold text-slate-900">{{ $product->label() }}</h2>
            <p class="mt-1 text-slate-500">{{ $product->blurb() }}</p>
        </div>
    </div>

    @if ($error)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">{{ $error }}</div>
    @else
        @error('order')
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('cloud.store', $product) }}" class="mt-6 grid gap-6 lg:grid-cols-3 lg:items-start"
            data-cloud-order="{{ $product->value }}" data-markup="{{ $markup }}" @if ($product === App\Enums\CloudProduct::Domain) data-check-url="{{ route('cloud.domains.check') }}" @endif>
            @csrf

            <div class="min-w-0 space-y-6 lg:col-span-2">
                @include('cloud.order.'.$product->value)
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-display font-semibold text-slate-900">Your order</h3>
                    <dl class="mt-4 space-y-2.5 text-sm" data-summary>
                        @stack('summary')
                    </dl>
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <p class="text-sm text-slate-500" data-price-label>Price</p>
                        <p class="mt-1 font-display text-3xl font-semibold text-slate-900 tabular-nums" data-price>—</p>
                        <p class="mt-1 text-xs text-slate-500" data-price-note>&nbsp;</p>
                    </div>
                    <x-button class="mt-5 w-full">
                        Place order
                        <x-icon name="arrow-right" class="size-4" />
                    </x-button>
                </section>
                <p class="flex gap-2.5 px-1 text-xs leading-relaxed text-slate-500">
                    <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                    Nothing is charged yet. Our team confirms every order and the final price with you before it's set up.
                </p>
            </aside>
        </form>
    @endif
</x-layouts.app>
