<?php

use App\Enums\KioskMachine;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Arr;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Which step of the kiosk form a page opens on (its [data-step-panel] with
 * data-active), or null if none is.
 */
function openStep(string $html): ?int
{
    return preg_match('/data-step-panel="(\d)"\s+data-active/', $html, $matches) ? (int) $matches[1] : null;
}

/**
 * The kiosk settings panel as the form posts it (switches as "0"/"1").
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function kioskSettingsInput(array $overrides = []): array
{
    return [
        'screen_size' => '27',
        'orientation' => 'portrait',
        'resolution' => '1920x1080',
        'webgl' => '0',
        'game_board' => '0',
        'leaderboard' => '0',
        'payments' => '1',
        'payment_methods' => ['Card (chip & tap)'],
        ...$overrides,
    ];
}

test('the create page offers every kind of project', function () {
    $response = $this->actingAs(userWithRole('user'))->get('/projects/create')->assertOk();

    foreach (ProjectType::cases() as $type) {
        $response->assertSee($type->label())->assertSee("/projects/create/{$type->value}")->assertSee($type->highlights());
    }

    $response->assertSee('Choose from '.count(KioskMachine::cases()).' kiosk machines');
});

test('every kind of project has a working form', function () {
    $user = userWithRole('user');

    foreach (ProjectType::cases() as $type) {
        // Kiosk projects show their form once a machine is picked.
        $query = $type === ProjectType::KioskApp ? '?machine='.KioskMachine::SelfServiceTower->value : '';
        $response = $this->actingAs($user)->get("/projects/create/{$type->value}{$query}")->assertOk();

        foreach ($type->questions() as $question) {
            $response->assertSee($question['label']);
        }
    }

    $this->actingAs($user)->get('/projects/create/not-a-type')->assertNotFound();
});

test('kiosk projects start by picking a machine', function () {
    $user = userWithRole('user');

    $picker = $this->actingAs($user)->get('/projects/create/kiosk-app')->assertOk()->assertSee('Which kiosk machine do you need?');
    foreach (KioskMachine::cases() as $machine) {
        $picker->assertSee($machine->label())->assertSee($machine->image());
        expect(public_path("kiosk/flat/{$machine->value}.svg"))->toBeFile();
    }

    $this->actingAs($user)->get('/projects/create/kiosk-app?machine=not-a-machine')->assertOk()->assertSee('Which kiosk machine do you need?');

    $this->actingAs($user)->get('/projects/create/kiosk-app?machine=ticketing')
        ->assertOk()
        ->assertSee('Ticketing Kiosk with Keyboard')
        ->assertSee('name="details[machine]" value="ticketing"', false);

    $this->actingAs($user)->get('/projects/create/kiosk-app?skip=1')
        ->assertOk()
        ->assertSee('Not chosen yet')
        ->assertDontSee('name="details[machine]"', false);
});

test('only the kiosk machines on offer are accepted', function () {
    $this->actingAs(userWithRole('user'))->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'A kiosk for our lobby.',
        'details' => ['machine' => 'toaster'],
    ])->assertSessionHasErrors('details.machine');

    expect(Project::count())->toBe(0);
});

test('users can submit a project with its extra details', function () {
    $user = userWithRole('user');

    $response = $this->actingAs($user)->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Café kiosks',
        'description' => 'Self-ordering kiosks for our three cafés.',
        'budget' => '$15,000–$50,000',
        'timeline' => '1–3 months',
        'details' => [
            'machine' => 'ordering-billing',
            'kiosk_count' => '6',
            'features' => ['Self-ordering', 'AI assistant'],
            'not_a_question' => 'ignored',
        ],
        'settings' => kioskSettingsInput(),
    ]);

    $project = Project::sole();

    $response->assertRedirect("/projects/{$project->id}");
    expect($project)
        ->user_id->toBe($user->id)
        ->type->toBe(ProjectType::KioskApp)
        ->status->toBe(ProjectStatus::Submitted)
        ->and(Arr::except($project->details, 'settings'))->toBe(['kiosk_count' => 6, 'features' => ['Self-ordering', 'AI assistant'], 'machine' => 'ordering-billing'])
        ->and($project->kioskMachine())->toBe(KioskMachine::OrderingBilling);

    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertOk()
        ->assertSee('Café kiosks')
        ->assertSee('Self-Ordering &amp; Billing Kiosk', false)
        ->assertSee(KioskMachine::OrderingBilling->image())
        ->assertSee('Self-ordering, AI assistant');
});

test('the kiosk form starts from the chosen machine’s screen', function () {
    $user = userWithRole('user');

    $response = $this->actingAs($user)->get('/projects/create/kiosk-app?machine=portrait-55')
        ->assertOk()
        // Step 3 has the project and screen; step 4 the budget, features and questions.
        ->assertSeeInOrder([
            'data-step-panel="3"',
            'Project name', 'Tell us about it', 'Screen size', 'Resolution', 'Orientation',
            'Continue to budget &amp; features',
            'data-step-panel="4"',
            'Budget', 'Timeline', 'WebGL rendering', 'Game board', 'Payment integration', 'Extras',
            'A few more details', 'How many kiosks do you need?',
            'Submit project',
        ], false);

    expect(openStep($response->getContent()))->toBe(3);

    $response
        ->assertSee('<option value="55" selected>', false)
        ->assertSee('value="portrait" class="peer sr-only" checked', false)
        ->assertSee('<span data-preview-diagonal>55″</span>', false)
        ->assertSee('<span data-preview-dimensions>68.5 × 121.8</span>', false);

    $this->actingAs($user)->get('/projects/create/kiosk-app?machine=wide-touch-table')
        ->assertSee('value="landscape" class="peer sr-only" checked', false)
        ->assertSee('<span data-preview-dimensions>121.8 × 68.5</span>', false);

    // Other types keep their one-page form.
    $this->actingAs($user)->get('/projects/create/mobile-app')
        ->assertOk()
        ->assertDontSee('data-step-panel', false)
        ->assertSeeInOrder(['Project name', 'Budget', 'Timeline', 'A few more details', 'Submit project']);
});

test('a failed kiosk submit reopens the step with the problem', function () {
    $user = userWithRole('user');
    $form = '/projects/create/kiosk-app?machine=ticketing';

    // A bad budget lives on step 4…
    $budgetProblem = $this->actingAs($user)->from($form)->followingRedirects()->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'A kiosk for our lobby.',
        'budget' => 'A million dollars',
        'settings' => kioskSettingsInput(),
    ])->assertOk()->assertSee('Lobby kiosk');

    expect(openStep($budgetProblem->getContent()))->toBe(4);

    // …while a missing name sends them back to step 3.
    $nameProblem = $this->actingAs($user)->from($form)->followingRedirects()->post('/projects', [
        'type' => 'kiosk-app',
        'description' => 'A kiosk for our lobby.',
        'budget' => 'A million dollars',
        'settings' => kioskSettingsInput(),
    ])->assertOk();

    expect(openStep($nameProblem->getContent()))->toBe(3);
});

test('kiosk settings are saved, dropping choices under switches that are off', function () {
    $user = userWithRole('user');

    $this->actingAs($user)->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Mall games',
        'description' => 'Interactive games in our mall.',
        'details' => ['machine' => 'advertising'],
        'settings' => kioskSettingsInput([
            'screen_size' => '43',
            'resolution' => '3840x2160',
            'webgl' => '1',
            'game_board' => '1',
            'games' => ['Quiz', 'Spin the wheel'],
            'leaderboard' => '1',
            'payments' => '0',
            'payment_methods' => ['Cash'],
            'add_ons' => ['Camera'],
        ]),
    ])->assertRedirect();

    $project = Project::sole();

    expect($project->kioskSettings())->toBe([
        'screen_size' => '43',
        'orientation' => 'portrait',
        'resolution' => '3840x2160',
        'webgl' => true,
        'game_board' => true,
        'games' => ['Quiz', 'Spin the wheel'],
        'leaderboard' => true,
        'payments' => false,
        'payment_methods' => [],
        'add_ons' => ['Camera'],
    ]);

    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertOk()
        ->assertSee('Kiosk settings')
        ->assertSee('43″ · Portrait · 53.5 × 95.2 cm')
        ->assertSee('4K UHD · 2160 × 3840 px · 102 PPI')
        ->assertSee('Quiz, Spin the wheel · with leaderboard');
});

test('kiosk settings only accept the options on offer', function () {
    $this->actingAs(userWithRole('user'))->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'A kiosk for our lobby.',
        'settings' => kioskSettingsInput([
            'screen_size' => '100',
            'resolution' => '8k',
            'games' => ['Chess'],
        ]),
    ])->assertSessionHasErrors(['settings.screen_size', 'settings.resolution', 'settings.games.0']);

    $this->actingAs(userWithRole('user'))->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'A kiosk for our lobby.',
    ])->assertSessionHasErrors('settings');

    expect(Project::count())->toBe(0);
});

test('projects need a name and description and only accept the offered answers', function () {
    $this->actingAs(userWithRole('user'))->post('/projects', [
        'type' => 'mobile-app',
        'budget' => 'A million dollars',
        'details' => ['platforms' => ['Windows Phone']],
    ])->assertSessionHasErrors(['name', 'description', 'budget', 'details.platforms.0']);

    expect(Project::count())->toBe(0);
});

test('users only see their own projects', function () {
    $user = userWithRole('user');
    $mine = Project::factory()->for($user)->create(['name' => 'My project']);
    $theirs = Project::factory()->create(['name' => 'Someone else’s project']);

    $this->actingAs($user)->get('/projects')->assertOk()->assertSee('My project')->assertDontSee('Someone else’s project');
    $this->actingAs($user)->get("/projects/{$mine->id}")->assertOk()->assertDontSee('Update status');
    $this->actingAs($user)->get("/projects/{$theirs->id}")->assertForbidden();
});

test('the client panel only shows a manager their own projects', function () {
    $manager = userWithRole('manager');
    $project = Project::factory()->create(['name' => 'Client project']);

    $this->actingAs($manager)->get('/projects')->assertOk()->assertDontSee('Client project');
    $this->actingAs($manager)->get("/projects/{$project->id}")->assertForbidden();
});

test('clients cannot change the status of their own project', function () {
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)->patch("/admin/projects/{$project->id}/status", ['status' => 'completed'])->assertForbidden();

    expect($project->fresh()->status)->toBe(ProjectStatus::Submitted);
});

test('clients can cancel a project until work starts', function () {
    $user = userWithRole('user');
    $open = Project::factory()->for($user)->create();
    $started = Project::factory()->for($user)->status(ProjectStatus::InProgress)->create();

    $this->actingAs($user)->post("/projects/{$open->id}/cancel")->assertRedirect("/projects/{$open->id}");
    $this->actingAs($user)->post("/projects/{$started->id}/cancel")->assertForbidden();

    expect($open->fresh()->status)->toBe(ProjectStatus::Cancelled)
        ->and($started->fresh()->status)->toBe(ProjectStatus::InProgress);
});

test('the dashboard lists the user’s latest projects', function () {
    $user = userWithRole('user');
    Project::factory()->for($user)->create(['name' => 'Spring campaign']);

    $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Your projects')->assertSee('Spring campaign');
});
