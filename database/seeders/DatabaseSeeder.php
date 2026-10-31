<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(ClientSeeder::class);

        // One demo account per role for local development, all with the
        // password "password". Never seeded outside the local environment.
        if (app()->isLocal()) {
            foreach (['admin' => 'Admin User', 'manager' => 'Manager User', 'user' => 'Regular User'] as $role => $name) {
                User::firstOrCreate(
                    ['email' => "{$role}@example.com"],
                    ['name' => $name, 'password' => 'password'],
                )->syncRoles($role);
            }
        }
    }
}
