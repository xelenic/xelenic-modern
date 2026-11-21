<?php

use App\Enums\KioskMachine;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Support\KioskDesign;
use App\Support\KioskSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

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

        // The kiosk form asks about the screen and a design instead.
        if ($type === ProjectType::KioskApp) {
            continue;
        }

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
        // Step 3 is the project and screen; Continue goes straight into Kiosk
        // Studio, whose menu bar sends the project. No budget or features to fill in.
        ->assertSeeInOrder([
            'Project name', 'Tell us about it', 'Screen size', 'Resolution', 'Orientation',
            'data-studio-open', 'Continue to design',
            'data-studio-overlay', 'data-studio-action="Submit project"', 'data-studio-back="Back to the form"', 'data-studio-skip="Skip design &amp; submit project"',
        ], false)
        ->assertDontSee('Budget')
        ->assertDontSee('WebGL rendering');

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
        ->assertDontSee('data-studio-open', false)
        ->assertSeeInOrder(['Project name', 'Budget', 'Timeline', 'A few more details', 'Submit project']);
});

test('a failed kiosk submit shows what went wrong on the form', function () {
    $user = userWithRole('user');
    $form = '/projects/create/kiosk-app?machine=ticketing';

    // The design's problem is above the Continue button, with what was typed kept…
    $this->actingAs($user)->from($form)->followingRedirects()->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'A kiosk for our lobby.',
        'settings' => kioskSettingsInput(),
        'kiosk_design' => 'not a design',
    ])->assertOk()
        ->assertSee('Lobby kiosk')
        ->assertSeeInOrder(['The kiosk app design could not be read.', 'Continue to design']);

    // …and a missing name is under its field.
    $this->actingAs($user)->from($form)->followingRedirects()->post('/projects', [
        'type' => 'kiosk-app',
        'description' => 'A kiosk for our lobby.',
        'settings' => kioskSettingsInput(),
    ])->assertOk()->assertSeeInOrder(['Project name', 'The name field is required.']);
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
        ->assertSee('43″ · Portrait')
        ->assertSee('4K UHD · 2160 × 3840')
        ->assertSee('<span data-preview-dimensions>53.5 × 95.2</span>', false)
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

/**
 * A Kiosk Studio project as the studio hands it back.
 *
 * @param  array<string, mixed>  $overrides
 */
function studioDesign(array $overrides = []): string
{
    return json_encode([
        'id' => 'p_lobby',
        'name' => 'Lobby check-in',
        'width' => 1920,
        'height' => 1080,
        'startScreen' => 's_welcome',
        'vars' => ['score' => 0],
        'assets' => [],
        'screens' => [
            ['id' => 's_welcome', 'name' => 'Welcome', 'type' => 'page', 'objects' => []],
            ['id' => 's_check_in', 'name' => 'Check in', 'type' => 'page', 'objects' => []],
        ],
        ...$overrides,
    ]);
}

test('the kiosk form opens Kiosk Studio, the kiosk engine, for an optional design', function () {
    $this->actingAs(userWithRole('user'))->get('/projects/create/kiosk-app?machine=ticketing')
        ->assertOk()
        ->assertSeeInOrder([
            'data-studio-url="'.asset('nignest/index.html').'?embed=1"',
            'name="kiosk_design"',
            'Continue to design',
            'data-studio-frame',
        ], false);

    // The studio's add-on that the form talks to loads before the studio starts.
    $studio = file_get_contents(public_path('nignest/index.html'));
    expect(strpos($studio, 'js/embed.js'))->toBeLessThan(strpos($studio, 'js/editor-main.js'))
        ->and(public_path('nignest/js/embed.js'))->toBeFile();
});

test('a design from Kiosk Studio is kept with the kiosk project', function () {
    Storage::fake('local');
    $user = userWithRole('user');

    $this->actingAs($user)->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'Check-in for our lobby.',
        'settings' => kioskSettingsInput(['orientation' => 'landscape']),
        'kiosk_design' => studioDesign(),
    ])->assertSessionHasNoErrors();

    $project = Project::sole();
    expect($project->kioskDesign())->toBe(['name' => 'Lobby check-in', 'screens' => 2, 'orientation' => 'landscape']);
    Storage::disk('local')->assertExists("kiosk-designs/{$project->id}.json");

    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertSee('Kiosk app design')
        ->assertSee('2 screens · Landscape')
        ->assertSee('Edit in Kiosk Studio');

    $download = $this->actingAs($user)->get("/projects/{$project->id}/kiosk-design")->assertOk();
    expect($download->streamedContent())->toBe(studioDesign())
        ->and($download->headers->get('Content-Disposition'))->toContain('lobby-kiosk.kiosk.json');

    // Nobody else's, but the team can open it from the admin panel.
    $this->actingAs(userWithRole('user'))->get("/projects/{$project->id}/kiosk-design")->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get("/admin/projects/{$project->id}")
        ->assertSee('Kiosk app design')
        ->assertSee(urlencode(route('admin.projects.kiosk-design', $project)), false);
    $this->actingAs(userWithRole('manager'))->get("/admin/projects/{$project->id}/kiosk-design")->assertOk();
});

test('a kiosk design has to be a Kiosk Studio project of a sensible size', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $post = fn (string $design) => $this->actingAs($user)->from('/projects/create/kiosk-app?skip=1')->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'Check-in for our lobby.',
        'settings' => kioskSettingsInput(),
        'kiosk_design' => $design,
    ]);

    $post('{"hello": "world"}')->assertSessionHasErrors('kiosk_design');
    $post(studioDesign(['screens' => []]))->assertSessionHasErrors('kiosk_design');
    $post(studioDesign(['assets' => [['src' => str_repeat('a', 5_000_000)]]]))
        ->assertSessionHasErrors(['kiosk_design' => 'The kiosk app design is too large to attach (over 5 MB). Use smaller pictures in Kiosk Studio and try again.']);

    expect(Project::count())->toBe(0);

    // Back on the form, the design that was sent is kept for the studio.
    $this->actingAs($user)->from('/projects/create/kiosk-app?skip=1')->followingRedirects()->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'Check-in for our lobby.',
        'settings' => kioskSettingsInput(),
        'kiosk_design' => '{"screens": []}',
    ])->assertSee('name="kiosk_design" value="{&quot;screens&quot;: []}"', false);
});

test('a kiosk project without a design has nothing to download', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create(['type' => ProjectType::KioskApp]);

    $this->actingAs($user)->get("/projects/{$project->id}")->assertOk()->assertSee('No design yet')->assertDontSee('Download');
    $this->actingAs($user)->get("/projects/{$project->id}/kiosk-design")->assertNotFound();
});

test('clients can change the Kiosk Studio design on their kiosk project', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create([
        'type' => ProjectType::KioskApp,
        'details' => ['settings' => KioskSettings::fromInput(kioskSettingsInput(['orientation' => 'landscape']))],
    ]);

    // No design yet: the page offers to make one, at the kiosk's orientation.
    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertSee('No design yet')
        ->assertSee('Design in Kiosk Studio')
        ->assertSee('action="'.route('projects.kiosk-design.update', $project).'"', false)
        ->assertSee('data-studio-orientation="landscape"', false)
        ->assertDontSee('data-studio-source', false);

    $this->actingAs($user)->put("/projects/{$project->id}/kiosk-design", ['kiosk_design' => studioDesign()])
        ->assertRedirect("/projects/{$project->id}?tab=design")
        ->assertSessionHas('success', 'Your kiosk app design was saved.');

    expect($project->fresh()->kioskDesign())->toBe(['name' => 'Lobby check-in', 'screens' => 2, 'orientation' => 'landscape']);

    // From then on the studio opens with the saved design, and saving replaces it.
    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertSee('Edit in Kiosk Studio')
        ->assertSee('data-studio-source="'.route('projects.kiosk-design', $project).'"', false);

    $changed = studioDesign(['name' => 'Lobby check-in v2', 'screens' => [['id' => 's_welcome', 'objects' => []]]]);
    $this->actingAs($user)->put("/projects/{$project->id}/kiosk-design", ['kiosk_design' => $changed])->assertRedirect();

    expect($project->fresh()->kioskDesign())->toBe(['name' => 'Lobby check-in v2', 'screens' => 1, 'orientation' => 'landscape'])
        ->and($this->actingAs($user)->get("/projects/{$project->id}/kiosk-design")->streamedContent())->toBe($changed);
});

test('only the client can change a kiosk design, while the project is open', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create(['type' => ProjectType::KioskApp]);
    $save = fn ($as, Project $project, array $data) => $this->actingAs($as)->put("/projects/{$project->id}/kiosk-design", $data);

    $save(userWithRole('user'), $project, ['kiosk_design' => studioDesign()])->assertForbidden();
    $save($user, Project::factory()->for($user)->create(['type' => ProjectType::MobileApp]), ['kiosk_design' => studioDesign()])->assertForbidden();

    foreach ([ProjectStatus::Completed, ProjectStatus::Cancelled] as $status) {
        $project->update(['status' => $status]);
        $save($user, $project, ['kiosk_design' => studioDesign()])->assertForbidden();
        $this->actingAs($user)->get("/projects/{$project->id}")->assertDontSee('data-kiosk-studio', false);
    }

    // Work in progress can still change; a design that isn't one is turned away.
    $project->update(['status' => ProjectStatus::InProgress]);
    $save($user, $project, ['kiosk_design' => '{"screens": []}'])->assertSessionHasErrors('kiosk_design');
    $save($user, $project, [])->assertSessionHasErrors('kiosk_design');

    expect($project->fresh()->kioskDesign())->toBeNull();
    Storage::disk('local')->assertMissing("kiosk-designs/{$project->id}.json");
});

test('a design whose file has gone missing can be designed again', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create([
        'type' => ProjectType::KioskApp,
        'details' => ['design' => ['name' => 'Untitled Kiosk', 'screens' => 1, 'orientation' => 'portrait']],
    ]);

    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertSee('The design file is missing. Design it again in Kiosk Studio.')
        ->assertSee('Design in Kiosk Studio')
        ->assertDontSee('data-studio-source', false)
        ->assertDontSee('Download');

    $this->actingAs(userWithRole('manager'))->get("/admin/projects/{$project->id}")
        ->assertSee('The design file is missing.')
        ->assertDontSee('Open in Kiosk Studio');

    $this->actingAs($user)->put("/projects/{$project->id}/kiosk-design", ['kiosk_design' => studioDesign()])->assertRedirect();

    $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertDontSee('The design file is missing')
        ->assertSee('Edit in Kiosk Studio');
});

/**
 * Which tab a project's page opens on (the one marked aria-selected).
 */
function openTab(string $html): ?string
{
    return preg_match('/id="tab-(\w+)"[^>]*?aria-selected="true"/s', $html, $matches) ? $matches[1] : null;
}

test('a kiosk project’s page is set out in tabs', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create([
        'type' => ProjectType::KioskApp,
        'budget' => null,
        'timeline' => null,
        'details' => ['machine' => 'ticketing', 'settings' => KioskSettings::fromInput(kioskSettingsInput())],
    ]);

    $page = $this->actingAs($user)->get("/projects/{$project->id}")
        ->assertOk()
        ->assertSeeInOrder(['role="tablist"', 'Overview', 'Kiosk machine', 'Screen', 'App design', 'At a glance'], false)
        ->assertDontSee('aria-label="Progress"', false)
        ->assertDontSee('id="tab-details"', false);
    expect(openTab($page->getContent()))->toBe('overview');

    // Each tab has its own address; anything else is the overview.
    expect(openTab($this->actingAs($user)->get("/projects/{$project->id}?tab=screen")->getContent()))->toBe('screen')
        ->and(openTab($this->actingAs($user)->get("/projects/{$project->id}?tab=nonsense")->getContent()))->toBe('overview');

    // The team sees the same tabs.
    $this->actingAs(userWithRole('manager'))->get("/admin/projects/{$project->id}?tab=machine")
        ->assertSee('Ticketing', false)
        ->assertSee('id="tab-machine"', false);

    // Other kinds of project have an overview, and details once there are some.
    $app = Project::factory()->for($user)->create(['type' => ProjectType::MobileApp, 'budget' => null, 'timeline' => null, 'details' => []]);
    $this->actingAs($user)->get("/projects/{$app->id}")
        ->assertSee('aria-label="Progress"', false)
        ->assertSee('id="tab-overview"', false)
        ->assertDontSee('id="tab-machine"', false)
        ->assertDontSee('id="tab-details"', false);

    $app->update(['budget' => Project::BUDGETS[0]]);
    $this->actingAs($user)->get("/projects/{$app->id}")->assertSee('id="tab-details"', false);
});

/**
 * A Kiosk Studio design with a sign-up form on its second screen.
 */
function studioDesignWithForm(): string
{
    return studioDesign(['screens' => [
        ['id' => 's_welcome', 'name' => 'Welcome', 'type' => 'page', 'objects' => []],
        ['id' => 's_form', 'name' => 'Sign up', 'type' => 'page', 'objects' => [
            ['id' => 'o_name', 'type' => 'input', 'field' => 'name', 'placeholder' => 'Your name', 'inputType' => 'text', 'required' => true],
            ['id' => 'o_email', 'type' => 'input', 'field' => 'email', 'placeholder' => 'Email address', 'inputType' => 'email', 'required' => false],
            ['id' => 'o_send', 'type' => 'button', 'text' => 'Send'],
        ]],
    ]]);
}

test('a kiosk project shows its design’s data set and what its forms collected', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create(['type' => ProjectType::KioskApp]);
    $entries = [
        ['time' => '2026-11-20T10:00:00.000Z', 'screen' => 'Sign up', 'score' => 0, 'name' => 'Asha', 'email' => 'asha@example.test'],
        ['time' => '2026-11-21T09:30:00.000Z', 'screen' => 'Sign up', 'score' => 120, 'name' => '=HYPERLINK("http://example.test")', 'email' => ''],
    ];

    $this->actingAs($user)->put("/projects/{$project->id}/kiosk-design", [
        'kiosk_design' => studioDesignWithForm(),
        'kiosk_data' => json_encode($entries),
    ])->assertRedirect();

    $page = $this->actingAs($user)->get("/projects/{$project->id}?tab=data")
        ->assertSee('2 fields · 2 entries')
        ->assertSeeInOrder(['Data set', 'name', 'Your name', 'Text', 'Yes', 'Sign up', 'email', 'Email address', 'Email', 'No'])
        // Newest first.
        ->assertSeeInOrder(['Collected data', 'Sent', '21 Nov 2026, 09:30', '20 Nov 2026, 10:00', 'Asha']);
    expect(openTab($page->getContent()))->toBe('data');

    // Each save brings the browser's whole list: entries already kept aren't added twice.
    $this->actingAs($user)->put("/projects/{$project->id}/kiosk-design", [
        'kiosk_design' => studioDesignWithForm(),
        'kiosk_data' => json_encode([...$entries, ['time' => '2026-11-22T08:00:00.000Z', 'name' => 'Ben']]),
    ])->assertRedirect();
    expect(KioskDesign::entries($project))->toHaveCount(3);

    $csv = $this->actingAs($user)->get("/projects/{$project->id}/kiosk-data")->assertOk()->streamedContent();
    expect($csv)->toStartWith("time,screen,score,name,email\n")
        ->toContain("'=HYPERLINK") // stays text in a spreadsheet
        ->toContain('Ben');

    $this->actingAs(userWithRole('user'))->get("/projects/{$project->id}/kiosk-data")->assertForbidden();
    $this->actingAs(userWithRole('manager'))->get("/admin/projects/{$project->id}/kiosk-data")->assertOk();
    $this->actingAs(userWithRole('manager'))->get("/admin/projects/{$project->id}?tab=data")
        ->assertSee(route('admin.projects.kiosk-data', $project), false);
});

test('entries sent with a new kiosk project are kept', function () {
    Storage::fake('local');

    $this->actingAs(userWithRole('user'))->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'Check-in for our lobby.',
        'settings' => kioskSettingsInput(),
        'kiosk_design' => studioDesignWithForm(),
        'kiosk_data' => json_encode([['time' => '2026-11-20T10:00:00.000Z', 'name' => 'Asha']]),
    ])->assertSessionHasNoErrors();

    expect(KioskDesign::entries(Project::sole()))->toBe([['time' => '2026-11-20T10:00:00.000Z', 'name' => 'Asha']]);
});

test('collected data has to be a list of form entries', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create(['type' => ProjectType::KioskApp]);

    foreach (['not json', '{"name": "Asha"}', '[["a", "b"]]', '[{"name": {"nested": true}}]'] as $bad) {
        $this->actingAs($user)->put("/projects/{$project->id}/kiosk-design", ['kiosk_design' => studioDesign(), 'kiosk_data' => $bad])
            ->assertSessionHasErrors('kiosk_data');
    }

    expect(KioskDesign::entries($project))->toBe([]);
    $this->actingAs($user)->get("/projects/{$project->id}/kiosk-data")->assertNotFound();
});

test('Kiosk Studio saves a project’s design in the background and stays open', function () {
    Storage::fake('local');
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create(['type' => ProjectType::KioskApp]);

    $this->actingAs($user)->get("/projects/{$project->id}")->assertSee('data-kiosk-studio data-studio-stay', false);

    // Answered with what the page needs, not sent anywhere.
    $this->actingAs($user)->putJson("/projects/{$project->id}/kiosk-design", [
        'kiosk_design' => studioDesignWithForm(),
        'kiosk_data' => json_encode([['time' => '2026-11-22T08:00:00.000Z', 'name' => 'Ben']]),
    ])
        ->assertOk()
        ->assertExactJson([
            'message' => 'Design saved.',
            'design' => ['name' => 'Lobby check-in', 'screens' => 2, 'orientation' => 'landscape'],
            'entries' => 1,
        ]);

    expect($project->fresh()->kioskDesign()['screens'])->toBe(2);

    // A problem comes back as one the studio can show.
    $this->actingAs($user)->putJson("/projects/{$project->id}/kiosk-design", ['kiosk_design' => '{"screens": []}'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['kiosk_design' => 'could not be read']);
});
