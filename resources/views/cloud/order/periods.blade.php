{{-- Billing period choices (InterServer's, with their discounts). --}}
<div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
    @foreach (App\Cloud\CloudSettings::periods() as $months => $discount)
        <label class="relative flex cursor-pointer flex-col items-center rounded-xl border border-slate-200 bg-white px-3 py-3 text-center transition hover:border-slate-300 has-checked:border-blue-600 has-checked:ring-1 has-checked:ring-blue-600 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-blue-600">
            <input type="radio" name="period" value="{{ $months }}" class="sr-only" @checked($period === $months) required>
            <span class="font-semibold text-slate-900">{{ $months }} {{ Str::plural($unit, $months) }}</span>
            <span @class(['mt-0.5 text-xs', 'font-semibold text-emerald-700' => $discount, 'text-slate-400' => ! $discount])>{{ $discount ? 'Save '.$discount.'%' : 'Monthly' }}</span>
        </label>
    @endforeach
</div>
@error('period') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
