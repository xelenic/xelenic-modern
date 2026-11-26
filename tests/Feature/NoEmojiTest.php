<?php

use App\Models\Client;
use App\Models\GameTemplate;

/*
 * The website shows SVG icons (components/site/icon.blade.php), never emoji.
 * The kiosk apps it previews are Kiosk Studio's own designs, played by its
 * player in the browser, so they aren't part of the page's HTML.
 */

dataset('website pages', [
    'home' => fn () => route('home'),
    'game templates' => fn () => route('marketplace.games.index'),
    'a game' => fn () => route('marketplace.games.show', 'wheel'),
    'an uploaded game' => fn () => route('marketplace.games.show', GameTemplate::factory()->create(['icon' => '🎈'])),
    'animation templates' => fn () => route('marketplace.animations.index'),
    'a how-to-play template' => fn () => route('marketplace.animations.show', 'quiz-start'),
    'an ad template' => fn () => route('marketplace.animations.show', 'happy-hour'),
    'the winner template' => fn () => route('marketplace.animations.show', 'winner'),
    'Kiosk Studio' => fn () => route('studio'),
    'trending kiosk apps' => fn () => route('trending.index'),
    'a trending concept' => fn () => route('trending.show', 'happy-hour-memory-match'),
    'a multi-game concept' => fn () => route('trending.show', 'festival-fun-zone'),
    'our clients' => fn () => route('clients.index'),
    'a client story' => fn () => route('clients.show', Client::factory()->create(['is_published' => true])),
]);

test('website pages use icons, not emoji', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    preg_match_all('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}]/u', $html, $found);

    expect(array_unique($found[0]))->toBe([]);
})->with('website pages');
