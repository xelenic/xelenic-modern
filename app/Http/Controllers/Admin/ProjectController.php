<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage projects'),
        ];
    }

    /**
     * Every client's projects, filtered by status tab, type and a search
     * over the project name and the client's name or email.
     */
    public function index(Request $request): View
    {
        $status = ProjectStatus::tryFrom($request->string('status')->value());
        $type = ProjectType::tryFrom($request->string('type')->value());
        $search = $request->string('search')->trim()->value();

        $projects = Project::query()
            ->with('user')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhereRelation('user', 'name', 'like', "%{$search}%")
                ->orWhereRelation('user', 'email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.projects.index', [
            'projects' => $projects,
            'status' => $status,
            'type' => $type,
            'statusCounts' => Project::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'types' => collect(ProjectType::cases())->mapWithKeys(fn (ProjectType $type) => [$type->value => $type->label()])->all(),
        ]);
    }

    public function show(Project $project): View
    {
        return view('admin.projects.show', [
            'project' => $project->load(['user' => fn ($query) => $query->withCount('projects'), 'user.roles']),
            'statuses' => ProjectStatus::options(),
        ]);
    }

    /**
     * Move a project along; the client sees the new status on their project.
     */
    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ]);

        $project->update($validated);

        return to_route('admin.projects.show', $project)
            ->with('success', "The project is now {$project->status->label()}.");
    }
}
