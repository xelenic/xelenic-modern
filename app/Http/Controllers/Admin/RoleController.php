<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    /**
     * Roles the app looks up by name ("admin" in Gate::before, "user" for
     * new sign-ups), so they can't be renamed or deleted.
     */
    public const PROTECTED_ROLES = ['admin', 'user'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage roles'),
        ];
    }

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('users')->with('permissions')->orderBy('name')->get(),
            'protectedRoles' => self::PROTECTED_ROLES,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => $this->groupedPermissions(),
            'protectedRoles' => self::PROTECTED_ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique(Role::class, 'name')],
            ...$this->permissionRules(),
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('admin.roles.index')->with('success', "The {$role->name} role was created.");
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $this->groupedPermissions(),
            'protectedRoles' => self::PROTECTED_ROLES,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'admin', 403, 'The admin role always has every permission.');

        $renamable = ! in_array($role->name, self::PROTECTED_ROLES);

        $validated = $request->validate([
            ...($renamable ? ['name' => ['required', 'string', 'max:255', Rule::unique(Role::class, 'name')->ignore($role)]] : []),
            ...$this->permissionRules(),
        ]);

        if ($renamable) {
            $role->update(['name' => $validated['name']]);
        }

        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('admin.roles.index')->with('success', "The {$role->name} role was updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(in_array($role->name, self::PROTECTED_ROLES), 403, "The {$role->name} role can't be deleted.");

        $role->delete();

        return to_route('admin.roles.index')->with('success', "The {$role->name} role was deleted.");
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function permissionRules(): array
    {
        return [
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists(Permission::class, 'name')],
        ];
    }

    /**
     * Every permission, grouped by what it acts on ("edit users" → "users").
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    private function groupedPermissions(): Collection
    {
        return Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => Str::afterLast($permission->name, ' '));
    }
}
