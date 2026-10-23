<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    use PasswordValidationRules;

    /**
     * Each action needs its own permission (see RolesAndPermissionsSeeder).
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view users', only: ['index']),
            new Middleware('permission:create users', only: ['create', 'store']),
            new Middleware('permission:edit users', only: ['edit', 'update']),
            new Middleware('permission:delete users', only: ['destroy']),
        ];
    }

    /**
     * List users, optionally searched by name or email and filtered by role.
     */
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->string('role')->value(), fn ($query, string $role) => $query->whereRelation('roles', 'name', $role))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::withCount('permissions')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            ...$this->roleRules(),
        ]);

        $user = User::create(Arr::only($validated, ['name', 'email', 'password']));
        $user->syncRoles($this->rolesFromRequest($request));

        return to_route('admin.users.index')->with('success', "{$user->name} was added.");
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureCanChange($request, $user);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::withCount('permissions')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanChange($request, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            ...$this->roleRules(),
        ]);

        $roles = $this->rolesFromRequest($request, $user);

        if ($user->is($request->user()) && $user->hasRole('admin') && ! in_array('admin', $roles)) {
            throw ValidationException::withMessages([
                'roles' => "You can't remove your own admin role.",
            ]);
        }

        $user->fill(Arr::only($validated, ['name', 'email']));

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles($roles);

        return to_route('admin.users.index')->with('success', "{$user->name} was updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403, "You can't delete your own account.");
        $this->ensureCanChange($request, $user);

        $user->delete();

        return to_route('admin.users.index')->with('success', "{$user->name} was deleted.");
    }

    /**
     * Admin accounts can only be changed by other admins.
     */
    private function ensureCanChange(Request $request, User $user): void
    {
        abort_if(
            $user->hasRole('admin') && ! $request->user()->hasRole('admin'),
            403,
            'Only admins can change admin accounts.',
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function roleRules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(Role::class, 'name')],
        ];
    }

    /**
     * The roles $user should end up with. Only people who can manage roles
     * may change them (everyone else leaves them as they are, and new users
     * start as "user"), and only admins may hand out the admin role.
     *
     * @return array<int, string>
     */
    private function rolesFromRequest(Request $request, ?User $user = null): array
    {
        $current = $user?->getRoleNames()->all() ?? ['user'];

        if (! $request->user()->can('manage roles')) {
            return $current;
        }

        $roles = $request->input('roles', []);

        if (! $request->user()->hasRole('admin')) {
            $roles = array_diff($roles, ['admin']);
        }

        return array_values(array_unique($roles));
    }
}
