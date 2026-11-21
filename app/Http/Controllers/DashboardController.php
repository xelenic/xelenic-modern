<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Support\KioskDesign;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The signed-in user's own dashboard. Site-wide numbers live in the
     * admin panel (Admin\DashboardController).
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $byStatus = $user->projects()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $count = fn (ProjectStatus ...$statuses) => collect($statuses)->sum(fn ($status) => $byStatus[$status->value] ?? 0);

        return view('dashboard', [
            'user' => $user,
            'projects' => $user->projects()->latest()->take(5)->get(),
            'counts' => [
                'total' => $byStatus->sum(),
                'review' => $count(ProjectStatus::Submitted, ProjectStatus::InReview),
                'progress' => $count(ProjectStatus::InProgress),
                'completed' => $count(ProjectStatus::Completed),
            ],
            // The projects that changed most recently, for the updates list.
            'updates' => $user->projects()->latest('updated_at')->take(4)->get(),
            // Their latest kiosk app with a Kiosk Studio design, to jump back into.
            'kioskProject' => $user->projects()
                ->where('type', ProjectType::KioskApp)
                ->latest('updated_at')
                ->take(10)
                ->get()
                ->first(fn ($project) => KioskDesign::exists($project)),
        ]);
    }
}
