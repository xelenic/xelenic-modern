{{-- Cloud settings (Admin\CloudController@settings): which products clients
     can order and the markup on InterServer's price for each. --}}
@use('App\Cloud\CloudSettings')

<x-layouts.admin title="Cloud settings">
    <x-slot:actions>
        <x-button variant="secondary" :href="route('admin.cloud.index')">Cloud orders</x-button>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <form method="POST" action="{{ route('admin.cloud.settings.update') }}" class="space-y-6 lg:col-span-2">
            @csrf
            @method('PUT')

            <x-card title="Products and pricing" description="Clients pay InterServer's price plus your markup. A new markup applies to new orders only.">
                <ul class="divide-y divide-slate-100">
                    @foreach ($products as $product)
                        @php
                            $markup = old("markup.{$product->value}", CloudSettings::markup($product));
                        @endphp
                        <li class="grid gap-4 py-5 first:pt-0 last:pb-0 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $product->tint() }}">
                                    <x-icon :name="$product->icon()" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <x-form.toggle name="enabled[{{ $product->value }}]" :label="$product->label()" :checked="CloudSettings::enabled($product)" :description="$product->kind().' · '.$product->blurb()" />
                                </div>
                            </div>
                            <div class="sm:w-40">
                                <label for="markup-{{ $product->value }}" class="block text-xs font-medium text-slate-600">Markup</label>
                                <div class="relative mt-1">
                                    <input id="markup-{{ $product->value }}" name="markup[{{ $product->value }}]" type="number" min="0" max="500" step="0.5" value="{{ $markup }}" required
                                        @class([
                                            'block w-full rounded-lg border bg-white py-2 pr-9 pl-3 text-sm text-slate-900 shadow-xs outline-none tabular-nums focus:ring-2',
                                            'border-slate-300 focus:border-blue-500 focus:ring-blue-500/20' => ! $errors->has("markup.{$product->value}"),
                                            'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $errors->has("markup.{$product->value}"),
                                        ])>
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                                </div>
                                @error("markup.{$product->value}") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <p class="mt-1 text-xs text-slate-500 tabular-nums">$10.00 costs ${{ number_format(10 * (1 + (float) $markup / 100), 2) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <div class="flex justify-end">
                <x-button>Save settings</x-button>
            </div>
        </form>

        <aside class="space-y-6">
            <x-card title="InterServer">
                @if (! $connection['configured'])
                    <p class="flex items-center gap-2 text-sm font-medium text-amber-700"><span class="size-2 rounded-full bg-amber-500"></span> No API key</p>
                    <p class="mt-3 text-sm text-slate-600">Get an API key from your InterServer <a href="https://my.interserver.net/account_settings" target="_blank" rel="noopener" class="font-medium text-blue-600 hover:text-blue-700">Account Security</a> page, then add it to the server's <code class="font-mono text-xs">.env</code>:</p>
                    <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 px-3 py-2.5 font-mono text-xs text-slate-100">INTERSERVER_API_KEY=your-key</pre>
                @elseif ($connection['reachable'])
                    <p class="flex items-center gap-2 text-sm font-medium text-emerald-700"><span class="size-2 rounded-full bg-emerald-500"></span> Connected</p>
                    <p class="mt-3 text-sm text-slate-600">The API key works. Approved orders are paid from the account's prepay balance, so keep it topped up.</p>
                @else
                    <p class="flex items-center gap-2 text-sm font-medium text-red-700"><span class="size-2 rounded-full bg-red-500"></span> Not answering</p>
                    <p class="mt-3 text-sm text-slate-600">InterServer didn't accept the API key, or couldn't be reached. Check the key in <code class="font-mono text-xs">.env</code>.</p>
                @endif
                <a href="https://my.interserver.net" target="_blank" rel="noopener" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-700">Open InterServer account</a>
            </x-card>

            <x-card title="How ordering works">
                <ol class="list-decimal space-y-2 pl-4 text-sm text-slate-600 marker:text-slate-400">
                    <li>A client chooses a service and places an order. Nothing is bought yet.</li>
                    <li>You approve it here. It's bought on InterServer and paid from the prepay balance.</li>
                    <li>InterServer sets it up; the client sees its details once it's running.</li>
                    <li>You bill the client the price shown on the order.</li>
                </ol>
            </x-card>
        </aside>
    </div>
</x-layouts.admin>
