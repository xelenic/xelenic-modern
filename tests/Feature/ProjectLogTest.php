<?php

use App\Enums\ActivityType;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Support\KioskDesign;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

/**
 * A Kiosk Studio design, as the studio saves it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function logDesign(array $overrides = []): array
{
    return [
        'name' => 'Mall arcade',
        'width' => 1080,
        'height' => 1920,
        'startScreen' => 's_welcome',
        'assets' => [['id' => 'a_logo', 'name' => 'Logo', 'src' => 'data:image/png;base64,AA==']],
        'screens' => [
            ['id' => 's_welcome', 'name' => 'Welcome', 'type' => 'page', 'fx' => 40, 'fy' => 40, 'objects' => [['id' => 'o1', 'type' => 'text', 'text' => 'Hello']]],
            ['id' => 's_menu', 'name' => 'Menu', 'type' => 'page', 'fx' => 340, 'fy' => 40, 'objects' => []],
            ['id' => 's_old', 'name' => 'Old promo', 'type' => 'page', 'fx' => 640, 'fy' => 40, 'objects' => []],
        ],
        ...$overrides,
    ];
}

function kioskProjectWithDesign(): Project
{
    $project = Project::factory()->for(userWithRole('user'))->create(['type' => ProjectType::KioskApp, 'name' => 'Spring campaign']);
    KioskDesign::attach($project, json_encode(logDesign()));

    return $project->fresh();
}

test('a first design is described by its name, size and orientation', function () {
    expect(KioskDesign::changes(null, logDesign()))->toBe([
        ['tone' => 'info', 'label' => 'Kiosk Studio design', 'items' => ['Mall arcade', '3 screens', 'Portrait']],
    ]);
});

test('a save lists what changed in the design', function () {
    $new = logDesign([
        'name' => 'Mall arcade v2',
        'startScreen' => 's_menu',
        'assets' => [['id' => 'a_bg', 'name' => 'Background']],
        'gameTemplates' => [['manifest' => ['id' => 'balloon-pop', 'name' => 'Balloon Pop']]],
        'screens' => [
            // Text changed.
            ['id' => 's_welcome', 'name' => 'Welcome', 'type' => 'page', 'fx' => 40, 'fy' => 40, 'objects' => [['id' => 'o1', 'type' => 'text', 'text' => 'Hi there']]],
            // Renamed and moved on the flow map, nothing else.
            ['id' => 's_menu', 'name' => 'Choose a game', 'type' => 'page', 'fx' => 360, 'fy' => 80, 'objects' => []],
            ['id' => 's_hoop', 'name' => 'Hoop Shot Game', 'type' => 'game', 'objects' => []],
        ],
    ]);

    expect(KioskDesign::changes(logDesign(), $new))->toBe([
        ['tone' => 'renamed', 'label' => 'Design renamed', 'items' => ['Mall arcade → Mall arcade v2']],
        ['tone' => 'added', 'label' => 'Screens added', 'items' => ['Hoop Shot Game']],
        ['tone' => 'removed', 'label' => 'Screens removed', 'items' => ['Old promo']],
        ['tone' => 'renamed', 'label' => 'Screens renamed', 'items' => ['Menu → Choose a game']],
        ['tone' => 'edited', 'label' => 'Screens changed', 'items' => ['Welcome']],
        ['tone' => 'edited', 'label' => 'Start screen', 'items' => ['Choose a game']],
        ['tone' => 'added', 'label' => 'Games added', 'items' => ['Balloon Pop']],
        ['tone' => 'added', 'label' => 'Pictures added', 'items' => ['Background']],
        ['tone' => 'removed', 'label' => 'Pictures removed', 'items' => ['Logo']],
        ['tone' => 'info', 'label' => 'Flow map', 'items' => ['Screens rearranged']],
    ]);
});

test('turning the design round is logged as its orientation', function () {
    expect(KioskDesign::changes(logDesign(), logDesign(['width' => 1920, 'height' => 1080])))
        ->toBe([['tone' => 'edited', 'label' => 'Orientation', 'items' => ['Portrait → Landscape']]]);
});

test('each Kiosk Studio save goes in the log with what it changed', function () {
    $project = kioskProjectWithDesign();
    $owner = $project->user;
    $new = logDesign(['screens' => [...logDesign()['screens'], ['id' => 's_win', 'name' => 'You win', 'type' => 'page', 'objects' => []]]]);

    $this->actingAs($owner)->putJson("/projects/{$project->id}/kiosk-design", [
        'kiosk_design' => json_encode($new),
        'kiosk_data' => json_encode([['time' => '2026-11-21T10:00:00Z', 'name' => 'Riya'], ['time' => '2026-11-21T10:05:00Z', 'name' => 'Sam']]),
    ])->assertOk();

    $entry = $project->activities()->sole();
    expect($entry)
        ->type->toBe(ActivityType::Design)
        ->summary->toBe('Saved the design in Kiosk Studio')
        ->user_id->toBe($owner->id)
        ->and($entry->changes)->toBe([
            ['tone' => 'added', 'label' => 'Screens added', 'items' => ['You win']],
            ['tone' => 'added', 'label' => 'Form entries', 'items' => ['2 new entries']],
        ]);

    // Saving it again as it is changes nothing, so it isn't logged.
    $this->actingAs($owner)->putJson("/projects/{$project->id}/kiosk-design", ['kiosk_design' => json_encode($new)])->assertOk();
    expect($project->activities()->count())->toBe(1);
});

test('a submitted project starts its log, with its first design', function () {
    $user = userWithRole('user');

    $this->actingAs($user)->post('/projects', [
        'type' => 'kiosk-app',
        'name' => 'Lobby kiosk',
        'description' => 'Check-in for our lobby.',
        'settings' => ['screen_size' => '27', 'orientation' => 'portrait', 'resolution' => '1920x1080', 'webgl' => '0', 'game_board' => '0', 'leaderboard' => '0', 'payments' => '0'],
        'kiosk_design' => json_encode(logDesign()),
    ])->assertSessionHasNoErrors();

    expect(Project::sole()->activities()->sole())
        ->type->toBe(ActivityType::Created)
        ->summary->toBe('Project submitted')
        ->changes->toBe([['tone' => 'info', 'label' => 'Kiosk Studio design', 'items' => ['Mall arcade', '3 screens', 'Portrait']]]);
});

test('status changes, cancelling and the play link are logged too', function () {
    $project = kioskProjectWithDesign();
    $owner = $project->user;
    $admin = userWithRole('admin');

    $this->actingAs($admin)->patch("/admin/projects/{$project->id}/status", ['status' => ProjectStatus::InReview->value]);
    $this->actingAs($admin)->patch("/admin/projects/{$project->id}/status", ['status' => ProjectStatus::InReview->value]);
    $this->actingAs($owner)->post("/projects/{$project->id}/play-link");
    $this->actingAs($owner)->post("/projects/{$project->id}/play-link");
    $this->actingAs($owner)->delete("/projects/{$project->id}/play-link");
    $this->actingAs($owner)->delete("/projects/{$project->id}/play-link");
    $this->actingAs($owner)->post("/projects/{$project->id}/cancel");

    expect($project->activities()->orderBy('id')->get()->map(fn (ProjectActivity $a) => [$a->type, $a->summary, $a->user_id])->all())->toBe([
        [ActivityType::Status, 'Moved the project to In review', $admin->id],
        [ActivityType::PlayLink, 'Turned on the play link', $owner->id],
        [ActivityType::PlayLink, 'Made a new play link', $owner->id],
        [ActivityType::PlayLink, 'Turned off the play link', $owner->id],
        [ActivityType::Status, 'Cancelled the project', $owner->id],
    ]);
    expect($project->activities()->where('summary', 'Moved the project to In review')->sole()->changes)
        ->toBe([['tone' => 'edited', 'label' => 'Status', 'items' => ['Submitted → In review']]]);
});

test('the Log tab shows the timeline, newest first, by day', function () {
    $project = kioskProjectWithDesign();
    $owner = $project->user;
    $admin = userWithRole('admin');
    $project->activities()->create(['type' => ActivityType::Created, 'summary' => 'Project submitted', 'user_id' => $owner->id, 'created_at' => now()->subDays(3)]);
    $project->activities()->create(['type' => ActivityType::Status, 'summary' => 'Moved the project to In review', 'user_id' => $admin->id, 'created_at' => now()->subDay()]);
    $project->log(ActivityType::Design, 'Saved the design in Kiosk Studio', $owner, [['tone' => 'added', 'label' => 'Screens added', 'items' => ['Hoop Shot Game']]]);

    $this->actingAs($owner)->get("/projects/{$project->id}?tab=log")
        ->assertOk()
        ->assertSee('id="tab-log"', false)
        ->assertSeeInOrder([
            'Today', 'Saved the design in Kiosk Studio', 'by You', 'Screens added', 'Hoop Shot Game',
            'Yesterday', 'Moved the project to In review', 'by The Xelenic team',
            now()->subDays(3)->format('l, j F Y'), 'Project submitted',
        ]);

    // The team sees who did what.
    $this->actingAs($admin)->get("/admin/projects/{$project->id}?tab=log")
        ->assertOk()
        ->assertSeeInOrder(['by '.$owner->name.' (client)', 'by '.$admin->name]);
});

test('a project from before the log still shows its submission', function () {
    $project = Project::factory()->for(userWithRole('user'))->create(['type' => ProjectType::SoftwareSolution, 'created_at' => now()->subWeek()]);

    $this->actingAs($project->user)->get("/projects/{$project->id}?tab=log")
        ->assertOk()
        ->assertSeeInOrder([now()->subWeek()->format('l, j F Y'), 'Project submitted', 'by You']);
});
