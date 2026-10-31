<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Every permission the app checks for. Admins also pass every check
     * through the Gate::before rule in AppServiceProvider.
     */
    public const PERMISSIONS = [
        'view users',
        'create users',
        'edit users',
        'delete users',
        'manage roles',
        'manage projects',
        'manage clients',
    ];

    /**
     * Create the permissions and the default roles. Safe to run again.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)
            ->mapWithKeys(fn (string $name) => [$name => Permission::findOrCreate($name)]);

        Role::findOrCreate('admin')->syncPermissions($permissions);
        Role::findOrCreate('manager')->syncPermissions($permissions->only(['view users', 'create users', 'edit users', 'manage projects']));
        Role::findOrCreate('user');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
