<?php

namespace App\Http\Controllers;

use App\Models\GameTemplate;
use App\Support\AnimationTemplates;
use App\Support\KioskConcepts;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Trending kiosk apps on the website: concept campaigns for brand promotion
 * and event crowd engagement (App\Support\KioskConcepts), each playable,
 * built from Kiosk Studio's own templates.
 */
class TrendingKioskAppController extends Controller
{
    public function index(): View
    {
        $concepts = KioskConcepts::all();

        return view('trending.index', [
            'concepts' => $concepts->sortByDesc('trending'),
            'categories' => collect(KioskConcepts::CATEGORIES)->map(fn (string $label, string $key) => [
                'label' => $label,
                'count' => $concepts->where('category', $key)->count(),
            ])->filter(fn (array $category) => $category['count'] > 0),
            'games' => $this->games(),
        ]);
    }

    public function show(string $slug): View
    {
        $concept = KioskConcepts::find($slug);
        abort_unless($concept !== null, 404);

        $games = $this->games();
        $screens = AnimationTemplates::all()->keyBy('name');

        return view('trending.show', [
            'concept' => $concept,
            'category' => KioskConcepts::CATEGORIES[$concept['category']],
            'games' => $games,
            // What it's made of, for linking to the marketplace.
            'gameParts' => collect($concept['recipe']['games'])->map(fn (string $kind) => $games->get($kind))->filter(),
            'screenParts' => collect([...$concept['recipe']['attract'], ...array_filter([$concept['recipe']['reward'] ?? null])])
                ->map(fn (string $name) => $screens->get($name))->filter(),
            'flow' => $this->flow($concept, $games),
            'others' => KioskConcepts::all()->except($slug)
                ->sortBy(fn (array $other) => $other['category'] === $concept['category'] ? 0 : 1)
                ->take(3),
        ]);
    }

    /**
     * The concept running in Kiosk Studio's player, built from its recipe.
     * Sandboxed, like a play link.
     */
    public function demo(string $slug): Response
    {
        $concept = KioskConcepts::find($slug);
        abort_unless($concept !== null, 404);

        return response()
            ->view('trending.demo', ['concept' => $concept])
            ->header('Content-Security-Policy', 'sandbox allow-scripts')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Kiosk Studio's games, by kind.
     *
     * @return Collection<string, GameTemplate>
     */
    private function games(): Collection
    {
        return GameTemplate::active()->where('kind', 'builtin')->get()->keyBy('slug');
    }

    /**
     * How the campaign goes, step by step, from its recipe (the order
     * conceptProject() links the screens in).
     *
     * @param  array<string, mixed>  $concept
     * @param  Collection<string, GameTemplate>  $games
     * @return list<array{icon: string, title: string, text: string}>
     */
    private function flow(array $concept, Collection $games): array
    {
        $recipe = $concept['recipe'];
        $name = fn (string $kind) => $games->get($kind)?->name ?? ucfirst($kind);
        $list = fn (array $items) => count($items) > 1
            ? implode(', ', array_slice($items, 0, -1)).' and '.end($items)
            : ($items[0] ?? '');
        $prizeGames = array_intersect($recipe['games'], ['wheel', 'scratch']);

        $steps = [[
            'icon' => 'megaphone',
            'title' => 'Draws people in',
            'text' => count($recipe['attract']) > 1
                ? 'The '.$list($recipe['attract']).' screens take turns until someone taps.'
                : 'The '.$recipe['attract'][0].' screen plays until someone taps.',
        ]];

        if (count($recipe['games']) > 1) {
            $steps[] = ['icon' => 'gamepad', 'title' => 'Picks a game', 'text' => 'A menu offers '.$list(array_map($name, $recipe['games'])).'.'];
        }

        $first = $recipe['games'][0];
        $steps[] = [
            'icon' => $games->get($first)?->siteIcon() ?? 'gamepad',
            'title' => 'Plays',
            'text' => count($recipe['games']) > 1
                ? 'A how-to-play screen, then the game.'
                : 'A how-to-play screen, then '.$name($first).'.',
        ];

        $steps[] = [
            'icon' => 'trophy',
            'title' => 'Wins',
            'text' => $prizeGames
                ? 'The game picks a prize, and confetti falls.'
                : 'A winning score wins a prize; a near miss gets another go.',
        ];

        if ($recipe['reward'] ?? null) {
            $steps[] = ['icon' => AnimationTemplates::all()->firstWhere('name', $recipe['reward'])['icon'] ?? 'gift', 'title' => 'Gets a '.mb_strtolower($recipe['reward']), 'text' => 'The prize is a '.mb_strtolower($recipe['reward']).' screen to show at the counter.'];
        }

        if ($recipe['form']) {
            $steps[] = ['icon' => 'clipboard', 'title' => 'Leaves details', 'text' => 'Name and mobile number, to claim the prize.'];
        }

        $steps[] = ['icon' => 'loop', 'title' => 'Back to the loop', 'text' => 'A thank-you, then the screen returns to the start for the next person.'];

        return $steps;
    }
}
