<?php

namespace App\Http\Controllers;

use App\Enums\KioskMachine;
use App\Models\GameTemplate;
use App\Support\AnimationTemplates;
use Illuminate\View\View;

/**
 * Animation templates on the website: Kiosk Studio's ready-made animated
 * screens (App\Support\AnimationTemplates), each playing live from the
 * studio's own code, with a page of its own.
 */
class AnimationMarketplaceController extends Controller
{
    public function index(): View
    {
        $templates = AnimationTemplates::all();

        return view('marketplace.animations.index', [
            'templates' => $templates,
            'groups' => collect(AnimationTemplates::GROUPS)->map(fn (string $label, string $group) => [
                'label' => $label,
                'count' => $templates->where('group', $group)->count(),
            ]),
        ]);
    }

    public function show(string $slug): View
    {
        $template = AnimationTemplates::find($slug);
        abort_unless($template !== null, 404);

        $games = $template['group'] === 'ads' ? collect() : GameTemplate::active()->ordered()->where('kind', 'builtin')->get();

        return view('marketplace.animations.show', [
            'template' => $template,
            'group' => AnimationTemplates::GROUPS[$template['group']],
            // The game a how-to-play screen opens; every game, for the others.
            'game' => $template['kind'] ? $games->firstWhere('slug', $template['kind']) : null,
            'games' => $template['kind'] ? collect() : $games,
            // The kiosks ads are made for.
            'machines' => $template['group'] === 'ads' ? [KioskMachine::Advertising, KioskMachine::Portrait55, KioskMachine::SelfServiceTower] : [],
            'others' => AnimationTemplates::all()->except($slug)
                // An ad shows other ads; a game screen, the screens around a
                // game (welcome, winner) before more of its own kind.
                ->sortBy(fn (array $other) => $template['group'] === 'ads'
                    ? ($other['group'] === 'ads' ? 0 : 1)
                    : ($other['group'] === 'ads' ? 2 : ($other['group'] === $template['group'] ? 1 : 0)))
                ->take(4),
        ]);
    }
}
