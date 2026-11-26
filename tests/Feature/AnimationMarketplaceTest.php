<?php

use App\Support\AnimationTemplates;

test('anyone can explore the animation templates', function () {
    $this->get(route('marketplace.animations.index'))
        ->assertOk()
        ->assertSee('Animation')
        ->assertSeeText('20 animated screens')
        ->assertSeeInOrder(['Welcome', 'Quiz start', 'Catch start', 'Scratch start', 'Winner'])
        ->assertSee('data-screen="Welcome"', false)
        ->assertSee('data-category="howto"', false)
        ->assertSee(route('marketplace.animations.show', 'wheel-start'))
        // Kiosk Studio's player and templates play the screens.
        ->assertSeeInOrder(['nignest/js/games.js', 'nignest/js/runtime.js', 'nignest/js/templates.js', 'js/animations.js'], false);
});

test('a how-to-play template’s page links to its game', function () {
    $this->get(route('marketplace.animations.show', 'wheel-start'))
        ->assertOk()
        ->assertSee('Wheel start')
        ->assertSee('How to play screen')
        ->assertSee('data-screen="Wheel start"', false)
        ->assertSee('Goes with this game')
        ->assertSee(route('marketplace.games.show', 'wheel'))
        ->assertSee('More animation templates');
});

test('the welcome and winner templates work with every game', function () {
    $this->get(route('marketplace.animations.show', 'winner'))
        ->assertOk()
        ->assertSee('Winner')
        ->assertSee('Works with every game')
        ->assertSee(route('marketplace.games.show', 'hoop'));

    $this->get(route('marketplace.animations.show', 'welcome'))->assertOk()->assertSee('TAP TO START');
});

test('an unknown template is not found', function () {
    $this->get(route('marketplace.animations.show', 'nope'))->assertNotFound();
});

test('every template on the website is one Kiosk Studio has, by name', function () {
    $js = file_get_contents(public_path('nignest/js/templates.js'));
    preg_match('/const SCREEN_TEMPLATES = \[(.*?)\n\];/s', $js, $block);
    preg_match_all("/\\{ name: '([^']+)'/", $block[1], $names);

    expect(AnimationTemplates::all()->pluck('name')->all())->toBe($names[1]);
});

test('the site menu links to the animation templates', function () {
    $this->get('/')->assertSee('href="'.route('marketplace.animations.index').'"', false);
});

test('advertising templates have a group of their own', function () {
    $this->get(route('marketplace.animations.index'))
        ->assertOk()
        ->assertSee('data-filter="ads"', false)
        ->assertSeeText('Advertising 8')
        ->assertSeeInOrder(['Winner', 'Brand spotlight', 'Mega sale', 'New arrival', 'Today’s special', 'Coupon code', 'Grand opening', 'Happy hour', 'Free gift'])
        ->assertSee('data-category="ads"', false);

    $this->get(route('marketplace.animations.show', 'mega-sale'))
        ->assertOk()
        ->assertSee('Advertising screen')
        ->assertSee('data-screen="Mega sale"', false)
        ->assertSee('rotating loop')
        ->assertSee('Made for advertising kiosks')
        ->assertSee('Advertising Kiosk')
        ->assertDontSee('Works with every game');
});

test('the menu’s Advertisements item opens the advertising templates', function () {
    $this->get('/')->assertSee('href="'.route('marketplace.animations.index', ['group' => 'ads']).'#templates"', false);
});

test('Kiosk Studio offers the advertising kiosk as a starting point', function () {
    $core = file_get_contents(public_path('nignest/js/editor-core.js'));
    $templates = file_get_contents(public_path('nignest/js/templates.js'));

    expect($core)->toContain("make: adLoopProject, name: 'Advertising kiosk'")
        ->and($templates)->toContain('function adLoopProject()')
        ->and(file_get_contents(public_path('nignest/js/editor-packages.js')))->toContain("['ads', 'Advertising']");
});

test('a template’s page suggests templates of the right kind', function () {
    $this->get(route('marketplace.animations.show', 'happy-hour'))
        ->assertSeeInOrder(['More animation templates', 'Brand spotlight', 'Mega sale', 'New arrival', 'Today’s special']);

    $this->get(route('marketplace.animations.show', 'quiz-start'))
        ->assertSeeInOrder(['More animation templates', 'Welcome', 'Winner', 'Catch start', 'Slice start']);
});
