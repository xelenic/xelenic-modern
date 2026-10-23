<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/**
 * A new user with the given role (run RolesAndPermissionsSeeder first).
 */
function userWithRole(string $role): User
{
    return User::factory()->create()->assignRole($role);
}
