<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\KioskDesign;
use Illuminate\Http\Response;

/**
 * A kiosk app's play link (see Project::share()): its design running in
 * Kiosk Studio's player, for anyone with the link, signed in or not.
 *
 * A design can carry a custom game's own code, so the page is sandboxed: the
 * browser gives it an origin of its own, with no access to this site's
 * cookies or session, whoever opens it.
 */
class PlayController extends Controller
{
    public function __invoke(string $token): Response
    {
        $project = Project::where('share_token', $token)->first();
        abort_unless($project?->canBeShared(), 404);

        $design = KioskDesign::design($project);
        abort_unless($design !== null, 404);

        return response()
            ->view('play', [
                'project' => $project,
                'design' => $design,
                'name' => (string) ($design['name'] ?? '') ?: $project->name,
            ])
            ->header('Content-Security-Policy', 'sandbox allow-scripts allow-popups allow-popups-to-escape-sandbox')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
