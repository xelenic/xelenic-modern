<?php

use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\User;
use App\Support\KioskDesign;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

/**
 * A client's kiosk project with a saved Kiosk Studio design.
 */
function playableProject(?User $owner = null, string $designName = 'Mall arcade'): Project
{
    $project = Project::factory()->for($owner ?? userWithRole('user'))->create(['type' => ProjectType::KioskApp, 'name' => 'Spring campaign']);
    KioskDesign::attach($project, json_encode([
        'id' => 'p_arcade',
        'name' => $designName,
        'width' => 1080,
        'height' => 1920,
        'startScreen' => 's_welcome',
        'screens' => [['id' => 's_welcome', 'name' => 'Welcome', 'type' => 'page', 'objects' => []]],
    ]));

    return $project->fresh();
}

test('a kiosk project with a design offers a play link', function () {
    $project = playableProject();

    $this->actingAs($project->user)->get("/projects/{$project->id}")
        ->assertOk()
        ->assertSee('Play link')
        ->assertSee('Create play link')
        ->assertDontSee('/play/');
});

test('without a design there is no play link to make', function () {
    $user = userWithRole('user');
    $project = Project::factory()->for($user)->create(['type' => ProjectType::KioskApp]);

    $this->actingAs($user)->get("/projects/{$project->id}")->assertOk()->assertDontSee('Create play link');
    $this->actingAs($user)->post("/projects/{$project->id}/play-link")->assertNotFound();
    expect($project->fresh()->share_token)->toBeNull();
});

test('clients turn on the play link and anyone can open it', function () {
    $project = playableProject();

    $this->actingAs($project->user)->post("/projects/{$project->id}/play-link")
        ->assertRedirect("/projects/{$project->id}")
        ->assertSessionHas('success', 'Your play link is ready. Anyone you send it to can play your kiosk app.');

    $project->refresh();
    expect($project->share_token)->toHaveLength(40)
        ->and($project->shared_at)->not->toBeNull()
        ->and($project->playUrl())->toBe(url("/play/{$project->share_token}"));

    $this->actingAs($project->user)->get("/projects/{$project->id}")
        ->assertSee($project->playUrl())
        ->assertSee('Turn off')
        ->assertSee('New link');

    // A guest, with no account.
    auth()->logout();
    $this->get($project->playUrl())
        ->assertOk()
        ->assertSee('<title>Mall arcade · Play</title>', false)
        ->assertSee('"id":"p_arcade"', false)
        ->assertSee(asset('nignest/js/runtime.js'), false)
        ->assertHeader('Content-Security-Policy', 'sandbox allow-scripts allow-popups allow-popups-to-escape-sandbox')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('a new link replaces the old one, and turning it off stops it', function () {
    $project = playableProject();
    $project->share();
    $old = $project->playUrl();

    $this->actingAs($project->user)->post("/projects/{$project->id}/play-link")
        ->assertSessionHas('success', 'Here is your new play link. The old one no longer works.');
    $new = $project->fresh()->playUrl();

    expect($new)->not->toBe($old);
    $this->get($old)->assertNotFound();
    $this->get($new)->assertOk();

    $this->actingAs($project->user)->delete("/projects/{$project->id}/play-link")
        ->assertRedirect("/projects/{$project->id}")
        ->assertSessionHas('success', 'The play link is off. Anyone who has it can no longer play.');

    expect($project->fresh())->share_token->toBeNull()->shared_at->toBeNull();
    $this->get($new)->assertNotFound();
});

test('only the project\'s owner can share it', function () {
    $project = playableProject();
    $someoneElse = userWithRole('user');

    $this->actingAs($someoneElse)->post("/projects/{$project->id}/play-link")->assertForbidden();
    $this->actingAs($someoneElse)->delete("/projects/{$project->id}/play-link")->assertForbidden();
    auth()->logout();
    $this->post("/projects/{$project->id}/play-link")->assertRedirect('/login');

    expect($project->fresh()->share_token)->toBeNull();
});

test('play links that don\'t exist, or whose design is gone, are not found', function () {
    $project = playableProject();
    $project->share();

    $this->get('/play/'.str_repeat('a', 40))->assertNotFound();
    $this->get('/play/short')->assertNotFound();

    Storage::disk('local')->delete("kiosk-designs/{$project->id}.json");
    $this->get($project->playUrl())->assertNotFound();
});

test('a design\'s text can\'t break out of the play page\'s script', function () {
    $project = playableProject(designName: '</script><script>alert(1)</script>');
    $project->share();

    $this->get($project->playUrl())
        ->assertOk()
        ->assertDontSee('</script><script>alert(1)', false)
        ->assertSee('&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt; · Play', false);
});

test('the team can play a shared design from the admin panel', function () {
    $project = playableProject();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get("/admin/projects/{$project->id}?tab=design")->assertOk()->assertDontSee('/play/');

    $project->share();
    $this->actingAs($admin)->get("/admin/projects/{$project->id}?tab=design")
        ->assertOk()
        ->assertSee($project->playUrl())
        ->assertDontSee('Create play link');
});
