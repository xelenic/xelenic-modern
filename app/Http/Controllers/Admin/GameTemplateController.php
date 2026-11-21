<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameTemplateRequest;
use App\Models\GameTemplate;
use App\Models\GameTemplateReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The games offered in Kiosk Studio's marketplace (the game_templates table):
 * the studio's own, and template .zips uploaded here. Templates are part of
 * how kiosk projects get made, so it takes the "manage projects" permission.
 */
class GameTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage projects'),
        ];
    }

    /**
     * Every template, in the marketplace's order.
     */
    public function index(): View
    {
        return view('admin.game-templates.index', [
            'templates' => GameTemplate::ordered()->withRatings()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.game-templates.create', [
            'template' => new GameTemplate(['is_active' => true, 'sort_order' => (GameTemplate::max('sort_order') ?? 0) + 1]),
        ]);
    }

    public function store(GameTemplateRequest $request): RedirectResponse
    {
        $template = GameTemplate::create([
            ...$request->package(),
            // The .zip's own name and description, unless they were changed here.
            'name' => $request->validated('name') ?: $request->package()['name'],
            'description' => $request->validated('description') ?: $request->package()['description'],
            'details' => $request->validated('details'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->validated('sort_order'),
        ]);
        $request->file('package')->storeAs('game-templates', basename($template->packagePath()), 'local');
        $this->saveStorePage($request, $template);

        return to_route('admin.game-templates.index')->with('success', "{$template->name} was added to the marketplace.");
    }

    public function edit(GameTemplate $gameTemplate): View
    {
        return view('admin.game-templates.edit', [
            'template' => $gameTemplate->loadCount('reviews')->loadAvg('reviews', 'rating'),
            'reviews' => $gameTemplate->reviews()->with('user')->latest('updated_at')->get(),
        ]);
    }

    public function update(GameTemplateRequest $request, GameTemplate $gameTemplate): RedirectResponse
    {
        // A new version of the .zip brings its version, author, icon and so on;
        // the name and description are the ones on the form.
        if ($request->package()) {
            $gameTemplate->fill($request->package());
            $request->file('package')->storeAs('game-templates', basename($gameTemplate->packagePath()), 'local');
        }

        $gameTemplate->fill([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'details' => $request->validated('details'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->validated('sort_order'),
        ])->save();
        $this->saveStorePage($request, $gameTemplate);

        return to_route('admin.game-templates.index')->with('success', "{$gameTemplate->name} was updated.");
    }

    /**
     * Take down a review, say one that breaks the rules.
     */
    public function destroyReview(GameTemplate $gameTemplate, GameTemplateReview $review): RedirectResponse
    {
        abort_unless($review->game_template_id === $gameTemplate->id, 404);

        $review->delete();

        return to_route('admin.game-templates.edit', $gameTemplate)->with('success', "{$review->user->name}'s review was removed.");
    }

    /**
     * The cover photo and screenshots on the game's store page: new ones
     * uploaded, and ones ticked for removal deleted.
     */
    private function saveStorePage(GameTemplateRequest $request, GameTemplate $template): void
    {
        $disk = Storage::disk('public');

        if ($template->thumbnail_path && ($request->boolean('remove_thumbnail') || $request->hasFile('thumbnail'))) {
            $disk->delete($template->thumbnail_path);
            $template->thumbnail_path = null;
        }
        if ($request->hasFile('thumbnail')) {
            $template->thumbnail_path = $request->file('thumbnail')->store($template->mediaFolder(), 'public');
        }

        $screenshots = $template->screenshots ?? [];
        $removed = array_values(array_intersect($screenshots, (array) $request->input('remove_screenshots', [])));
        $disk->delete($removed);
        $screenshots = array_values(array_diff($screenshots, $removed));
        foreach ($request->file('screenshots', []) as $file) {
            $screenshots[] = $file->store($template->mediaFolder(), 'public');
        }
        $template->screenshots = $screenshots ?: null;

        $template->save();
    }

    /**
     * Uploaded templates only: the studio's own games can only be hidden.
     * Designs that already use a template keep their own copy of it.
     */
    public function destroy(GameTemplate $gameTemplate): RedirectResponse
    {
        abort_if($gameTemplate->isBuiltin(), 403);

        // The model deletes its .zip (see GameTemplate::booted()).
        $gameTemplate->delete();

        return to_route('admin.game-templates.index')->with('success', "{$gameTemplate->name} was removed from the marketplace.");
    }
}
