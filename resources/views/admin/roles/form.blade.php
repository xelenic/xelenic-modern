{{-- Fields shared by the create and edit forms; $role is null when creating. --}}
@php
    $selectedPermissions = session()->hasOldInput()
        ? old('permissions', [])
        : ($role?->permissions->pluck('name')->all() ?? []);
    $renamable = ! $role || ! in_array($role->name, $protectedRoles);
@endphp

<div class="space-y-8">
    @if ($renamable)
        <x-form.input name="name" label="Role name" :value="$role?->name" placeholder="e.g. editor" autocomplete="off" required />
    @else
        <div>
            <p class="text-sm font-medium text-slate-700">Role name</p>
            <p class="mt-2 text-sm text-slate-900">
                {{ ucfirst($role->name) }}
                <span class="ml-2 text-xs text-slate-500">Built-in roles can't be renamed.</span>
            </p>
        </div>
    @endif

    <fieldset>
        <legend class="text-sm font-medium text-slate-700">Permissions</legend>
        <p class="mt-1 text-xs text-slate-500">Everyone with this role can do these things.</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($permissions as $group => $groupPermissions)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-3 text-xs font-medium tracking-wider text-slate-500 uppercase">{{ $group }}</p>
                    <div class="space-y-3">
                        @foreach ($groupPermissions as $permission)
                            <x-form.checkbox
                                name="permissions[]"
                                :value="$permission->name"
                                :label="ucfirst($permission->name)"
                                :checked="in_array($permission->name, $selectedPermissions)"
                            />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @error('permissions.*')
            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </fieldset>
</div>
