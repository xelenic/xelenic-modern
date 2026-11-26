<?php

use App\Enums\KioskMachine;

test('the Kiosk Studio page tells visitors about it, with screenshots', function () {
    $response = $this->get(route('studio'))
        ->assertOk()
        ->assertSee('Xelenic Kiosk Studio')
        ->assertSeeInOrder(['Flow Map', 'Screen Editor', 'Animation timeline', 'Game templates', 'Screen templates', 'Play &amp; publish'], false)
        ->assertSeeText('10 ready-made games')
        ->assertSeeText('20 animated screens')
        ->assertSee(route('projects.create.type', 'kiosk-app'))
        ->assertSee(route('marketplace.games.index'))
        ->assertSee(route('marketplace.animations.index'))
        ->assertSee(route('marketplace.games.show', 'wheel'))
        ->assertSee('{{score}}');

    foreach (KioskMachine::cases() as $machine) {
        $response->assertSee($machine->label());
    }

    foreach (['editor', 'flow', 'timeline', 'games', 'screen-template', 'play', 'editor-light'] as $shot) {
        $response->assertSee("img/studio/{$shot}.webp");
        expect(public_path("moderning_xelenic/assets/img/studio/{$shot}.webp"))->toBeFile();
    }
});

test('the site menu links to the Kiosk Studio page', function () {
    $this->get('/')->assertSee('href="'.route('studio').'"', false)->assertSee('Discover Kiosk Studio');
});
