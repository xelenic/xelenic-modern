{{-- One of a project type's extra questions (see ProjectType::questions()). --}}
@php($field = "details[{$question['name']}]")

@switch($question['type'])
    @case('select')
        <x-form.select :name="$field" :label="$question['label']" :options="$question['options']" />
        @break

    @case('checkboxes')
        @php($checked = old("details.{$question['name']}", []))
        <fieldset>
            <legend class="text-sm font-medium text-slate-700">{{ $question['label'] }}</legend>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($question['options'] as $option)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <x-form.checkbox :name="$field.'[]'" :value="$option" :label="$option" :checked="in_array($option, $checked)" />
                    </div>
                @endforeach
            </div>
            @error("details.{$question['name']}.*")
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>
        @break

    @default
        <x-form.input
            :name="$field"
            :type="$question['type']"
            :label="$question['label']"
            :placeholder="$question['placeholder'] ?? null"
            :min="$question['type'] === 'number' ? 1 : null"
        />
@endswitch
