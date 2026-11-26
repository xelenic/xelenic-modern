<?php

use App\Models\GameTemplate;
use App\Support\AnimationTemplates;
use App\Support\KioskConcepts;

test('anyone can browse the trending kiosk app concepts', function () {
    $this->get(route('trending.index'))
        ->assertOk()
        ->assertSee('Trending Kiosk Apps')
        ->assertSeeText('8 campaign concepts')
        // Trending first.
        ->assertSeeInOrder(['Spin &amp; Win Mall Activation', 'Festival Fun Zone', 'Happy Hour Memory Match', 'Product Launch Quiz'], false)
        ->assertSee('data-filter="event"', false)
        ->assertSeeText('Events & crowds 3')
        ->assertSee('data-screen="Brand spotlight"', false)
        ->assertSee(route('trending.show', 'festival-fun-zone'))
        ->assertSeeInOrder(['nignest/js/runtime.js', 'nignest/js/templates.js', 'js/animations.js'], false);
});

test('a concept’s page explains the campaign and plays it', function () {
    $this->get(route('trending.show', 'happy-hour-memory-match'))
        ->assertOk()
        ->assertSee('Happy Hour Memory Match')
        ->assertSee('Play the concept')
        ->assertSee('data-demo-src="'.route('trending.demo', 'happy-hour-memory-match').'"', false)
        ->assertSee('data-screen="Happy hour"', false)
        ->assertSeeInOrder(['How the campaign flows', 'Draws people in', 'Plays', 'Wins', 'Gets a coupon code', 'Back to the loop'])
        ->assertDontSee('Leaves details')
        ->assertSee(route('marketplace.games.show', 'memory'))
        ->assertSee(route('marketplace.animations.show', 'happy-hour'))
        ->assertSee(route('marketplace.animations.show', 'coupon-code'))
        ->assertSee('Ordering &amp; Billing Kiosk', false)
        ->assertSee('More trending kiosk apps');

    $this->get(route('trending.show', 'festival-fun-zone'))
        ->assertSeeInOrder(['Picks a game', 'A menu offers Runner, Whack Attack and Hoop Shot.'])
        ->assertSee('Outdoor');

    $this->get(route('trending.show', 'mall-spin-and-win'))->assertSee('Leaves details')->assertSee('Collects leads');
});

test('the demo builds the campaign from its recipe, sandboxed', function () {
    $this->get(route('trending.demo', 'mall-spin-and-win'))
        ->assertOk()
        ->assertHeader('Content-Security-Policy', 'sandbox allow-scripts')
        ->assertSeeInOrder(['nignest/js/games.js', 'nignest/js/runtime.js', 'nignest/js/templates.js', 'nignest/js/concepts.js'], false)
        ->assertSee('"attract":["Brand spotlight","Mega sale"]', false)
        ->assertSee('"games":["wheel"]', false);
});

test('an unknown concept is not found', function () {
    $this->get(route('trending.show', 'nope'))->assertNotFound();
    $this->get(route('trending.demo', 'nope'))->assertNotFound();
});

test('every concept is made of templates Kiosk Studio has', function () {
    $screens = AnimationTemplates::all()->pluck('name');

    foreach (KioskConcepts::all() as $concept) {
        $recipe = $concept['recipe'];
        expect($screens)->toContain(...$recipe['attract']);
        expect(GameTemplate::BUILTIN)->toContain(...$recipe['games']);
        if (isset($recipe['reward'])) {
            expect($screens)->toContain($recipe['reward']);
        }
        expect(KioskConcepts::CATEGORIES)->toHaveKey($concept['category']);
    }

    expect(file_get_contents(public_path('nignest/js/concepts.js')))->toContain('function conceptProject(recipe)');
});

test('the menu’s Trending Kiosk App item opens the concepts', function () {
    $this->get('/')->assertSee('href="'.route('trending.index').'"', false);
});
