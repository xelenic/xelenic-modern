<?php

use App\Models\GameTemplate;
use App\Models\GameTemplateReview;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('anyone can browse the game templates marketplace', function () {
    $wheel = GameTemplate::where('slug', 'wheel')->sole();
    GameTemplateReview::factory()->for($wheel)->create(['rating' => 5]);
    GameTemplateReview::factory()->for($wheel)->create(['rating' => 4]);
    GameTemplate::factory()->create(['name' => 'Balloon Pop', 'description' => 'Pop the balloons']);
    GameTemplate::factory()->create(['name' => 'Secret Game', 'is_active' => false]);

    $this->get(route('marketplace.games.index'))
        ->assertOk()
        ->assertSee('Kiosk Game Templates')
        ->assertSeeInOrder(['Quiz', 'Catch', 'Slice', 'Spin Wheel', 'Runner'])
        ->assertSee('Balloon Pop')
        ->assertDontSee('Secret Game')
        ->assertSee(route('marketplace.games.show', $wheel))
        ->assertSee('4.5 (2)')
        ->assertSee('data-category="prize"', false)
        ->assertSeeText('10 playable demos');
});

test('a game’s page has its demo, how to play and reviews', function () {
    $wheel = GameTemplate::where('slug', 'wheel')->sole();
    GameTemplateReview::factory()->for($wheel)->create(['rating' => 4, 'comment' => 'Our shoppers queued for it.']);

    $this->get(route('marketplace.games.show', $wheel))
        ->assertOk()
        ->assertSee('Spin Wheel')
        ->assertSee('Play the demo')
        ->assertSee('data-demo-src="'.route('marketplace.games.demo', $wheel).'"', false)
        ->assertSeeInOrder(['How to play', 'Tap SPIN to turn the wheel', 'Wherever it stops is your prize'])
        ->assertSee('Ends with a prize')
        ->assertSee('Our shoppers queued for it.')
        ->assertSee('Sign in to review')
        ->assertSee('More games');
});

test('an uploaded game has a page but no demo', function () {
    $custom = GameTemplate::factory()->create(['name' => 'Balloon Pop']);

    $this->get(route('marketplace.games.show', $custom))
        ->assertOk()
        ->assertSee('Balloon Pop')
        ->assertDontSee('Play the demo')
        ->assertSee('Try it in Kiosk Studio');

    $this->get(route('marketplace.games.demo', $custom))->assertNotFound();
});

test('hidden games are only for staff', function () {
    $hidden = GameTemplate::factory()->create(['is_active' => false]);

    $this->get(route('marketplace.games.show', $hidden))->assertNotFound();
    $this->actingAs(userWithRole('user'))->get(route('marketplace.games.show', $hidden))->assertNotFound();
    $this->actingAs(userWithRole('admin'))->get(route('marketplace.games.show', $hidden))->assertOk();
});

test('the demo runs the game in Kiosk Studio’s player, sandboxed', function () {
    $runner = GameTemplate::where('slug', 'runner')->sole();

    $this->get(route('marketplace.games.demo', $runner))
        ->assertOk()
        ->assertHeader('Content-Security-Policy', 'sandbox allow-scripts')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSeeInOrder(['nignest/js/games.js', 'nignest/js/runtime.js', 'nignest/js/templates.js'], false)
        ->assertSee('var kind = "runner";', false)
        ->assertSee("'{{score}}'", false);
});

test('guests are asked to sign in to review, then brought back', function () {
    $quiz = GameTemplate::where('slug', 'quiz')->sole();

    $this->get(route('marketplace.games.review.start', $quiz))->assertRedirect(route('login'));
    $this->put(route('marketplace.games.review', $quiz), ['rating' => 5])->assertRedirect(route('login'));

    $this->actingAs(userWithRole('user'))
        ->get(route('marketplace.games.review.start', $quiz))
        ->assertRedirect(route('marketplace.games.show', $quiz).'#review');
});

test('signed-in users can rate a game, change their review and remove it', function () {
    $quiz = GameTemplate::where('slug', 'quiz')->sole();
    $user = userWithRole('user');

    $this->actingAs($user)->put(route('marketplace.games.review', $quiz), ['rating' => 0])->assertSessionHasErrors('rating');

    $this->actingAs($user)->put(route('marketplace.games.review', $quiz), ['rating' => 4, 'comment' => '  Great for launches.  '])
        ->assertRedirect(route('marketplace.games.show', $quiz).'#reviews')
        ->assertSessionHas('status', 'Thanks for your review.');
    expect($quiz->reviews()->sole())->rating->toBe(4)->comment->toBe('Great for launches.');

    $this->actingAs($user)->put(route('marketplace.games.review', $quiz), ['rating' => 5]);
    expect($quiz->reviews()->sole())->rating->toBe(5)->comment->toBeNull();

    $this->actingAs($user)->get(route('marketplace.games.show', $quiz))
        ->assertSee('Update review')
        ->assertSee('Remove my review');

    $this->actingAs($user)->delete(route('marketplace.games.review.destroy', $quiz))->assertSessionHas('status', 'Your review was removed.');
    expect($quiz->reviews()->count())->toBe(0);
});

test('the site menu links to the marketplace', function () {
    $this->get('/')->assertSee('href="'.route('marketplace.games.index').'"', false);
});
