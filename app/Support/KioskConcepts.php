<?php

namespace App\Support;

use App\Enums\KioskMachine;
use Illuminate\Support\Collection;

/**
 * Trending kiosk apps: concept campaigns for brand promotions and event
 * crowd engagement. Each is a recipe that Kiosk Studio's own templates are
 * built into a whole kiosk app (conceptProject() in public/nignest/js/
 * concepts.js), so its demo is something the studio can really make. What
 * the pages say about a concept matches its recipe.
 */
class KioskConcepts
{
    public const CATEGORIES = [
        'brand' => 'Brand promotion',
        'event' => 'Events & crowds',
        'retail' => 'Retail & malls',
        'food' => 'Food & drink',
    ];

    /**
     * Every concept, trending ones first.
     *
     * @return Collection<string, array{slug: string, name: string, icon: string, category: string, settings: list<string>, places: list<string>, tagline: string, summary: string, idea: string, goals: list<string>, machine: KioskMachine, trending: bool, recipe: array{attract: list<string>, tagline?: string, games: list<string>, reward?: string, form: bool}}>
     */
    public static function all(): Collection
    {
        return collect([
            [
                'slug' => 'mall-spin-and-win',
                'name' => 'Spin & Win Mall Activation',
                'icon' => 'wheel',
                'category' => 'retail',
                'settings' => ['Indoor'],
                'places' => ['Shopping malls', 'Retail stores', 'Brand pop-ups'],
                'tagline' => 'Spin the wheel, win on the spot, and leave your details to claim it.',
                'summary' => 'A prize wheel that turns passing shoppers into leads.',
                'idea' => 'Your brand and your sale take turns on the screen until someone walks up. One tap, one spin, and the wheel picks the prize: a discount, a free coffee, the jackpot. To claim it, winners leave their name and number, so every spin is a lead you can follow up.',
                'goals' => ['Draw shoppers to your stand', 'Collect leads with consent', 'Give prizes people talk about'],
                'machine' => KioskMachine::Advertising,
                'trending' => true,
                'recipe' => ['attract' => ['Brand spotlight', 'Mega sale'], 'tagline' => 'Spin the wheel and win a prize today', 'games' => ['wheel'], 'form' => true],
            ],
            [
                'slug' => 'festival-fun-zone',
                'name' => 'Festival Fun Zone',
                'icon' => 'tent',
                'category' => 'event',
                'settings' => ['Outdoor', 'Indoor'],
                'places' => ['Concerts and festivals', 'Fairs and carnivals', 'Fun days'],
                'tagline' => 'Three quick games that keep the crowd entertained.',
                'summary' => 'A game arcade for crowds, with your sponsor front and centre.',
                'idea' => 'Queues and crowds get bored. Give them three quick games to choose from: a runner, Whack Attack and Hoop Shot. The best scores win a prize to collect, a near miss gets another go, and your sponsor’s welcome screen greets every player.',
                'goals' => ['Keep queues and crowds entertained', 'Put a sponsor in front of every player', 'Bring people back for another go'],
                'machine' => KioskMachine::Advertising,
                'trending' => true,
                'recipe' => ['attract' => ['Welcome'], 'tagline' => 'Pick a game and beat the high score!', 'games' => ['runner', 'whack', 'hoop'], 'form' => false],
            ],
            [
                'slug' => 'happy-hour-memory-match',
                'name' => 'Happy Hour Memory Match',
                'icon' => 'cocktail',
                'category' => 'food',
                'settings' => ['Indoor'],
                'places' => ['Restaurants and bars', 'Cafés', 'Food courts'],
                'tagline' => 'Match the cards and win a coupon for your next visit.',
                'summary' => 'Your deals on a loop, and a game that wins a coupon.',
                'idea' => 'Your happy hour and today’s special take turns on the screen. While they wait, guests play a quick memory match, and winners get a coupon code to show at the counter. It sells today’s deal and brings them back for the next one.',
                'goals' => ['Show your deals where people wait', 'Turn waiting into playing', 'Bring guests back with a coupon'],
                'machine' => KioskMachine::OrderingBilling,
                'trending' => true,
                'recipe' => ['attract' => ['Happy hour', 'Today’s special'], 'games' => ['memory'], 'reward' => 'Coupon code', 'form' => false],
            ],
            [
                'slug' => 'product-launch-quiz',
                'name' => 'Product Launch Quiz',
                'icon' => 'quiz',
                'category' => 'brand',
                'settings' => ['Indoor'],
                'places' => ['Launch events', 'Showrooms', 'Pop-up stores'],
                'tagline' => 'Teach the new product through five quick questions.',
                'summary' => 'A quiz that tells your product’s story and rewards the best scores.',
                'idea' => 'Launching something new? Let the kiosk tell the story. Your new product takes the screen, then visitors answer five quick questions about it, your questions. A good score wins a prize and the chance to leave their details, and everyone walks away knowing what makes it special.',
                'goals' => ['Explain what’s new in a fun way', 'Reward the people who paid attention', 'Find your most interested customers'],
                'machine' => KioskMachine::Portrait55,
                'trending' => false,
                'recipe' => ['attract' => ['New arrival'], 'games' => ['quiz'], 'form' => true],
            ],
            [
                'slug' => 'grand-opening-scratch-and-win',
                'name' => 'Grand Opening Scratch & Win',
                'icon' => 'scratch',
                'category' => 'retail',
                'settings' => ['Indoor'],
                'places' => ['Store openings', 'Mall launches', 'Anniversaries'],
                'tagline' => 'Every visitor scratches a card on opening day.',
                'summary' => 'A digital scratch card that makes opening day an event.',
                'idea' => 'Announce the opening and the free gift on the screen, then invite every visitor to scratch a card. The prize appears under the foil, and claiming it takes their name and number, so you start your first week with a list of customers to welcome back.',
                'goals' => ['Make opening day feel like an event', 'Give every visitor a reason to stop', 'Start your customer list on day one'],
                'machine' => KioskMachine::SelfServiceTower,
                'trending' => false,
                'recipe' => ['attract' => ['Grand opening', 'Free gift'], 'games' => ['scratch'], 'form' => true],
            ],
            [
                'slug' => 'hoop-shot-sponsor-challenge',
                'name' => 'Hoop Shot Sponsor Challenge',
                'icon' => 'hoop',
                'category' => 'event',
                'settings' => ['Indoor', 'Outdoor'],
                'places' => ['Sports events', 'Stadium concourses', 'Sports stores'],
                'tagline' => 'Swipe to shoot, beat the target, win the merch.',
                'summary' => 'A basketball challenge for sports brands and sponsors.',
                'idea' => 'Fans love a challenge. Your brand welcomes them, then players swipe up to sink as many baskets as they can. Beat the target to win, leave your details to claim the prize, or try again to do better.',
                'goals' => ['Put your sponsorship in fans’ hands', 'Turn waiting time into play time', 'Collect fans’ details for your next campaign'],
                'machine' => KioskMachine::Advertising,
                'trending' => false,
                'recipe' => ['attract' => ['Brand spotlight'], 'tagline' => 'Can you beat today’s best score?', 'games' => ['hoop'], 'form' => true],
            ],
            [
                'slug' => 'trade-show-tower-stack',
                'name' => 'Trade Show Tower Stack',
                'icon' => 'stack',
                'category' => 'brand',
                'settings' => ['Indoor'],
                'places' => ['Exhibitions', 'Conferences', 'Corporate events'],
                'tagline' => 'A skill challenge that fills your stand and your lead list.',
                'summary' => 'A stacking challenge that draws visitors to your stand.',
                'idea' => 'On a busy show floor, a game is the easiest way to start a conversation. Visitors stack the tallest tower they can, the best builders win, and claiming the prize hands your team their details. Your sales team gets warm leads, and visitors remember your stand.',
                'goals' => ['Stand out on a busy show floor', 'Start conversations with visitors', 'Leave with warm leads for sales'],
                'machine' => KioskMachine::Portrait55,
                'trending' => false,
                'recipe' => ['attract' => ['Brand spotlight'], 'tagline' => 'Build the tallest tower and win', 'games' => ['stack'], 'form' => true],
            ],
            [
                'slug' => 'family-fun-day',
                'name' => 'Family Fun Day',
                'icon' => 'family',
                'category' => 'event',
                'settings' => ['Indoor', 'Outdoor'],
                'places' => ['Family days', 'Weekend mall events', 'School fairs'],
                'tagline' => 'Easy, colourful games for the whole family.',
                'summary' => 'Simple games that children and parents can play together.',
                'idea' => 'Some games are for everyone. Fruit Catch and Fruit Slice are easy to pick up, bright and quick, so children, parents and grandparents can all have a go. Winners collect a prize at the desk, and your event gets a corner people remember.',
                'goals' => ['Give families something to do together', 'Keep children happily busy', 'Make your event feel welcoming'],
                'machine' => KioskMachine::SelfServiceTower,
                'trending' => false,
                'recipe' => ['attract' => ['Welcome'], 'tagline' => 'Fun games for the whole family', 'games' => ['catch', 'slice'], 'form' => false],
            ],
        ])->keyBy('slug');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $slug): ?array
    {
        return self::all()->get($slug);
    }
}
