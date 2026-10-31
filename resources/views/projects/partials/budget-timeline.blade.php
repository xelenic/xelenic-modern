<div class="grid gap-5 sm:grid-cols-2">
    <x-form.select name="budget" label="Budget" :options="$budgets" placeholder="Choose a range" />
    <x-form.select name="timeline" label="Timeline" :options="$timelines" placeholder="Choose a timeline" />
</div>
