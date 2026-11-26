<?php

/*
 * Kiosk Studio draws its stickers, game pieces and icons from its own
 * illustrations (public/nignest/js/art.js), never emoji. The games need it,
 * so every page that runs them loads it first, and exported kiosks carry it.
 */

test('every page that runs the kiosk player loads the illustrations first', function () {
    $pages = [
        'Kiosk Studio' => file_get_contents(public_path('nignest/index.html')),
        'play links' => file_get_contents(resource_path('views/play.blade.php')),
        'game demos' => file_get_contents(resource_path('views/marketplace/games/demo.blade.php')),
        'concept demos' => file_get_contents(resource_path('views/trending/demo.blade.php')),
        'site pages' => file_get_contents(resource_path('views/components/layouts/site.blade.php')),
    ];

    foreach ($pages as $page => $source) {
        $art = strpos($source, 'art.js');
        expect($art)->not->toBeFalse("{$page} doesn't load art.js")
            ->and($art)->toBeLessThan(strpos($source, 'games.js'), "{$page} loads art.js after games.js");
    }
});

test('an exported kiosk carries the illustrations', function () {
    expect(file_get_contents(public_path('nignest/js/editor-core.js')))
        ->toContain("KioskArt.toString() + '\\n' + KioskGames.toString()");
});

test('the sticker palette only offers stickers that have an illustration', function () {
    $art = file_get_contents(public_path('nignest/js/art.js'));
    preg_match("/const EMOJI_LIBRARY = '([^']+)'/u", file_get_contents(public_path('nignest/js/templates.js')), $library);

    foreach (explode(' ', $library[1]) as $sticker) {
        expect($art)->toContain("'{$sticker}':");
    }
});

test('the default prizes have pictures of their own, not emoji in their labels', function () {
    $templates = file_get_contents(public_path('nignest/js/templates.js'));

    expect($templates)->toContain("{ label: 'Free coffee', icon: '☕',")
        ->and(preg_match("/label: '[^']*[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u", $templates))->toBe(0);
});
