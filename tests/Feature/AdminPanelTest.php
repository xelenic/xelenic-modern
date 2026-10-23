<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('regular users cannot open the admin panel', function () {
    $user = userWithRole('user');

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
    $this->actingAs($user)->get('/admin/roles')->assertForbidden();
    $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('Recent sign-ups');
});

test('managers can see and edit users but not roles', function () {
    $manager = userWithRole('manager');
    $user = userWithRole('user');

    $this->actingAs($manager)->get('/admin/users')->assertOk()->assertSee($user->email);
    $this->actingAs($manager)->get("/admin/users/{$user->id}/edit")->assertOk();
    $this->actingAs($manager)->get('/admin/roles')->assertForbidden();
    $this->actingAs($manager)->delete("/admin/users/{$user->id}")->assertForbidden();
});

test('managers cannot change admin accounts or hand out roles', function () {
    $manager = userWithRole('manager');
    $admin = userWithRole('admin');
    $user = userWithRole('user');

    $this->actingAs($manager)->get("/admin/users/{$admin->id}/edit")->assertForbidden();

    $this->actingAs($manager)->put("/admin/users/{$user->id}", [
        'name' => $user->name,
        'email' => $user->email,
        'roles' => ['admin'],
    ])->assertRedirect('/admin/users');

    expect($user->fresh()->getRoleNames()->all())->toBe(['user']);
});

test('admins can create users and change their roles', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'New Manager',
        'email' => 'new.manager@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'roles' => ['manager'],
    ])->assertRedirect('/admin/users');

    $created = User::where('email', 'new.manager@example.com')->firstOrFail();
    expect($created->hasRole('manager'))->toBeTrue();

    $this->actingAs($admin)->put("/admin/users/{$created->id}", [
        'name' => 'New Manager',
        'email' => 'new.manager@example.com',
        'roles' => ['user'],
    ])->assertRedirect('/admin/users');

    expect($created->fresh()->getRoleNames()->all())->toBe(['user']);
});

test('admins cannot lock themselves out', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
        'name' => $admin->name,
        'email' => $admin->email,
        'roles' => ['user'],
    ])->assertSessionHasErrors('roles');

    $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertForbidden();

    expect($admin->fresh()->hasRole('admin'))->toBeTrue();
});

test('admins can delete other users', function () {
    $admin = userWithRole('admin');
    $user = userWithRole('user');

    $this->actingAs($admin)->delete("/admin/users/{$user->id}")->assertRedirect('/admin/users');

    expect(User::find($user->id))->toBeNull();
});

test('admins can create, edit and delete roles', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post('/admin/roles', [
        'name' => 'support',
        'permissions' => ['view users'],
    ])->assertRedirect('/admin/roles');

    $role = Role::findByName('support');
    expect($role->hasPermissionTo('view users'))->toBeTrue();

    $this->actingAs($admin)->put("/admin/roles/{$role->id}", [
        'name' => 'support-team',
        'permissions' => ['view users', 'edit users'],
    ])->assertRedirect('/admin/roles');

    expect($role->fresh())->name->toBe('support-team')
        ->and($role->fresh()->hasPermissionTo('edit users'))->toBeTrue();

    $this->actingAs($admin)->delete("/admin/roles/{$role->id}")->assertRedirect('/admin/roles');
    expect(Role::where('name', 'support-team')->exists())->toBeFalse();
});

test('built-in roles cannot be deleted and the admin role cannot be edited', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->delete('/admin/roles/'.Role::findByName('admin')->id)->assertForbidden();
    $this->actingAs($admin)->delete('/admin/roles/'.Role::findByName('user')->id)->assertForbidden();
    $this->actingAs($admin)->put('/admin/roles/'.Role::findByName('admin')->id, ['permissions' => []])->assertForbidden();
});

test('every admin page renders', function () {
    $admin = userWithRole('admin');
    $user = userWithRole('user');
    $role = Role::findByName('manager');

    foreach (['/dashboard', '/admin/users', '/admin/users/create', "/admin/users/{$user->id}/edit", '/admin/roles', '/admin/roles/create', "/admin/roles/{$role->id}/edit"] as $page) {
        $this->actingAs($admin)->get($page)->assertOk();
    }
});
