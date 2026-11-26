<?php

namespace App\Http\Controllers;

use App\Enums\KioskMachine;
use App\Models\GameTemplate;
use App\Support\AnimationTemplates;
use Illuminate\View\View;

/**
 * The website's page about Xelenic Kiosk Studio (public/nignest): what it
 * does, with screenshots of it, the games and screens it comes with, and the
 * kiosks it designs for.
 */
class KioskStudioPageController extends Controller
{
    public function __invoke(): View
    {
        return view('studio.show', [
            'games' => GameTemplate::active()->ordered()->get(),
            'animations' => AnimationTemplates::all()->count(),
            'machines' => KioskMachine::cases(),
        ]);
    }
}
