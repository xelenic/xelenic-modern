<?php

use App\Models\GameTemplate;
use App\Models\GameTemplateReview;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * A game template .zip, as Kiosk Studio exports one.
 *
 * @param  array<string, mixed>  $manifest  merged into a custom game's manifest
 * @param  array<string, string>  $files  more files, by path in the zip
 */
function templateZip(array $manifest = [], array $files = ['game.js' => 'KioskGame.define({ start() {} });'], ?string $folder = null): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'tpl');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $files = ['manifest.json' => json_encode([
        'format' => 'kiosk-game-template',
        'id' => 'balloon-pop',
        'name' => 'Balloon Pop',
        'description' => 'Pop the balloons before they float away.',
        'version' => '1.2.0',
        'author' => 'Riya',
        'icon' => '🎈',
        'color' => '#1d4ed8',
        ...$manifest,
    ])] + $files;
    foreach ($files as $name => $contents) {
        $zip->addFromString(($folder ? "{$folder}/" : '').$name, $contents);
    }
    $zip->close();

    return new UploadedFile($path, 'balloon-pop.zip', 'application/zip', null, true);
}

function staff(): User
{
    return userWithRole('manager');
}

test('the ten built-in games are in the table', function () {
    expect(GameTemplate::ordered()->pluck('slug')->all())->toBe(GameTemplate::BUILTIN)
        ->and(GameTemplate::where('kind', 'builtin')->count())->toBe(10)
        ->and(GameTemplate::where('slug', 'runner')->sole())->name->toBe('Runner')->sort_order->toBe(5)
        ->and(GameTemplate::where('slug', 'memory')->sole())->name->toBe('Memory Match')->sort_order->toBe(6)
        ->and(GameTemplate::where('slug', 'hoop')->sole())->name->toBe('Hoop Shot')->sort_order->toBe(10);
});

test('Kiosk Studio has a game, defaults and settings for every built-in game', function () {
    $studio = collect(['games.js', 'templates.js', 'editor-inspector.js', 'runtime.js'])
        ->mapWithKeys(fn (string $file) => [$file => file_get_contents(public_path("nignest/js/{$file}"))]);

    foreach (GameTemplate::BUILTIN as $game) {
        expect($studio['games.js'])->toMatch("/\\b{$game}: \\w+,/")
            ->and($studio['templates.js'])->toMatch("/^  {$game}: \\{ name: /m")
            ->and($studio['editor-inspector.js'])->toMatch("/^  {$game}: \\[\\['h'/m")
            ->and($studio['runtime.js'])->toContain("'{$game}'");
    }
});

test('Kiosk Studio knows the same built-in games', function () {
    preg_match("/const BUILTIN_KINDS = \[(.*?)\];/", file_get_contents(public_path('nignest/js/editor-packages.js')), $match);

    expect(str_replace(["'", ' '], '', $match[1]))->toBe(implode(',', GameTemplate::BUILTIN));
});

test('signed-in users get the offered templates, in order, as JSON', function () {
    GameTemplate::where('slug', 'slice')->update(['is_active' => false]);
    GameTemplate::where('slug', 'wheel')->update(['sort_order' => 0, 'name' => 'Prize Wheel']);

    $response = $this->actingAs(userWithRole('user'))->getJson('/game-templates')->assertOk();

    expect($response->json('*.id'))->toBe(['wheel', 'quiz', 'catch', 'runner', 'memory', 'whack', 'stack', 'scratch', 'hoop'])
        ->and($response->json('0'))->toMatchArray(['name' => 'Prize Wheel', 'kind' => 'builtin', 'download' => null]);
});

test('guests can\'t read the template list', function () {
    $this->get('/game-templates')->assertRedirect('/login');
    $this->getJson('/game-templates')->assertUnauthorized();
});

test('staff can upload a custom game, which the studio can then download', function () {
    $this->actingAs(staff())
        ->post('/admin/game-templates', [
            'package' => templateZip(files: ['game.js' => 'KioskGame.define({});', 'assets/icon.png' => 'png-bytes'], manifest: ['icon' => 'assets/icon.png']),
            'name' => '',
            'description' => '',
            'sort_order' => 5,
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/game-templates');

    $template = GameTemplate::where('slug', 'balloon-pop')->sole();
    expect($template)
        ->name->toBe('Balloon Pop')
        ->description->toBe('Pop the balloons before they float away.')
        ->kind->toBe('custom')
        ->extends->toBeNull()
        ->version->toBe('1.2.0')
        ->author->toBe('Riya')
        ->icon->toBeNull()
        ->icon_image->toBe('data:image/png;base64,'.base64_encode('png-bytes'))
        ->color->toBe('#1d4ed8')
        ->is_active->toBeTrue();
    Storage::disk('local')->assertExists('game-templates/balloon-pop.zip');

    $user = userWithRole('user');
    $entry = collect($this->actingAs($user)->getJson('/game-templates')->json())->firstWhere('id', 'balloon-pop');
    expect($entry['download'])->toBe(route('game-templates.download', 'balloon-pop'));

    $this->actingAs($user)->get($entry['download'])
        ->assertOk()
        ->assertDownload('balloon-pop-1.2.0.zip');
});

test('a preset needs no code, and the form can rename it', function () {
    $this->actingAs(staff())
        ->post('/admin/game-templates', [
            'package' => templateZip(['id' => 'fruit-catch', 'extends' => 'catch', 'icon' => '🍓'], files: [], folder: 'fruit-catch'),
            'name' => 'Strawberry Catch',
            'sort_order' => 6,
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect(GameTemplate::where('slug', 'fruit-catch')->sole())
        ->name->toBe('Strawberry Catch')
        ->kind->toBe('preset')
        ->extends->toBe('catch')
        ->icon->toBe('🍓');
});

test('a template .zip has to be one the studio can install', function (Closure $package, string $error) {
    GameTemplate::create(['slug' => 'taken', 'name' => 'Taken', 'kind' => 'custom', 'sort_order' => 9]);

    $this->actingAs(staff())
        ->post('/admin/game-templates', ['package' => $package(), 'sort_order' => 5, 'is_active' => '1'])
        ->assertSessionHasErrors(['package' => $error]);

    expect(GameTemplate::count())->toBe(11);
    Storage::disk('local')->assertDirectoryEmpty('game-templates');
})->with([
    'not a zip' => [fn () => UploadedFile::fake()->createWithContent('game.zip', 'just text'), 'That file is not a .zip.'],
    'no manifest' => [function () {
        $path = tempnam(sys_get_temp_dir(), 'tpl');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('game.js', 'KioskGame.define({});');
        $zip->close();

        return new UploadedFile($path, 'game.zip', 'application/zip', null, true);
    }, 'manifest.json was not found at the top of the zip.'],
    'a built-in id' => [fn () => templateZip(['id' => 'quiz']), '"quiz" is the name of a built-in game. Choose another id.'],
    'a bad id' => [fn () => templateZip(['id' => 'Balloon Pop!']), 'manifest.json needs an "id" of 2–41 lowercase letters, numbers, "-" or "_".'],
    'no code' => [fn () => templateZip(files: []), 'game.js was not found in the zip.'],
    'an unknown base game' => [fn () => templateZip(['extends' => 'chess']), '"extends" must be one of: quiz, catch, slice, wheel, runner, memory, whack, stack, scratch, hoop.'],
    'an id in use' => [fn () => templateZip(['id' => 'taken']), 'There\'s already a template with the id "taken". Edit it to upload a new version.'],
    'no package' => [fn () => null, 'Choose the game template .zip to upload.'],
]);

test('only staff who manage projects can change templates', function () {
    $user = userWithRole('user');
    $quiz = GameTemplate::where('slug', 'quiz')->sole();

    $this->actingAs($user)->get('/admin/game-templates')->assertForbidden();
    $this->actingAs($user)->post('/admin/game-templates', ['package' => templateZip(), 'sort_order' => 1])->assertForbidden();
    $this->actingAs($user)->delete("/admin/game-templates/{$quiz->id}")->assertForbidden();

    $this->actingAs(staff())->get('/admin/game-templates')
        ->assertOk()
        ->assertSee('Quiz')
        ->assertSee('Spin Wheel')
        ->assertSee('Built in');
});

test('a new version has to be the same template', function () {
    $this->actingAs(staff())->post('/admin/game-templates', ['package' => templateZip(), 'sort_order' => 5, 'is_active' => '1']);
    $template = GameTemplate::where('slug', 'balloon-pop')->sole();

    $this->actingAs(staff())
        ->put("/admin/game-templates/{$template->id}", ['package' => templateZip(['id' => 'other-game']), 'name' => 'Balloon Pop', 'sort_order' => 5, 'is_active' => '1'])
        ->assertSessionHasErrors(['package' => 'This .zip is the template "other-game", not "balloon-pop". Add it as a new template instead.']);

    $this->actingAs(staff())
        ->put("/admin/game-templates/{$template->id}", ['package' => templateZip(['version' => '2.0.0', 'author' => 'Sam']), 'name' => 'Balloon Party', 'description' => 'New and improved.', 'sort_order' => 7, 'is_active' => '0'])
        ->assertSessionHasNoErrors();

    expect($template->fresh())
        ->name->toBe('Balloon Party')
        ->description->toBe('New and improved.')
        ->version->toBe('2.0.0')
        ->author->toBe('Sam')
        ->sort_order->toBe(7)
        ->is_active->toBeFalse();
});

test('built-in games can be renamed and hidden, but not replaced or removed', function () {
    $wheel = GameTemplate::where('slug', 'wheel')->sole();

    $this->actingAs(staff())
        ->put("/admin/game-templates/{$wheel->id}", ['package' => templateZip(), 'name' => 'Wheel', 'sort_order' => 4, 'is_active' => '1'])
        ->assertSessionHasErrors(['package' => 'Kiosk Studio\'s own games can\'t be replaced.']);

    $this->actingAs(staff())
        ->put("/admin/game-templates/{$wheel->id}", ['name' => 'Prize Wheel', 'sort_order' => 4, 'is_active' => '0'])
        ->assertSessionHasNoErrors();
    expect($wheel->fresh())->name->toBe('Prize Wheel')->is_active->toBeFalse()->kind->toBe('builtin');

    $this->actingAs(staff())->delete("/admin/game-templates/{$wheel->id}")->assertForbidden();
    $this->actingAs(staff())->get('/game-templates/wheel/download')->assertNotFound();
    expect($wheel->fresh())->not->toBeNull();
});

test('a hidden template is only for staff, and a removed one takes its .zip with it', function () {
    $this->actingAs(staff())->post('/admin/game-templates', ['package' => templateZip(), 'sort_order' => 5, 'is_active' => '0']);
    $template = GameTemplate::where('slug', 'balloon-pop')->sole();

    $this->actingAs(userWithRole('user'))->getJson('/game-templates')->assertJsonMissing(['id' => 'balloon-pop']);
    $this->actingAs(userWithRole('user'))->get('/game-templates/balloon-pop/download')->assertNotFound();
    $this->actingAs(staff())->get('/game-templates/balloon-pop/download')->assertOk();

    $this->actingAs(staff())->delete("/admin/game-templates/{$template->id}")->assertRedirect('/admin/game-templates');

    expect(GameTemplate::where('slug', 'balloon-pop')->exists())->toBeFalse();
    Storage::disk('local')->assertMissing('game-templates/balloon-pop.zip');
    $this->actingAs(staff())->get('/game-templates/balloon-pop/download')->assertNotFound();
});

test('the list has each game\'s rating and review count', function () {
    $runner = GameTemplate::where('slug', 'runner')->sole();
    GameTemplateReview::factory()->for($runner)->create(['rating' => 5]);
    GameTemplateReview::factory()->for($runner)->create(['rating' => 4]);

    $games = collect($this->actingAs(userWithRole('user'))->getJson('/game-templates')->json())->keyBy('id');

    expect($games['runner'])->toMatchArray(['rating' => 4.5, 'reviews_count' => 2, 'thumbnail' => null, 'screenshots_count' => 0])
        ->and($games['quiz'])->toMatchArray(['rating' => 0, 'reviews_count' => 0]);
});

test('a game\'s store page has its write-up, pictures and reviews', function () {
    $sam = userWithRole('user');
    $hoop = GameTemplate::where('slug', 'hoop')->sole();
    $hoop->update([
        'details' => "## Great for events\n\nPlayers **love** it.\n\n<script>alert(1)</script>",
        'thumbnail_path' => 'game-templates/hoop/cover.jpg',
        'screenshots' => ['game-templates/hoop/one.png', 'game-templates/hoop/two.png'],
    ]);
    GameTemplateReview::factory()->for($hoop)->for(User::factory()->create(['name' => 'Riya Patel']))->create(['rating' => 4, 'comment' => 'Kids queued for it.']);
    GameTemplateReview::factory()->for($hoop)->for($sam)->create(['rating' => 5, 'comment' => null]);

    $page = $this->actingAs($sam)->getJson('/game-templates/hoop')->assertOk();

    expect($page->json())
        ->toMatchArray(['id' => 'hoop', 'name' => 'Hoop Shot', 'rating' => 4.5, 'reviews_count' => 2])
        ->and($page->json('details_html'))->toContain('<h2>Great for events</h2>', '<strong>love</strong>')->not->toContain('<script>')
        ->and($page->json('thumbnail'))->toEndWith('/storage/game-templates/hoop/cover.jpg')
        ->and($page->json('screenshots'))->toHaveCount(2)
        ->and($page->json('breakdown'))->toBe(['5' => 1, '4' => 1, '3' => 0, '2' => 0, '1' => 0])
        ->and($page->json('my_review'))->toBe(['rating' => 5, 'comment' => null])
        ->and(collect($page->json('reviews'))->firstWhere('name', 'Riya Patel'))->toMatchArray(['initials' => 'RP', 'rating' => 4, 'comment' => 'Kids queued for it.', 'mine' => false]);
});

test('users can rate a game once, change their review and take it down', function () {
    $sam = userWithRole('user');

    $this->actingAs($sam)->putJson('/game-templates/stack/review', ['rating' => 4, 'comment' => '  Fun!  '])
        ->assertOk()
        ->assertJson(['message' => 'Thanks for your review.', 'rating' => 4, 'reviews_count' => 1, 'my_review' => ['rating' => 4, 'comment' => 'Fun!']]);

    $this->actingAs($sam)->putJson('/game-templates/stack/review', ['rating' => 2, 'comment' => ''])
        ->assertOk()
        ->assertJson(['rating' => 2, 'reviews_count' => 1, 'my_review' => ['rating' => 2, 'comment' => null]]);
    expect(GameTemplateReview::count())->toBe(1);

    $this->actingAs($sam)->deleteJson('/game-templates/stack/review')
        ->assertOk()
        ->assertJson(['message' => 'Your review was removed.', 'reviews_count' => 0, 'my_review' => null]);
    expect(GameTemplateReview::count())->toBe(0);
});

test('a review needs 1 to 5 stars and a short comment', function (array $data, string $field, string $message) {
    $this->actingAs(userWithRole('user'))->putJson('/game-templates/stack/review', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'no stars' => [['comment' => 'Nice'], 'rating', 'Choose a star rating.'],
    'too many stars' => [['rating' => 6], 'rating', 'Choose between 1 and 5 stars.'],
    'no stars at all' => [['rating' => 0], 'rating', 'Choose between 1 and 5 stars.'],
    'a long comment' => [['rating' => 3, 'comment' => str_repeat('a', 1001)], 'comment', 'Keep your comment under 1,000 characters.'],
]);

test('hidden games have no store page for clients, and guests see none', function () {
    GameTemplate::where('slug', 'slice')->update(['is_active' => false]);

    $this->getJson('/game-templates/quiz')->assertUnauthorized();
    $this->putJson('/game-templates/quiz/review', ['rating' => 5])->assertUnauthorized();
    $this->actingAs(userWithRole('user'))->getJson('/game-templates/slice')->assertNotFound();
    $this->actingAs(userWithRole('user'))->putJson('/game-templates/slice/review', ['rating' => 5])->assertNotFound();
    $this->actingAs(staff())->getJson('/game-templates/slice')->assertOk();
    expect(GameTemplateReview::count())->toBe(0);
});

test('staff add a cover photo, screenshots and a write-up to a game\'s page', function () {
    $quiz = GameTemplate::where('slug', 'quiz')->sole();

    $this->actingAs(staff())
        ->put("/admin/game-templates/{$quiz->id}", [
            'name' => 'Quiz', 'sort_order' => 1, 'is_active' => '1',
            'details' => 'Five quick questions.',
            'thumbnail' => UploadedFile::fake()->image('cover.jpg', 1600, 900),
            'screenshots' => [UploadedFile::fake()->image('one.png', 540, 960), UploadedFile::fake()->image('two.png', 540, 960)],
        ])
        ->assertSessionHasNoErrors();

    $quiz->refresh();
    expect($quiz->details)->toBe('Five quick questions.')
        ->and($quiz->screenshots)->toHaveCount(2);
    Storage::disk('public')->assertExists([$quiz->thumbnail_path, ...$quiz->screenshots]);
    expect($quiz->thumbnail_path)->toStartWith('game-templates/quiz/');

    // Replace the cover, drop one screenshot.
    [$first, $second] = $quiz->screenshots;
    $oldCover = $quiz->thumbnail_path;
    $this->actingAs(staff())
        ->put("/admin/game-templates/{$quiz->id}", [
            'name' => 'Quiz', 'sort_order' => 1, 'is_active' => '1',
            'thumbnail' => UploadedFile::fake()->image('new.jpg'),
            'remove_screenshots' => [$first, 'game-templates/other/not-ours.png'],
        ])
        ->assertSessionHasNoErrors();

    $quiz->refresh();
    expect($quiz->screenshots)->toBe([$second])->and($quiz->thumbnail_path)->not->toBe($oldCover);
    Storage::disk('public')->assertMissing([$first, $oldCover]);

    $this->actingAs(staff())->get("/admin/game-templates/{$quiz->id}/edit")
        ->assertOk()
        ->assertSee('Store page')
        ->assertSee($quiz->thumbnailUrl())
        ->assertSee('No one has reviewed this game yet.');
});

test('a game can have up to eight screenshots', function () {
    $quiz = GameTemplate::where('slug', 'quiz')->sole();
    $quiz->update(['screenshots' => array_map(fn ($i) => "game-templates/quiz/{$i}.png", range(1, 7))]);

    $this->actingAs(staff())
        ->put("/admin/game-templates/{$quiz->id}", [
            'name' => 'Quiz', 'sort_order' => 1, 'is_active' => '1',
            'screenshots' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')],
        ])
        ->assertSessionHasErrors(['screenshots' => 'A game can have up to 8 screenshots. Remove some first.']);
});

test('staff can take down a review, and a removed game takes its pictures with it', function () {
    $template = GameTemplate::factory()->create([
        'thumbnail_path' => 'game-templates/x/cover.jpg',
        'screenshots' => ['game-templates/x/one.png'],
    ]);
    Storage::disk('public')->put('game-templates/x/cover.jpg', 'jpg');
    Storage::disk('public')->put('game-templates/x/one.png', 'png');
    $review = GameTemplateReview::factory()->for($template)->create(['comment' => 'Spam spam']);

    $this->actingAs(staff())->get("/admin/game-templates/{$template->id}/edit")->assertSee('Spam spam');
    $this->actingAs(userWithRole('user'))->delete("/admin/game-templates/{$template->id}/reviews/{$review->id}")->assertForbidden();
    $this->actingAs(staff())->delete("/admin/game-templates/{$template->id}/reviews/{$review->id}")
        ->assertRedirect("/admin/game-templates/{$template->id}/edit");
    expect(GameTemplateReview::count())->toBe(0);

    GameTemplateReview::factory()->for($template)->create();
    $this->actingAs(staff())->delete("/admin/game-templates/{$template->id}");

    expect(GameTemplateReview::count())->toBe(0);
    Storage::disk('public')->assertMissing(['game-templates/x/cover.jpg', 'game-templates/x/one.png']);
});
