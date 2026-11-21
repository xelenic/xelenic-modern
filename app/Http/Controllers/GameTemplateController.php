<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameTemplateReviewRequest;
use App\Models\GameTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kiosk Studio's game templates marketplace reads the game_templates table
 * from here (public/nignest/js/editor-packages.js): the list of games, each
 * game's store page with its reviews, and an uploaded game's .zip when it's
 * added to a design. Signed-in users review games from their page.
 */
class GameTemplateController extends Controller
{
    /**
     * The games on offer, in order, with their ratings.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            GameTemplate::active()->ordered()->withRatings()->get()->map->catalogEntry()->values()
        );
    }

    /**
     * A game's store page: pictures, write-up and reviews.
     */
    public function show(Request $request, GameTemplate $gameTemplate): JsonResponse
    {
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);

        return response()->json($gameTemplate->storePage($request->user()));
    }

    /**
     * Rate the game, or change your rating.
     */
    public function review(GameTemplateReviewRequest $request, GameTemplate $gameTemplate): JsonResponse
    {
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);

        $gameTemplate->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['rating' => $request->integer('rating'), 'comment' => trim((string) $request->validated('comment')) ?: null],
        );

        return response()->json([
            'message' => 'Thanks for your review.',
            ...$gameTemplate->storePage($request->user()),
        ]);
    }

    /**
     * Take your review down.
     */
    public function destroyReview(Request $request, GameTemplate $gameTemplate): JsonResponse
    {
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);

        $gameTemplate->reviews()->where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Your review was removed.',
            ...$gameTemplate->storePage($request->user()),
        ]);
    }

    /**
     * An uploaded game's .zip. A hidden one is only for staff.
     */
    public function download(Request $request, GameTemplate $gameTemplate): StreamedResponse
    {
        abort_if($gameTemplate->isBuiltin(), 404);
        abort_unless($gameTemplate->isVisibleTo($request->user()), 404);
        abort_unless(Storage::disk('local')->exists($gameTemplate->packagePath()), 404);

        return Storage::disk('local')->download(
            $gameTemplate->packagePath(),
            "{$gameTemplate->slug}-{$gameTemplate->version}.zip",
            ['Content-Type' => 'application/zip'],
        );
    }
}
