<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The admin overview. Each part only shows for people with the
     * permission behind it.
     */
    public function __invoke(Request $request): View
    {
        $canViewUsers = $request->user()->can('view users');
        $canManageProjects = $request->user()->can('manage projects');

        return view('admin.dashboard', [
            'users' => $canViewUsers ? [
                'total' => User::count(),
                'newThisWeek' => User::where('created_at', '>=', now()->subWeek())->count(),
                'latest' => User::with('roles')->latest()->take(5)->get(),
            ] : null,
            'projects' => $canManageProjects ? $this->projectStats() : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function projectStats(): array
    {
        $byStatus = Project::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $byType = Project::toBase()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return [
            'total' => $byStatus->sum(),
            'open' => collect([ProjectStatus::Submitted, ProjectStatus::InReview, ProjectStatus::InProgress])
                ->sum(fn (ProjectStatus $status) => $byStatus[$status->value] ?? 0),
            'newRequests' => $byStatus[ProjectStatus::Submitted->value] ?? 0,
            'inProgress' => $byStatus[ProjectStatus::InProgress->value] ?? 0,
            'byStatus' => collect(ProjectStatus::cases())->map(fn (ProjectStatus $status) => [
                'label' => $status->label(),
                'value' => (int) ($byStatus[$status->value] ?? 0),
                'href' => route('admin.projects.index', ['status' => $status->value]),
            ])->all(),
            'byType' => collect(ProjectType::cases())->map(fn (ProjectType $type) => [
                'label' => $type->label(),
                'icon' => $type->icon(),
                'value' => (int) ($byType[$type->value] ?? 0),
                'href' => route('admin.projects.index', ['type' => $type->value]),
            ])->all(),
            'latest' => Project::with('user')->latest()->take(6)->get(),
        ];
    }
}
