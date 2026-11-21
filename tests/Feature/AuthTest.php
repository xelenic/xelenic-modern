<?php

use App\Models\Project;
use App\Models\User;

test('the login and sign-up pages render', function () {
    $this->get('/login')->assertOk()->assertSee('Welcome back')->assertSee('data-brand-scene', false);
    $this->get('/register')->assertOk()->assertSee('Create your account')->assertSee('data-brand-scene', false);
});

test('password fields can be shown, and new ones get a strength meter and a match check', function () {
    $this->get('/login')
        ->assertSee('data-password-toggle="password"', false)
        ->assertDontSee('data-password-strength', false);

    $this->get('/register')
        ->assertSee('data-password-toggle="password"', false)
        ->assertSee('data-password-toggle="password_confirmation"', false)
        ->assertSee('data-password-strength="password"', false)
        ->assertSee('data-password-match="password"', false);
    $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password?');
});

test('new users can sign up and start with the user role', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
    ])->assertRedirect('/dashboard');

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->getRoleNames()->all())->toBe(['user']);
});

test('users can log in and out', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

test('users cannot log in with the wrong password', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('guests are sent to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/profile')->assertRedirect('/login');
    $this->get('/admin/users')->assertRedirect('/login');
});

test('signed-in users see their dashboard and profile', function () {
    $user = User::factory()->create(['name' => 'Sam Rivera']);

    // Someone with no projects yet is welcomed in; after that, welcomed back.
    $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Welcome to Xelenic, Sam');
    Project::factory()->for($user)->create();
    $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Welcome back, Sam');
    $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Profile information');
});

test('users can update their profile through Fortify', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->put('/user/profile-information', ['name' => 'New Name', 'email' => 'new@example.com'])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'profile-information-updated');

    expect($user->fresh())->name->toBe('New Name')->email->toBe('new@example.com');
});
