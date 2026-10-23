{{-- Fields shared by the create and edit forms; $user is null when creating. --}}
@php
    $selectedRoles = session()->hasOldInput()
        ? old('roles', [])
        : ($user?->getRoleNames()->all() ?? ['user']);
@endphp

<div class="space-y-5">
    <x-form.input name="name" label="Name" :value="$user?->name" autocomplete="off" required />
    <x-form.input name="email" type="email" label="Email" :value="$user?->email" autocomplete="off" required />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.input name="password" type="password" :label="$user ? 'New password' : 'Password'" autocomplete="new-password"
            :required="! $user" :hint="$user ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
        <x-form.input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" :required="! $user" />
    </div>

    @can('manage roles')
        <fieldset class="pt-2">
            <legend class="text-sm font-medium text-slate-700">Roles</legend>
            <p class="mt-1 text-xs text-slate-500">The user gets every permission in each role they have.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($roles as $role)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <x-form.checkbox
                            name="roles[]"
                            :value="$role->name"
                            :label="ucfirst($role->name)"
                            :checked="in_array($role->name, $selectedRoles)"
                            :disabled="$role->name === 'admin' && ! auth()->user()->hasRole('admin')"
                            :description="$role->name === 'admin' ? 'Full access to everything' : $role->permissions_count.' '.Str::plural('permission', $role->permissions_count)"
                        />
                    </div>
                @endforeach
            </div>

            @error('roles')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('roles.*')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>
    @elseif ($user)
        <div class="pt-2">
            <p class="text-sm font-medium text-slate-700">Roles</p>
            <div class="mt-2 flex flex-wrap gap-1.5">
                @forelse ($user->roles as $role)
                    <x-badge :role="$role->name" />
                @empty
                    <span class="text-sm text-slate-500">None</span>
                @endforelse
            </div>
            <p class="mt-2 text-xs text-slate-500">Only people who can manage roles can change these.</p>
        </div>
    @endcan
</div>
