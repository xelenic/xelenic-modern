<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Kiosk Studio's animated screen templates, for the website's marketplace.
 * The screens themselves are built by the studio's own code (SCREEN_TEMPLATES
 * in public/nignest/js/templates.js, matched by name) and played by its
 * player; this is what the website says about them.
 */
class AnimationTemplates
{
    /**
     * The sections templates are filed under, as in Kiosk Studio.
     */
    public const GROUPS = [
        'welcome' => 'Welcome',
        'howto' => 'How to play',
        'result' => 'Results',
        'ads' => 'Advertising',
    ];

    /**
     * The advertising screens: name, icon (resources/views/components/site/
     * icon.blade.php), blurb, and what plays on them.
     */
    private const ADS = [
        ['brand-spotlight', 'Brand spotlight', 'megaphone', 'Your logo, tagline and a call to action', 'Your logo pops in and floats in a soft glow, your brand name spells itself out, the tagline types itself, and a shining button invites a tap, all over a moving aurora.'],
        ['mega-sale', 'Mega sale', 'tag', 'Big discount, sale dates and Shop button', 'A LIMITED TIME badge pops in, MEGA SALE lands in huge letters, a pulsing UP TO 50% OFF badge bounces in, and a shining Shop the sale button follows, over sun rays.'],
        ['new-arrival', 'New arrival', 'bag', 'Product, price tag and Shop now', 'NEW ARRIVAL spells itself out, the product bounces in and floats in a soft glow, its name and a short line follow, then a pulsing price tag and a shining Shop now button, among sparkles.'],
        ['todays-special', 'Today’s special', 'burger', 'Menu deal with a price badge', 'TODAY’S SPECIAL drops in, the dish bounces in with a wobbling price badge beside it, then its name, what comes with it, a shining Order now button and the serving times, over sun rays.'],
        ['coupon-code', 'Coupon code', 'coupon', 'Discount code to show at the counter', '20% OFF zooms in and pulses, a coupon flips into view and its code types out letter by letter, with a reminder to show it at the counter and a shining Get my code button, among sparkles.'],
        ['grand-opening', 'Grand opening', 'party', 'Event date, place and a celebration', 'A party popper pops in, GRAND OPENING rises in big glowing letters, the date appears on a pill, then the opening gift and a shining Find out more button, under falling confetti.'],
        ['happy-hour', 'Happy hour', 'cocktail', 'Time-limited deal with a big headline', 'HAPPY HOUR bounces in, a cocktail swings into view, then the deal, the hours on a pill and a shining See the menu button, under twinkling stars.'],
        ['free-gift', 'Free gift', 'gift', 'Spend-and-get offer with a gift', 'FREE GIFT zooms in, the gift bounces in and wiggles, a While stocks last ribbon slides across, and a shining Claim your gift button follows, with falling snow.'],
    ];

    /**
     * Which game each how-to-play screen opens (its icon is the game's), and
     * the word on its button.
     */
    private const HOW_TO_PLAY = [
        'quiz' => ['Quiz', 'PLAY'],
        'catch' => ['Catch', 'PLAY'],
        'slice' => ['Slice', 'PLAY'],
        'wheel' => ['Wheel', 'LET’S SPIN'],
        'runner' => ['Runner', 'PLAY'],
        'memory' => ['Memory', 'PLAY'],
        'whack' => ['Whack', 'PLAY'],
        'stack' => ['Stack', 'PLAY'],
        'hoop' => ['Hoop', 'PLAY'],
        'scratch' => ['Scratch', 'GET MY CARD'],
    ];

    /**
     * Every template, in Kiosk Studio's order.
     *
     * @return Collection<string, array{slug: string, name: string, icon: string, group: string, blurb: string, description: string, kind: ?string, links: string}>
     */
    public static function all(): Collection
    {
        $templates = [[
            'slug' => 'welcome',
            'name' => 'Welcome',
            'icon' => 'sparkles',
            'group' => 'welcome',
            'blurb' => 'Logo, animated title, start button',
            'description' => 'An attract screen for when nobody is playing. Your logo pops in and floats, the title spells itself out letter by letter, a line of text types itself, three icons bounce in, and a shining TAP TO START button invites people over, all over a softly moving aurora.',
            'kind' => null,
            'links' => 'Set it as your kiosk’s start screen for an attract loop, and link its button to your game menu or first game.',
        ]];

        foreach (self::HOW_TO_PLAY as $kind => [$name, $button]) {
            $templates[] = [
                'slug' => "{$kind}-start",
                'name' => "{$name} start",
                'icon' => $kind,
                'group' => 'howto',
                'blurb' => $kind === 'scratch' ? 'How it works + card button' : ($kind === 'wheel' ? 'How to play + SPIN button' : 'How to play + PLAY button'),
                'description' => "The how-to-play screen for the {$name} game. The game’s icon bounces in, the title appears letter by letter, the three steps slide in one after another, and a shining {$button} button starts the game.",
                'kind' => $kind,
                'links' => 'Its button opens the game. Add the game to your design and Kiosk Studio links them for you; you can change the link later in the Flow Map.',
            ];
        }

        $templates[] = [
            'slug' => 'winner',
            'name' => 'Winner',
            'icon' => 'trophy',
            'group' => 'result',
            'blurb' => 'Trophy, score and confetti',
            'description' => 'A celebration for after a game. A trophy bounces in, YOU WIN! pops up with the player’s score, confetti falls, and buttons offer to claim a prize or play again.',
            'kind' => null,
            'links' => 'Use it as a game’s winning screen: it shows the player’s score. Link “Claim your prize” to a form screen and “Play again” back to the game.',
        ];

        foreach (self::ADS as [$slug, $name, $icon, $blurb, $description]) {
            $templates[] = [
                'slug' => $slug,
                'name' => $name,
                'icon' => $icon,
                'group' => 'ads',
                'blurb' => $blurb,
                'description' => $description.' Swap in your own words, prices and pictures.',
                'kind' => null,
                'links' => 'It moves on by itself after 8 seconds once you choose the next screen in Auto advance, so a few ads make a rotating loop. Link its button to an offer or form screen, or start from the Advertising kiosk project, where four ads already take turns.',
            ];
        }

        return collect($templates)->keyBy('slug');
    }

    /**
     * @return array{slug: string, name: string, icon: string, group: string, blurb: string, description: string, kind: ?string, links: string}|null
     */
    public static function find(string $slug): ?array
    {
        return self::all()->get($slug);
    }
}
