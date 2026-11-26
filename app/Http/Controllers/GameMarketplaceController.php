<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameTemplateReviewRequest;
use App\Models\GameTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * The game templates marketplace on the website: every game Kiosk Studio
 * offers, each with a store page where visitors play a live demo and
 * signed-in users rate it. The same games and reviews as the marketplace
 * inside Kiosk Studio (GameTemplateController).
 */
class GameMarketplaceController extends Controller
{
    public function index(): View
    {
        $games = GameTemplate::active()->ordered()->withRatings()->get();

        return view('marketplace.games.index', [
            'games' => $games,
            'demos' => $games->filter->hasDemo()->count(),
            'reviews' => (int) $games->sum('reviews_count'),
        ]);
    }

    public function show(Request $request, GameTemplate $gameTemplate): View
    {
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);

        $gameTemplate->loadCount('reviews')->loadAvg('reviews', 'rating');
        $counts = $gameTemplate->reviews()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');

        return view('marketplace.games.show', [
            'game' => $gameTemplate,
            'reviews' => $gameTemplate->reviews()->with('user')->latest('updated_at')->latest('id')->limit(50)->get(),
            'breakdown' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn (int $stars) => [$stars => (int) ($counts[$stars] ?? 0)]),
            'mine' => $request->user() ? $gameTemplate->reviews()->where('user_id', $request->user()->id)->first() : null,
            'others' => GameTemplate::active()->ordered()->withRatings()->whereKeyNot($gameTemplate->id)->limit(4)->get(),
        ]);
    }

    /**
     * The game running in Kiosk Studio's player, on its own how-to-play
     * screen, for the store page to frame. Sandboxed like a play link.
     */
    public function demo(Request $request, GameTemplate $gameTemplate): Response
    {
        abort_unless($gameTemplate->hasDemo() && $gameTemplate->isVisibleTo($request->user()), 404);

        return response()
            ->view('marketplace.games.demo', ['game' => $gameTemplate])
            ->header('Content-Security-Policy', 'sandbox allow-scripts')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * "Write a review", for guests: signing in brings them back here.
     */
    public function startReview(Request $request, GameTemplate $gameTemplate): RedirectResponse
    {
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);

        return redirect(route('marketplace.games.show', $gameTemplate).'#review');
    }

    public function review(GameTemplateReviewRequest $request, GameTemplate $gameTemplate): RedirectResponse
    {
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);

        $gameTemplate->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['rating' => $request->integer('rating'), 'comment' => trim((string) $request->validated('comment')) ?: null],
        );

        return redirect(route('marketplace.games.show', $gameTemplate).'#reviews')->with('status', 'Thanks for your review.');
    }

    public function destroyReview(Request $request, GameTemplate $gameTemplate): RedirectResponse
    {
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);

        $gameTemplate->reviews()->where('user_id', $request->user()->id)->delete();

        return redirect(route('marketplace.games.show', $gameTemplate).'#reviews')->with('status', 'Your review was removed.');
    }
}
