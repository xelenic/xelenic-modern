<?php

namespace App\Http\Controllers;

use App\Enums\KioskMachine;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Support\KioskSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * The signed-in user's projects. Everyone's are in the admin panel
     * (Admin\ProjectController).
     */
    public function index(Request $request): View
    {
        return view('projects.index', [
            'projects' => $request->user()->projects()->latest()->paginate(12),
        ]);
    }

    /**
     * Pick what kind of project to start.
     */
    public function create(): View
    {
        return view('projects.create', [
            'types' => ProjectType::cases(),
        ]);
    }

    /**
     * The form for one kind of project. Kiosk projects pick their machine
     * first (?machine=…), or skip that step with ?skip=1.
     */
    public function createType(Request $request, ProjectType $type): View
    {
        $machine = null;

        if ($type === ProjectType::KioskApp) {
            $machine = KioskMachine::tryFrom($request->string('machine')->value());

            if (! $machine && ! $request->boolean('skip')) {
                return view('projects.kiosk-machines', [
                    'type' => $type,
                    'machines' => KioskMachine::cases(),
                ]);
            }
        }

        // Kiosk projects finish in two more steps (projects.kiosk-form); the
        // rest fill in one page.
        return view($type === ProjectType::KioskApp ? 'projects.kiosk-form' : 'projects.form', [
            'type' => $type,
            'machine' => $machine,
            'kioskSettings' => $type === ProjectType::KioskApp ? KioskSettings::defaults($machine) : null,
            'budgets' => Project::BUDGETS,
            'timelines' => Project::TIMELINES,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->projects()->create([
            ...Arr::only($request->validated(), ['type', 'name', 'description', 'budget', 'timeline']),
            'details' => $request->details(),
        ]);

        return to_route('projects.show', $project)
            ->with('success', "Thanks! We've received your {$project->type->label()} project and will be in touch soon.");
    }

    /**
     * A client's own project. The Xelenic team opens projects from the
     * admin panel instead.
     */
    public function show(Request $request, Project $project): View
    {
        abort_unless($project->user()->is($request->user()), 403);

        return view('projects.show', [
            'project' => $project,
        ]);
    }

    /**
     * Let the client withdraw a project before work has started.
     */
    public function cancel(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->user()->is($request->user()) && $project->status->canBeCancelled(), 403);

        $project->update(['status' => ProjectStatus::Cancelled]);

        return to_route('projects.show', $project)->with('success', 'Your project was cancelled.');
    }
}
