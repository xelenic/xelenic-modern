<?php

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('only staff can open the admin panel', function () {
    $this->get('/admin')->assertRedirect('/login');

    $this->actingAs(userWithRole('user'))->get('/admin')->assertForbidden();
    $this->actingAs(userWithRole('user'))->get('/admin/projects')->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get('/admin')->assertOk()->assertSee('Overview');
    $this->actingAs(userWithRole('admin'))->get('/admin')->assertOk();
});

test('the client panel links staff to the admin panel', function () {
    $this->actingAs(userWithRole('manager'))->get('/dashboard')->assertSee('Admin panel')->assertSee('/admin"', false);
    $this->actingAs(userWithRole('user'))->get('/dashboard')->assertDontSee('Admin panel');
});

test('the overview counts projects by status and type', function () {
    Project::factory()->count(2)->create(['status' => ProjectStatus::Submitted, 'type' => ProjectType::KioskApp]);
    Project::factory()->create(['status' => ProjectStatus::InProgress, 'type' => ProjectType::Ecommerce]);
    Project::factory()->create(['status' => ProjectStatus::Completed, 'type' => ProjectType::Ecommerce]);

    $this->actingAs(userWithRole('admin'))->get('/admin')
        ->assertOk()
        ->assertSee(['Open projects', 'New requests', 'In progress', 'Projects by status', 'Projects by type', 'New users'])
        ->assertSee('Review new requests')
        ->assertSeeInOrder(['Submitted', '2', 'In review', '0', 'In progress', '1', 'Completed', '1'])
        ->assertSeeInOrder(['Kiosk App', '2'])
        ->assertSee('/admin/projects?status=submitted', false);
});

test('the overview only shows what each person may see', function () {
    // A support role that can manage projects but not users.
    $support = User::factory()->create()->assignRole(
        Role::create(['name' => 'support'])->givePermissionTo(Permission::findByName('manage projects'))
    );

    $this->actingAs($support)->get('/admin')
        ->assertOk()
        ->assertSee('Projects by status')
        ->assertDontSee('New users');

    $this->actingAs($support)->get('/admin/users')->assertForbidden();
});

test('the projects list filters by status, type and search', function () {
    $client = User::factory()->create(['name' => 'Priya Fernando', 'email' => 'priya@example.com']);
    Project::factory()->for($client)->create(['name' => 'Lobby kiosks', 'type' => ProjectType::KioskApp]);
    Project::factory()->create(['name' => 'Online shop', 'type' => ProjectType::Ecommerce, 'status' => ProjectStatus::InProgress]);

    $admin = userWithRole('admin');

    $this->actingAs($admin)->get('/admin/projects')->assertOk()->assertSee(['Lobby kiosks', 'Online shop', 'Priya Fernando']);
    $this->actingAs($admin)->get('/admin/projects?status=in_progress')->assertSee('Online shop')->assertDontSee('Lobby kiosks');
    $this->actingAs($admin)->get('/admin/projects?type=kiosk-app')->assertSee('Lobby kiosks')->assertDontSee('Online shop');
    $this->actingAs($admin)->get('/admin/projects?search=priya@example')->assertSee('Lobby kiosks')->assertDontSee('Online shop');
    $this->actingAs($admin)->get('/admin/projects?search=nothing-like-this')->assertSee('No projects match these filters.');
});

test('staff can open a project and change its status', function () {
    $manager = userWithRole('manager');
    $project = Project::factory()->create(['name' => 'Client project']);

    $this->actingAs($manager)->get("/admin/projects/{$project->id}")
        ->assertOk()
        ->assertSee(['Client project', 'Update status', $project->user->email]);

    $this->actingAs($manager)->patch("/admin/projects/{$project->id}/status", ['status' => 'in_progress'])
        ->assertRedirect("/admin/projects/{$project->id}");

    expect($project->fresh()->status)->toBe(ProjectStatus::InProgress);

    $this->actingAs($manager)->patch("/admin/projects/{$project->id}/status", ['status' => 'on_hold'])
        ->assertSessionHasErrors('status');
});

test('users and roles now live in the admin panel', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('Xelenic Admin', false);
    $this->actingAs($admin)->get('/admin/roles')->assertOk()->assertSee('Client panel');
    $this->actingAs($admin)->get('/dashboard')->assertOk()->assertDontSee('Recent sign-ups');
});
