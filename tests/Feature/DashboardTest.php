<?php

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Support\KioskDesign;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a dashboard with no projects offers Xelenic’s services to choose from', function () {
    $this->actingAs(userWithRole('user'))
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Kiosk Engine', 'Cloud Services', 'Hire Our Engineers', 'Custom Development'])
        ->assertSee('action="'.route('projects.create').'"', false)
        ->assertSeeInOrder(['value="kiosk-app"', 'value="cloud"', 'value="hire-engineers"', 'value="software-solution"'], false)
        ->assertDontSee('All projects');
});

test('choosing a service on the dashboard opens that service’s form', function () {
    $user = userWithRole('user');

    $this->actingAs($user)->get('/projects/create?type=kiosk-app')->assertRedirect('/projects/create/kiosk-app');
    $this->actingAs($user)->get('/projects/create?type=hire-engineers')->assertRedirect('/projects/create/hire-engineers');
    // Cloud services are ordered in Cloud; anything unknown gets the picker.
    $this->actingAs($user)->get('/projects/create?type=cloud')->assertRedirect(route('cloud.index'));
    $this->actingAs($user)->get('/projects/create?type=')->assertOk()->assertSee('What would you like to build?');
    $this->actingAs($user)->get('/projects/create?type=nonsense')->assertOk()->assertSee('What would you like to build?');
});

test('a dashboard with projects counts them by status and keeps the services to hand', function () {
    $user = userWithRole('user');
    foreach ([ProjectStatus::Submitted, ProjectStatus::InReview, ProjectStatus::InProgress, ProjectStatus::Completed] as $status) {
        Project::factory()->for($user)->create(['status' => $status]);
    }

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['All projects', '4', 'Under review', '2', 'In progress', '1', 'Completed', '1'])
        ->assertSee('More from Xelenic')
        ->assertSee(route('projects.create.type', ProjectType::KioskApp))
        ->assertSee(route('cloud.index'));
});

test('each status has a place on the progress track, except cancelled', function () {
    expect(ProjectStatus::Submitted->step())->toBe(1)
        ->and(ProjectStatus::Completed->step())->toBe(4)
        ->and(ProjectStatus::Cancelled->step())->toBeNull();
});

test('the banner sums up where the projects stand, and updates list what changed', function () {
    $user = userWithRole('user');
    Project::factory()->for($user)->create(['status' => ProjectStatus::Submitted, 'updated_at' => now()->subDays(3)]);
    Project::factory()->for($user)->create(['status' => ProjectStatus::InReview, 'updated_at' => now()->subDays(2)]);
    Project::factory()->for($user)->create(['name' => 'Café ordering kiosk', 'status' => ProjectStatus::InProgress, 'updated_at' => now()->subHour()]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('You have 3 projects with Xelenic: 2 waiting for review and 1 being built.')
        ->assertSee('Step 3 of 4')
        ->assertSeeInOrder(['Latest updates', 'Café ordering kiosk', "We're working on your project.", 'In progress · 1 hour ago'], false);
});

test('Kiosk Studio picks up the latest kiosk design, with its play link', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create(['type' => ProjectType::KioskApp, 'name' => 'Mall kiosk']);
    KioskDesign::attach($project, json_encode([
        'name' => 'Mall arcade', 'width' => 1080, 'height' => 1920,
        'screens' => [['id' => 's1', 'name' => 'Welcome', 'type' => 'page', 'objects' => []], ['id' => 's2', 'name' => 'Game', 'type' => 'page', 'objects' => []]],
    ]));

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Kiosk Studio', 'Latest design', 'Mall arcade', '2 screens · Portrait · Mall kiosk', 'Open design', 'Share to play'])
        ->assertSee('2 screens designed')
        ->assertDontSee('Start a kiosk project');

    $project->fresh()->share();
    $this->actingAs($user)->get('/dashboard')
        ->assertSee($project->fresh()->playUrl())
        ->assertDontSee('Share to play');
});

test('without a kiosk design, Kiosk Studio offers to start one', function () {
    $this->actingAs(userWithRole('user'))->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Welcome to Xelenic', 'Kiosk Studio', 'Start a kiosk project', 'Need a hand?']);
});
